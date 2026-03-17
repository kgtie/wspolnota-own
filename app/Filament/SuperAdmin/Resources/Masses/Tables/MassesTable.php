<?php

namespace App\Filament\SuperAdmin\Resources\Masses\Tables;

use App\Models\Mass;
use App\Models\Parish;
use App\Models\User;
use App\Support\SuperAdmin\InstantCommunicationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('celebration_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('celebration_at')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('parish.name')
                    ->label('Parafia')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('intention_title')
                    ->label('Intencja')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Mass $record): ?string => $record->intention_details ? (string) str($record->intention_details)->limit(80) : null),

                TextColumn::make('mass_kind')
                    ->label('Rodzaj')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => Mass::getMassKindOptions()[$state] ?? $state),

                TextColumn::make('mass_type')
                    ->label('Typ')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => Mass::getMassTypeOptions()[$state] ?? $state),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => Mass::getStatusOptions()[$state] ?? $state),

                TextColumn::make('celebrant_name')
                    ->label('Celebrans')
                    ->searchable()
                    ->placeholder('Nie przypisano')
                    ->toggleable(),

                TextColumn::make('participants_count')
                    ->label('Uczestnicy')
                    ->state(fn (Mass $record): string => (string) ($record->participants_count ?? 0))
                    ->badge()
                    ->sortable()
                    ->color(fn (Mass $record): string => (($record->participants_count ?? 0) > 0) ? 'success' : 'gray'),

                TextColumn::make('reminder_push_24h_count')
                    ->label('Push 24h')
                    ->badge()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('reminder_push_8h_count')
                    ->label('Push 8h')
                    ->badge()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('reminder_push_1h_count')
                    ->label('Push 1h')
                    ->badge()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('reminder_email_count')
                    ->label('Email 5:00')
                    ->badge()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('stipendium_amount')
                    ->label('Stypendium')
                    ->state(fn (Mass $record): string => $record->stipendium_amount !== null
                        ? number_format((float) $record->stipendium_amount, 2, ',', ' ').' PLN'
                        : 'Brak')
                    ->badge()
                    ->color(fn (Mass $record): string => $record->stipendium_amount !== null ? 'success' : 'gray')
                    ->toggleable(),

                TextColumn::make('stipendium_paid_at')
                    ->label('Oplacone')
                    ->state(fn (Mass $record): string => $record->stipendium_paid_at ? 'Tak' : 'Nie')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Tak' ? 'success' : 'warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdBy.full_name')
                    ->label('Utworzyl')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updatedBy.full_name')
                    ->label('Edytowal')
                    ->placeholder('Brak')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label('Nadchodzace')
                    ->query(fn (Builder $query): Builder => $query->where('celebration_at', '>=', now()->startOfDay())),

                Filter::make('past')
                    ->label('Przeszle')
                    ->query(fn (Builder $query): Builder => $query->where('celebration_at', '<', now()->startOfDay())),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Mass::getStatusOptions()),

                SelectFilter::make('parish_id')
                    ->label('Parafia')
                    ->options(fn (): array => Parish::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),

                SelectFilter::make('mass_kind')
                    ->label('Rodzaj')
                    ->options(Mass::getMassKindOptions()),

                SelectFilter::make('mass_type')
                    ->label('Typ')
                    ->options(Mass::getMassTypeOptions()),

                TernaryFilter::make('stipendium_amount')
                    ->label('Stypendium')
                    ->nullable()
                    ->trueLabel('Jest')
                    ->falseLabel('Brak'),

                TernaryFilter::make('stipendium_paid_at')
                    ->label('Stypendium oplacone')
                    ->nullable()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::sendInstantPushAction(),
                    self::sendInstantEmailAction(),
                    self::setStatusAction('completed', 'Oznacz jako odprawiona', 'heroicon-o-check-circle', 'success'),
                    self::setStatusAction('scheduled', 'Oznacz jako zaplanowana', 'heroicon-o-clock', 'warning'),
                    self::setStatusAction('cancelled', 'Oznacz jako odwolana', 'heroicon-o-x-circle', 'danger'),
                    self::duplicateAction(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::sendInstantPushBulkAction(),
                    self::sendInstantEmailBulkAction(),
                    self::bulkSetStatusAction('completed', 'Oznacz zaznaczone jako odprawione', 'heroicon-o-check-circle', 'success'),
                    self::bulkSetStatusAction('cancelled', 'Oznacz zaznaczone jako odwolane', 'heroicon-o-x-circle', 'danger'),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }

    protected static function setStatusAction(string $status, string $label, string $icon, string $color): Action
    {
        return Action::make("set_status_{$status}")
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (Mass $record): bool => $record->status !== $status)
            ->requiresConfirmation()
            ->action(function (Mass $record) use ($status): void {
                $admin = Filament::auth()->user();

                $record->update([
                    'status' => $status,
                    'updated_by_user_id' => $admin instanceof User ? $admin->id : $record->updated_by_user_id,
                ]);
            })
            ->successNotificationTitle('Status mszy zostal zaktualizowany.');
    }

    protected static function duplicateAction(): Action
    {
        return Action::make('duplicate_mass')
            ->label('Duplikuj wpis')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->action(function (Mass $record): void {
                $admin = Filament::auth()->user();
                $clone = $record->replicate();

                $clone->celebration_at = $record->celebration_at?->copy()->addWeek();
                $clone->status = 'scheduled';
                $clone->stipendium_paid_at = null;
                $clone->created_by_user_id = $admin instanceof User ? $admin->id : $record->created_by_user_id;
                $clone->updated_by_user_id = null;
                $clone->save();

                if ($admin instanceof User) {
                    activity('admin-mass-management')
                        ->causedBy($admin)
                        ->performedOn($clone)
                        ->event('mass_duplicated')
                        ->withProperties([
                            'parish_id' => $clone->parish_id,
                            'source_mass_id' => $record->getKey(),
                            'new_mass_id' => $clone->getKey(),
                        ])
                        ->log('Proboszcz zduplikowal wpis mszy.');
                }

                Notification::make()
                    ->success()
                    ->title('Utworzono kopie mszy.')
                    ->body('Nowy wpis otrzymal termin przesuniety o 7 dni.')
                    ->send();
            });
    }

    protected static function sendInstantPushAction(): Action
    {
        return Action::make('send_mass_push_now')
            ->label('Push teraz')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('info')
            ->visible(fn (Mass $record): bool => $record->status === 'scheduled' && ($record->participants_count ?? 0) > 0)
            ->schema([
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default(fn (): string => 'Przypomnienie o mszy'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(5)
                    ->maxLength(1000)
                    ->default(fn (Mass $record): string => 'Zbliza sie msza: '.$record->intention_title),
            ])
            ->action(function (Mass $record, array $data, InstantCommunicationService $service): void {
                $users = $record->participants()->with('devices')->where('status', 'active')->get();

                $result = $service->queuePushToUsers(
                    users: $users,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                    type: 'MASS_PENDING',
                    routingData: [
                        'mass_id' => (string) $record->getKey(),
                        'parish_id' => (string) $record->parish_id,
                        'reminder_key' => 'manual',
                        'source' => 'superadmin_manual',
                    ],
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano push dla uczestnikow mszy.')
                    ->body("Uzytkownicy: {$result['users']} · urzadzenia: {$result['devices']} · skipped: {$result['skipped']}")
                    ->send();
            });
    }

    protected static function sendInstantEmailAction(): Action
    {
        return Action::make('send_mass_email_now')
            ->label('Email teraz')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->visible(fn (Mass $record): bool => $record->status === 'scheduled' && ($record->participants_count ?? 0) > 0)
            ->schema([
                TextInput::make('subject')
                    ->label('Temat')
                    ->required()
                    ->maxLength(200)
                    ->default(fn (Mass $record): string => 'Przypomnienie o mszy: '.$record->intention_title),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(8)
                    ->maxLength(12000)
                    ->default(fn (Mass $record): string => 'Przypominamy o nadchodzacej mszy: '.$record->intention_title),
            ])
            ->action(function (Mass $record, array $data, InstantCommunicationService $service): void {
                $actor = Filament::auth()->user();
                $users = $record->participants()->where('status', 'active')->get();

                $result = $service->sendEmailToUsers(
                    users: $users,
                    subjectLine: (string) $data['subject'],
                    messageBody: (string) $data['body'],
                    actor: $actor instanceof User ? $actor : null,
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano email dla uczestnikow mszy.')
                    ->body("Odbiorcy: {$result['users']} · queued: {$result['queued']} · skipped: {$result['skipped']}")
                    ->send();
            });
    }

    protected static function bulkSetStatusAction(string $status, string $label, string $icon, string $color): BulkAction
    {
        return BulkAction::make("bulk_set_status_{$status}")
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->action(function ($records) use ($status): void {
                $admin = Filament::auth()->user();
                $updated = 0;
                $updatedIds = [];
                $selectedCount = is_countable($records) ? count($records) : 0;

                foreach ($records as $record) {
                    if (! $record instanceof Mass || $record->status === $status) {
                        continue;
                    }

                    $record->update([
                        'status' => $status,
                        'updated_by_user_id' => $admin instanceof User ? $admin->id : $record->updated_by_user_id,
                    ]);
                    $updated++;
                    $updatedIds[] = $record->getKey();
                }

                if ($admin instanceof User && $updated > 0) {
                    $firstUpdatedMass = $records
                        ->first(fn ($record): bool => $record instanceof Mass && in_array($record->getKey(), $updatedIds, true));

                    activity('admin-mass-management')
                        ->causedBy($admin)
                        ->event('masses_bulk_status_updated')
                        ->withProperties([
                            'parish_id' => $firstUpdatedMass instanceof Mass ? $firstUpdatedMass->parish_id : null,
                            'target_status' => $status,
                            'selected_count' => $selectedCount,
                            'updated_count' => $updated,
                            'updated_mass_ids' => $updatedIds,
                        ])
                        ->log('Proboszcz masowo zaktualizowal statusy mszy.');
                }

                Notification::make()
                    ->success()
                    ->title('Zaktualizowano statusy mszy.')
                    ->body("Liczba zmienionych rekordow: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function sendInstantPushBulkAction(): BulkAction
    {
        return BulkAction::make('send_mass_push_bulk')
            ->label('Push teraz')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('info')
            ->schema([
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default('Przypomnienie o wybranych mszach'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(5)
                    ->maxLength(1000),
            ])
            ->action(function ($records, array $data, InstantCommunicationService $service): void {
                $masses = collect($records)
                    ->filter(fn ($record): bool => $record instanceof Mass && $record->status === 'scheduled')
                    ->values();

                $users = $masses
                    ->flatMap(fn (Mass $record) => $record->participants()->with('devices')->where('status', 'active')->get())
                    ->unique(fn (User $user): int => (int) $user->getKey())
                    ->values();

                $result = $service->queuePushToUsers(
                    users: $users,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                    type: 'MANUAL_MESSAGE',
                    routingData: [
                        'scope' => 'masses_bulk',
                        'mass_ids' => json_encode($masses->map(fn (Mass $record): int => (int) $record->getKey())->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'parish_ids' => json_encode($masses->pluck('parish_id')->filter()->unique()->values()->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'source' => 'superadmin_bulk',
                    ],
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano push dla uczestnikow zaznaczonych mszy.')
                    ->body("Uzytkownicy: {$result['users']} · urzadzenia: {$result['devices']} · skipped: {$result['skipped']}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function sendInstantEmailBulkAction(): BulkAction
    {
        return BulkAction::make('send_mass_email_bulk')
            ->label('Email teraz')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->schema([
                TextInput::make('subject')
                    ->label('Temat')
                    ->required()
                    ->maxLength(200)
                    ->default('Przypomnienie o wybranych mszach'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(8)
                    ->maxLength(12000),
            ])
            ->action(function ($records, array $data, InstantCommunicationService $service): void {
                $actor = Filament::auth()->user();
                $masses = collect($records)
                    ->filter(fn ($record): bool => $record instanceof Mass && $record->status === 'scheduled')
                    ->values();

                $users = $masses
                    ->flatMap(fn (Mass $record) => $record->participants()->where('status', 'active')->get())
                    ->unique(fn (User $user): int => (int) $user->getKey())
                    ->values();

                $result = $service->sendEmailToUsers(
                    users: $users,
                    subjectLine: (string) $data['subject'],
                    messageBody: (string) $data['body'],
                    actor: $actor instanceof User ? $actor : null,
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano email dla uczestnikow zaznaczonych mszy.')
                    ->body("Odbiorcy: {$result['users']} · queued: {$result['queued']} · skipped: {$result['skipped']}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
