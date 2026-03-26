<?php

namespace App\Filament\SuperAdmin\Resources\UserDevices;

use App\Filament\SuperAdmin\Pages\FcmSettingsPage;
use App\Filament\SuperAdmin\Resources\Parishes\ParishResource;
use App\Filament\SuperAdmin\Resources\PushDeliveries\PushDeliveryResource;
use App\Filament\SuperAdmin\Resources\UserDevices\Pages\ListUserDevices;
use App\Filament\SuperAdmin\Resources\UserDevices\Pages\ViewUserDevice;
use App\Filament\SuperAdmin\Resources\Users\UserResource;
use App\Jobs\SendManualPushToDeviceJob;
use App\Models\PushDelivery;
use App\Models\UserDevice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Support\Push\PushDispatchService;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Operacyjny resource do zarzadzania rejestrem urzadzen push.
 *
 * Pokazuje stan zgód, tokenów i bledów dostarczenia oraz pozwala superadminowi
 * wykonywac masowe akcje naprawcze bez edycji samego modelu UserDevice.
 */
class UserDeviceResource extends Resource
{
    protected static ?string $model = UserDevice::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $modelLabel = 'urzadzenie push';

    protected static ?string $pluralModelLabel = 'urzadzenia push';

    protected static ?string $navigationLabel = 'Urzadzenia push';

