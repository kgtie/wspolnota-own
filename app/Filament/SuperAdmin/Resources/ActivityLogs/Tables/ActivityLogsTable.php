<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs\Tables;

use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->placeholder('default'),

                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Brak'),

                TextColumn::make('description')
                    ->label('Opis')
                    ->searchable()
                    ->wrap()
                    ->limit(140),

                TextColumn::make('causer_ref')
                    ->label('Sprawca')
                    ->state(fn (Activity $record): string => ActivityLogResource::relationLabel(
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
                    ->toggleable(),

                TextColumn::make('properties')
                    ->label('Properties')
                    ->state(fn (Activity $record): string => ActivityLogResource::propertiesPreview($record))
                    ->tooltip(fn (Activity $record): string => ActivityLogResource::propertiesPretty($record))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('batch_uuid')
                    ->label('Batch UUID')
                    ->copyable()
                    ->copyMessage('Skopiowano UUID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Brak'),

                TextColumn::make('created_at')
                    ->label('Czas')
                    ->dateTime('d.m.Y H:i:s')
                    ->sinceTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Log')
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('log_name')
                        ->select('log_name')
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->all()),

                SelectFilter::make('event')
                    ->label('Event')
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('event')
                        ->select('event')
                        ->distinct()
                        ->orderBy('event')
                        ->pluck('event', 'event')
                        ->all()),

                SelectFilter::make('causer_type')
                    ->label('Typ sprawcy')
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
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('subject_type')
                        ->select('subject_type')
                        ->distinct()
                        ->orderBy('subject_type')
                        ->pluck('subject_type')
                        ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])
                        ->all()),

                TernaryFilter::make('batch_uuid')
                    ->label('W partii')
                    ->queries(
                        true: fn (Builder $query, array $data): Builder => $query->whereNotNull('batch_uuid'),
                        false: fn (Builder $query, array $data): Builder => $query->whereNull('batch_uuid'),
                    ),

                Filter::make('created_between')
                    ->label('Zakres czasu')
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
            ->emptyStateDescription('Wpisy pojawia sie automatycznie, gdy aplikacja wykona akcje logowane przez Spatie.');
    }
}
