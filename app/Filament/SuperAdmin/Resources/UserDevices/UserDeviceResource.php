<?php

namespace App\Filament\SuperAdmin\Resources\UserDevices;

use App\Filament\SuperAdmin\Resources\UserDevices\Pages\ListUserDevices;
use App\Filament\SuperAdmin\Resources\UserDevices\Pages\ViewUserDevice;
use App\Models\UserDevice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

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
                TextColumn::make('user.email')->label('Uzytkownik')->searchable()->sortable(),
                TextColumn::make('parish.name')->label('Parafia')->placeholder('Brak')->toggleable(),
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
                    Action::make('enable')
                        ->label('Wlacz')
                        ->visible(fn (UserDevice $record): bool => $record->disabled_at !== null)
                        ->action(fn (UserDevice $record) => $record->forceFill(['disabled_at' => null])->saveQuietly()),
                    Action::make('disable')
                        ->label('Wylacz')
                        ->visible(fn (UserDevice $record): bool => $record->disabled_at === null)
                        ->color('warning')
                        ->action(fn (UserDevice $record) => $record->forceFill(['disabled_at' => now()])->saveQuietly()),
                    DeleteAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
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
}