    protected static string|UnitEnum|null $navigationGroup = 'Push i urzadzenia';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('id')->label('#')->badge()->sortable(),
                TextColumn::make('user.email')
                    ->label('Uzytkownik')
                    ->searchable()
                    ->sortable()
                    ->url(fn (UserDevice $record): ?string => $record->user ? UserResource::getUrl('view', ['record' => $record->user]) : null),
                TextColumn::make('parish.name')
                    ->label('Parafia')
                    ->placeholder('Brak')
                    ->toggleable()
                    ->url(fn (UserDevice $record): ?string => $record->parish ? ParishResource::getUrl('view', ['record' => $record->parish]) : null),
                TextColumn::make('platform')->label('Platforma')->badge()->sortable(),
                TextColumn::make('provider')->label('Provider')->badge()->sortable(),
                TextColumn::make('permission_status')
                    ->label('Zgoda')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'authorized', 'provisional' => 'success',
                        'denied' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('device_name')->label('Urzadzenie')->searchable()->placeholder('Brak'),
                TextColumn::make('app_version')->label('Wersja app')->sortable(),
                TextColumn::make('push_token')
                    ->label('Token')
                    ->limit(24)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_seen_at')->label('Ostatnio widziane')->since()->sortable(),
                TextColumn::make('last_push_sent_at')->label('Ostatni push OK')->since()->placeholder('Brak')->toggleable(),
                TextColumn::make('last_push_error_at')->label('Ostatni blad')->since()->placeholder('Brak')->toggleable(),
                TextColumn::make('disabled_at')
                    ->label('Wylaczone')
                    ->state(fn (UserDevice $record): string => $record->disabled_at ? $record->disabled_at->diffForHumans() : 'Aktywne')
                    ->badge()
                    ->color(fn (UserDevice $record): string => $record->disabled_at ? 'danger' : 'success'),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Platforma')
                    ->options([
                        'android' => 'Android',
                        'ios' => 'iOS',
                    ]),
                SelectFilter::make('permission_status')
                    ->label('Zgoda')
                    ->options([
                        'authorized' => 'authorized',
                        'provisional' => 'provisional',
                        'denied' => 'denied',
                        'not_determined' => 'not_determined',
                    ]),
                TernaryFilter::make('disabled_at')
                    ->label('Aktywne')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('disabled_at'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('disabled_at'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::openUserAction(),
                    self::openParishAction(),
                    self::sendTestPushAction(),
                    self::setPermissionStatusAction('authorized', 'Ustaw authorized', 'success'),
                    self::setPermissionStatusAction('provisional', 'Ustaw provisional', 'info'),
                    self::setPermissionStatusAction('denied', 'Ustaw denied', 'danger'),
                    self::setPermissionStatusAction('not_determined', 'Ustaw not_determined', 'warning'),
                    Action::make('clear_error')
                        ->label('Wyczysc blad')
                        ->visible(fn (UserDevice $record): bool => filled($record->last_push_error) || $record->last_push_error_at !== null)
                        ->action(function (UserDevice $record): void {
                            $record->forceFill([
                                'last_push_error' => null,
                                'last_push_error_at' => null,
                            ])->saveQuietly();

                            Notification::make()
                                ->success()
                                ->title('Wyczyszczono blad urzadzenia.')
                                ->send();
                        }),
                    Action::make('enable')
                        ->label('Wlacz')
                        ->visible(fn (UserDevice $record): bool => $record->disabled_at !== null)
                        ->action(function (UserDevice $record): void {
                            $record->forceFill(['disabled_at' => null])->saveQuietly();

                            Notification::make()
                                ->success()
                                ->title('Urzadzenie zostalo wlaczone.')
                                ->send();
                        }),
                    Action::make('disable')
                        ->label('Wylacz')
                        ->visible(fn (UserDevice $record): bool => $record->disabled_at === null)
                        ->color('warning')
                        ->action(function (UserDevice $record): void {
                            $record->forceFill(['disabled_at' => now()])->saveQuietly();

                            Notification::make()
                                ->success()
                                ->title('Urzadzenie zostalo wylaczone.')
                                ->send();
                        }),
                    DeleteAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::sendTestPushBulkAction(),
                    self::setPermissionStatusBulkAction('authorized', 'Ustaw authorized', 'success'),
                    self::setPermissionStatusBulkAction('provisional', 'Ustaw provisional', 'info'),
                    self::setPermissionStatusBulkAction('denied', 'Ustaw denied', 'danger'),
                    self::setPermissionStatusBulkAction('not_determined', 'Ustaw not_determined', 'warning'),
                    self::enableDevicesBulkAction(),
                    self::disableDevicesBulkAction(),
                    self::clearErrorsBulkAction(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Urzadzenie')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')->label('ID')->badge(),
                        TextEntry::make('user.email')->label('Uzytkownik'),
                        TextEntry::make('parish.name')->label('Parafia')->placeholder('Brak'),
                        TextEntry::make('provider')->label('Provider')->badge(),
                        TextEntry::make('platform')->label('Platforma')->badge(),
                        TextEntry::make('permission_status')->label('Zgoda')->badge(),
                        TextEntry::make('device_id')->label('Device ID')->copyable(),
                        TextEntry::make('device_name')->label('Nazwa urzadzenia')->placeholder('Brak'),
                        TextEntry::make('push_token')->label('Push token')->copyable()->columnSpanFull(),
                        TextEntry::make('app_version')->label('Wersja app'),
                        TextEntry::make('locale')->label('Locale')->placeholder('Brak'),
                        TextEntry::make('timezone')->label('Timezone')->placeholder('Brak'),
                        TextEntry::make('push_token_updated_at')->label('Token updated')->dateTime('d.m.Y H:i')->placeholder('Brak'),
                        TextEntry::make('last_seen_at')->label('Last seen')->dateTime('d.m.Y H:i')->placeholder('Brak'),
                        TextEntry::make('last_push_sent_at')->label('Last push sent')->dateTime('d.m.Y H:i')->placeholder('Brak'),
                        TextEntry::make('last_push_error_at')->label('Last push error')->dateTime('d.m.Y H:i')->placeholder('Brak'),
                        TextEntry::make('last_push_error')->label('Opis bledu')->columnSpanFull()->placeholder('Brak'),
                        TextEntry::make('disabled_at')->label('Wylaczone')->dateTime('d.m.Y H:i')->placeholder('Nie'),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'parish'])
            ->orderByDesc('last_seen_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserDevices::route('/'),
            'view' => ViewUserDevice::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->pushable()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getEloquentQuery()->whereNotNull('disabled_at')->exists()
            ? 'warning'
            : 'success';
    }

    protected static function openUserAction(): Action
    {
        return Action::make('open_user')
            ->label('Otworz uzytkownika')
            ->icon('heroicon-o-user')
            ->visible(fn (UserDevice $record): bool => $record->user !== null)
            ->url(fn (UserDevice $record): ?string => $record->user ? UserResource::getUrl('view', ['record' => $record->user]) : null);
    }

