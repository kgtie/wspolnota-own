<?php

namespace App\Filament\Admin\Resources\Masses\Tables;

use App\Models\Mass;
use App\Models\User;
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
                $clone = $record->replicate(['participants_count']);

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
                            'parish_id' => Filament::getTenant()?->getKey(),
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
                    activity('admin-mass-management')
                        ->causedBy($admin)
                        ->event('masses_bulk_status_updated')
                        ->withProperties([
                            'parish_id' => Filament::getTenant()?->getKey(),
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
}
