<?php

namespace App\Filament\SuperAdmin\Resources\OfficeConversations\Tables;

use App\Models\OfficeConversation;
use App\Models\Parish;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfficeConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->sortable(),

                TextColumn::make('parish.short_name')
                    ->label('Parafia')
                    ->placeholder('Brak')
                    ->searchable(['parishes.name', 'parishes.short_name'])
                    ->sortable(),

                TextColumn::make('parishioner.full_name')
                    ->label('Parafianin')
                    ->state(fn (OfficeConversation $record): string => $record->parishioner?->full_name
                        ?: $record->parishioner?->name
                        ?: $record->parishioner?->email
                        ?: 'Użytkownik usunięty')
                    ->description(fn (OfficeConversation $record): string => $record->parishioner?->email ?: 'Brak adresu e-mail')
                    ->searchable(['users.full_name', 'users.name', 'users.email'])
                    ->sortable(),

                TextColumn::make('priest.full_name')
                    ->label('Administrator')
                    ->state(fn (OfficeConversation $record): string => $record->priest?->full_name
                        ?: $record->priest?->name
                        ?: $record->priest?->email
                        ?: 'Użytkownik usunięty')
                    ->description(fn (OfficeConversation $record): string => $record->priest?->email ?: 'Brak adresu e-mail')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OfficeConversation::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => $state === OfficeConversation::STATUS_OPEN ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('messages_count')
                    ->label('Wiadomości')
                    ->badge()
                    ->sortable(),

                TextColumn::make('last_message_at')
                    ->label('Ostatnia aktywność')
                    ->since()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Utworzona')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(OfficeConversation::getStatusOptions()),

                SelectFilter::make('parish_id')
                    ->label('Parafia')
                    ->options(fn (): array => Parish::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),

                SelectFilter::make('priest_user_id')
                    ->label('Administrator')
                    ->options(fn (): array => User::query()
                        ->where('role', '>=', 1)
                        ->orderByRaw("COALESCE(NULLIF(full_name, ''), name, email)")
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [
                            $user->getKey() => $user->full_name ?: $user->name ?: $user->email,
                        ])
                        ->all()),

                Filter::make('with_unread_for_priest')
                    ->label('Nieprzeczytane po stronie administratora')
                    ->query(fn (Builder $query): Builder => $query->whereHas('messages', fn (Builder $inner): Builder => $inner
                        ->whereNull('read_by_priest_at')
                        ->whereColumn('sender_user_id', '!=', 'office_conversations.priest_user_id'))),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::statusAction(OfficeConversation::STATUS_OPEN, 'Otwórz', 'heroicon-o-lock-open', 'success'),
                    self::statusAction(OfficeConversation::STATUS_CLOSED, 'Zamknij', 'heroicon-o-lock-closed', 'gray'),
                    DeleteAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::bulkStatusAction(OfficeConversation::STATUS_OPEN, 'Otwórz zaznaczone'),
                    self::bulkStatusAction(OfficeConversation::STATUS_CLOSED, 'Zamknij zaznaczone'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function statusAction(string $status, string $label, string $icon, string $color): Action
    {
        return Action::make("set_status_{$status}")
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (OfficeConversation $record): bool => $record->status !== $status)
            ->action(function (OfficeConversation $record) use ($status): void {
                $record->update([
                    'status' => $status,
                    'closed_at' => $status === OfficeConversation::STATUS_CLOSED ? now() : null,
                ]);

                Notification::make()
                    ->success()
                    ->title('Status konwersacji zaktualizowany.')
                    ->send();
            });
    }

    protected static function bulkStatusAction(string $status, string $label): BulkAction
    {
        return BulkAction::make("bulk_set_status_{$status}")
            ->label($label)
            ->icon($status === OfficeConversation::STATUS_OPEN ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
            ->color($status === OfficeConversation::STATUS_OPEN ? 'success' : 'gray')
            ->requiresConfirmation()
            ->action(function ($records) use ($status): void {
                $updated = 0;

                foreach ($records as $record) {
                    if (! $record instanceof OfficeConversation || $record->status === $status) {
                        continue;
                    }

                    $record->update([
                        'status' => $status,
                        'closed_at' => $status === OfficeConversation::STATUS_CLOSED ? now() : null,
                    ]);

                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Operacja zakonczona')
                    ->body("Zaktualizowano konwersacje: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