    protected static function openParishAction(): Action
    {
        return Action::make('open_parish')
            ->label('Otworz parafie')
            ->icon('heroicon-o-building-library')
            ->visible(fn (UserDevice $record): bool => $record->parish !== null)
            ->url(fn (UserDevice $record): ?string => $record->parish ? ParishResource::getUrl('view', ['record' => $record->parish]) : null);
    }

    protected static function sendTestPushAction(): Action
    {
        return Action::make('send_test_push')
            ->label('Test push')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->schema([
                Select::make('type')
                    ->label('Typ')
                    ->options([
                        'TEST_MESSAGE' => 'TEST_MESSAGE',
                        'NEWS_CREATED' => 'NEWS_CREATED',
                        'ANNOUNCEMENTS_PACKAGE_PUBLISHED' => 'ANNOUNCEMENTS_PACKAGE_PUBLISHED',
                        'MASS_PENDING' => 'MASS_PENDING',
                        'OFFICE_MESSAGE_RECEIVED' => 'OFFICE_MESSAGE_RECEIVED',
                        'PARISH_APPROVAL_STATUS_CHANGED' => 'PARISH_APPROVAL_STATUS_CHANGED',
                    ])
                    ->default('TEST_MESSAGE')
                    ->required(),
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default('Test push dla urzadzenia'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->rows(4)
                    ->required()
                    ->default('Wiadomosc testowa wyslana bezposrednio do wybranego urzadzenia.'),
            ])
            ->action(function (UserDevice $record, array $data, PushDispatchService $dispatcher): void {
                $delivery = $dispatcher->sendTestPush(
                    token: (string) $record->push_token,
                    platform: (string) $record->platform,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                    type: (string) $data['type'],
                    routingData: [
                        'source' => 'superadmin_user_device_resource',
                        'device_id' => (string) $record->getKey(),
                    ],
                    device: $record,
                    user: $record->user,
                );

                Notification::make()
                    ->title($delivery->status === PushDelivery::STATUS_SENT ? 'Test push wyslany.' : 'Test push nieudany.')
                    ->body($delivery->status === PushDelivery::STATUS_SENT
                        ? 'Sprawdz dostarczenia push po szczegoly.'
                        : ($delivery->error_message ?: 'Szczegoly sa zapisane w dostarczeniu push.'))
                    ->color($delivery->status === PushDelivery::STATUS_SENT ? 'success' : 'danger')
                    ->send();
            });
    }

    protected static function setPermissionStatusAction(string $status, string $label, string $color): Action
    {
        return Action::make('set_permission_'.$status)
            ->label($label)
            ->color($color)
            ->visible(fn (UserDevice $record): bool => $record->permission_status !== $status)
            ->action(function (UserDevice $record) use ($status): void {
                $record->forceFill([
                    'permission_status' => $status,
                    'disabled_at' => $status === 'denied' ? now() : null,
                ])->saveQuietly();

                Notification::make()
                    ->success()
                    ->title("Ustawiono permission_status = {$status}.")
                    ->send();
            });
    }

    protected static function sendTestPushBulkAction(): BulkAction
    {
        return BulkAction::make('send_test_push_bulk')
            ->label('Test push do zaznaczonych')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->schema([
                Select::make('type')
                    ->label('Typ')
                    ->options([
                        'TEST_MESSAGE' => 'TEST_MESSAGE',
                        'NEWS_CREATED' => 'NEWS_CREATED',
                        'ANNOUNCEMENTS_PACKAGE_PUBLISHED' => 'ANNOUNCEMENTS_PACKAGE_PUBLISHED',
                        'MASS_PENDING' => 'MASS_PENDING',
                        'OFFICE_MESSAGE_RECEIVED' => 'OFFICE_MESSAGE_RECEIVED',
                        'PARISH_APPROVAL_STATUS_CHANGED' => 'PARISH_APPROVAL_STATUS_CHANGED',
                    ])
                    ->default('TEST_MESSAGE')
                    ->required(),
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default('Test push dla zaznaczonych urzadzen'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->rows(4)
                    ->required()
                    ->default('Wiadomosc testowa wyslana do zaznaczonych urzadzen push.'),
            ])
            ->action(function ($records, array $data): void {
                $devices = collect($records)
                    ->filter(fn ($record): bool => $record instanceof UserDevice)
                    ->values();

                $queued = 0;
                $skipped = 0;

                foreach ($devices as $device) {
                    if (! filled($device->push_token) || $device->disabled_at !== null) {
                        $skipped++;

                        continue;
                    }

                    SendManualPushToDeviceJob::dispatch(
                        deviceId: (int) $device->getKey(),
                        userId: $device->user_id ? (int) $device->user_id : null,
                        title: (string) $data['title'],
                        body: (string) $data['body'],
                        type: (string) $data['type'],
                        routingData: [
                            'source' => 'superadmin_user_devices_bulk',
                            'bulk' => true,
                        ],
                    );

                    $queued++;
                }

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano test push do zaznaczonych urzadzen.')
                    ->body("Queued: {$queued} · skipped: {$skipped}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function setPermissionStatusBulkAction(string $status, string $label, string $color): BulkAction
    {
        return BulkAction::make('bulk_permission_'.$status)
            ->label($label)
            ->color($color)
            ->requiresConfirmation()
            ->action(function ($records) use ($status): void {
                $devices = collect($records)
                    ->filter(fn ($record): bool => $record instanceof UserDevice)
                    ->values();

                $updated = 0;

                foreach ($devices as $device) {
                    if ($device->permission_status === $status && (($status === 'denied') === ($device->disabled_at !== null))) {
                        continue;
                    }

                    $device->forceFill([
                        'permission_status' => $status,
                        'disabled_at' => $status === 'denied' ? now() : null,
                    ])->saveQuietly();

                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title("Zaktualizowano permission_status = {$status}.")
                    ->body("Liczba urzadzen: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function enableDevicesBulkAction(): BulkAction
    {
        return BulkAction::make('bulk_enable_devices')
            ->label('Wlacz zaznaczone')
            ->icon('heroicon-o-play')
            ->color('success')
            ->requiresConfirmation()
            ->action(function ($records): void {
                $devices = collect($records)
                    ->filter(fn ($record): bool => $record instanceof UserDevice)
                    ->values();

                $updated = 0;

                foreach ($devices as $device) {
                    if ($device->disabled_at === null) {
                        continue;
                    }

                    $device->forceFill(['disabled_at' => null])->saveQuietly();
                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Wlaczono zaznaczone urzadzenia.')
                    ->body("Liczba urzadzen: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function disableDevicesBulkAction(): BulkAction
    {
        return BulkAction::make('bulk_disable_devices')
            ->label('Wylacz zaznaczone')
            ->icon('heroicon-o-pause')
            ->color('warning')
            ->requiresConfirmation()
            ->action(function ($records): void {
                $devices = collect($records)
                    ->filter(fn ($record): bool => $record instanceof UserDevice)
                    ->values();

                $updated = 0;

                foreach ($devices as $device) {
                    if ($device->disabled_at !== null) {
                        continue;
                    }

                    $device->forceFill(['disabled_at' => now()])->saveQuietly();
                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Wylaczono zaznaczone urzadzenia.')
                    ->body("Liczba urzadzen: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function clearErrorsBulkAction(): BulkAction
    {
        return BulkAction::make('bulk_clear_errors')
            ->label('Wyczysc bledy')
            ->icon('heroicon-o-x-mark')
            ->color('gray')
            ->action(function ($records): void {
                $devices = collect($records)
                    ->filter(fn ($record): bool => $record instanceof UserDevice)
                    ->values();

                $updated = 0;

                foreach ($devices as $device) {
                    if (! filled($device->last_push_error) && $device->last_push_error_at === null) {
                        continue;
                    }

                    $device->forceFill([
                        'last_push_error' => null,
                        'last_push_error_at' => null,
                    ])->saveQuietly();

                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Wyczyszczono bledy zaznaczonych urzadzen.')
                    ->body("Liczba urzadzen: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
