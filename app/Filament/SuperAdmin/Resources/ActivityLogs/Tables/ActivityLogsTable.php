<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs\Tables;

use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Buduje tabelaryczny eksplorator activity_log z naciskiem na audyt,
 * triage incydentow i szybkie przechodzenie do encji powiazanych.
 */
class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('created_at')
                    ->label('Czas')
                    ->dateTime('d.m.Y H:i:s')
                    ->sinceTooltip()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Kategoria')
                    ->state(fn (Activity $record): string => ActivityLogResource::eventCategoryLabel($record))
                    ->badge()
                    ->color(fn (Activity $record): string => ActivityLogResource::eventCategoryColor($record))
                    ->toggleable(),

                TextColumn::make('log_name')
                    ->label('Strumien')
                    ->state(fn (Activity $record): string => ActivityLogResource::logNameLabel($record->log_name))
                    ->badge()
                    ->color(fn (Activity $record): string => ActivityLogResource::logNameColor($record->log_name))
                    ->sortable()
                    ->searchable(isIndividual: true),

                TextColumn::make('event')
                    ->label('Event')
                    ->state(fn (Activity $record): string => ActivityLogResource::eventLabel($record->event))
                    ->tooltip(fn (Activity $record): string => $record->event ?: 'Brak eventu')
                    ->badge()
                    ->color(fn (Activity $record): string => ActivityLogResource::eventColor($record))
                    ->sortable()
                    ->searchable(isIndividual: true),

                TextColumn::make('outcome')
                    ->label('Wynik')
                    ->state(fn (Activity $record): string => ActivityLogResource::outcomeLabel($record))
                    ->badge()
                    ->color(fn (Activity $record): string => ActivityLogResource::outcomeColor($record))
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Opis')
                    ->searchable()
                    ->wrap()
                    ->limit(120),

                TextColumn::make('causer_ref')
                    ->label('Sprawca')
                    ->state(fn (Activity $record): string => ActivityLogResource::relationLabel(
                        $record->causer,
                        $record->causer_type,
                        $record->causer_id,
                    ))
                    ->url(fn (Activity $record): ?string => ActivityLogResource::relationUrl(
                        $record->causer,
                        $record->causer_type,
                        $record->causer_id,
                    ))
                    ->toggleable(),

                TextColumn::make('subject_ref')
                    ->label('Obiekt')
                    ->state(fn (Activity $record): string => ActivityLogResource::relationLabel(
                        $record->subject,
                        $record->subject_type,
                        $record->subject_id,
                    ))
                    ->url(fn (Activity $record): ?string => ActivityLogResource::relationUrl(
                        $record->subject,
                        $record->subject_type,
                        $record->subject_id,
                    ))
                    ->toggleable(),

                TextColumn::make('context_summary')
                    ->label('Kontekst')
                    ->state(fn (Activity $record): string => ActivityLogResource::contextSummary($record))
                    ->tooltip(fn (Activity $record): string => ActivityLogResource::contextPretty($record))
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('batch_uuid')
                    ->label('Batch UUID')
                    ->copyable()
                    ->copyMessage('Skopiowano UUID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Brak'),

                TextColumn::make('id')
                    ->label('#')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Strumien logow')
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('log_name')
                        ->select('log_name')
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name')
                        ->mapWithKeys(fn (string $logName): array => [$logName => ActivityLogResource::logNameLabel($logName)])
                        ->all()),

                SelectFilter::make('event')
                    ->label('Event')
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('event')
                        ->select('event')
                        ->distinct()
                        ->orderBy('event')
                        ->pluck('event')
                        ->mapWithKeys(fn (string $event): array => [$event => ActivityLogResource::eventLabel($event)])
                        ->all()),

                SelectFilter::make('causer_type')
                    ->label('Typ sprawcy')
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('causer_type')
                        ->select('causer_type')
                        ->distinct()
                        ->orderBy('causer_type')
                        ->pluck('causer_type')
                        ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])
                        ->all()),

                SelectFilter::make('subject_type')
                    ->label('Typ obiektu')
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('subject_type')
                        ->select('subject_type')
                        ->distinct()
                        ->orderBy('subject_type')
                        ->pluck('subject_type')
                        ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])
                        ->all()),

                Filter::make('only_failures')
                    ->label('Tylko niepowodzenia')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ActivityLogResource::applyFailureScope($query)),

                Filter::make('only_security')
                    ->label('Tylko zdarzenia wrazliwe')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ActivityLogResource::applySecurityScope($query)),

                Filter::make('references')
                    ->label('Powiazania i kontekst')
                    ->columns(2)
                    ->schema([
                        TextInput::make('causer_id')
                            ->label('ID sprawcy')
                            ->numeric(),
                        TextInput::make('subject_id')
                            ->label('ID obiektu')
                            ->numeric(),
                        TextInput::make('batch_uuid')
                            ->label('Batch UUID'),
                        TextInput::make('parish_id')
                            ->label('Parafia ID'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['causer_id'] ?? null, fn (Builder $query, $causerId): Builder => $query->where('causer_id', (int) $causerId))
                            ->when($data['subject_id'] ?? null, fn (Builder $query, $subjectId): Builder => $query->where('subject_id', (int) $subjectId))
                            ->when($data['batch_uuid'] ?? null, fn (Builder $query, $batchUuid): Builder => $query->where('batch_uuid', (string) $batchUuid))
                            ->when($data['parish_id'] ?? null, fn (Builder $query, $parishId): Builder => ActivityLogResource::applyParishContextScope($query, $parishId));
                    }),

                Filter::make('created_between')
                    ->label('Zakres czasu')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('from')
                            ->label('Od')
                            ->seconds(false),
                        DateTimePicker::make('until')
                            ->label('Do')
                            ->seconds(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $from): Builder => $query->where('created_at', '>=', $from))
                            ->when($data['until'] ?? null, fn (Builder $query, $until): Builder => $query->where('created_at', '<=', $until));
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('open_subject')
                        ->label('Otwórz obiekt')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->visible(fn (Activity $record): bool => filled(ActivityLogResource::relationUrl(
                            $record->subject,
                            $record->subject_type,
                            $record->subject_id,
                        )))
                        ->url(fn (Activity $record): ?string => ActivityLogResource::relationUrl(
                            $record->subject,
                            $record->subject_type,
                            $record->subject_id,
                        )),
                    Action::make('open_causer')
                        ->label('Otwórz sprawcę')
                        ->icon('heroicon-o-user')
                        ->visible(fn (Activity $record): bool => filled(ActivityLogResource::relationUrl(
                            $record->causer,
                            $record->causer_type,
                            $record->causer_id,
                        )))
                        ->url(fn (Activity $record): ?string => ActivityLogResource::relationUrl(
                            $record->causer,
                            $record->causer_type,
                            $record->causer_id,
                        )),
                    DeleteAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Brak logow aktywnosci')
            ->emptyStateDescription('To miejsce sluzy do sledzenia wszystkich waznych zdarzen biznesowych, bezpieczenstwa i automatyki systemu.');
    }
}
