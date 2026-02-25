<?php

namespace App\Filament\SuperAdmin\Resources\Settings\Tables;

use App\Filament\SuperAdmin\Resources\Settings\SettingResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelSettings\Models\SettingsProperty;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('group')
                    ->label('Grupa')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nazwa')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Skopiowano nazwe'),

                TextColumn::make('payload')
                    ->label('Payload')
                    ->state(fn (SettingsProperty $record): string => SettingResource::payloadPreview($record->payload, 120))
                    ->tooltip(fn (SettingsProperty $record): string => SettingResource::prettyPayload($record->payload))
                    ->copyable()
                    ->copyableState(fn (SettingsProperty $record): string => SettingResource::prettyPayload($record->payload))
                    ->copyMessage('Skopiowano payload')
                    ->wrap(),

                IconColumn::make('locked')
                    ->label('Lock')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Ostatnia zmiana')
                    ->dateTime('d.m.Y H:i')
                    ->sinceTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('Grupa')
                    ->options(fn (): array => SettingsProperty::query()
                        ->select('group')
                        ->distinct()
                        ->orderBy('group')
                        ->pluck('group', 'group')
                        ->all()),

                TernaryFilter::make('locked')
                    ->label('Zablokowane')
                    ->boolean()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('lock_selected')
                        ->label('Zablokuj zaznaczone')
                        ->icon('heroicon-o-lock-closed')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn (SettingsProperty $record) => $record->update(['locked' => true]));
                        }),
                    BulkAction::make('unlock_selected')
                        ->label('Odblokuj zaznaczone')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn (SettingsProperty $record) => $record->update(['locked' => false]));
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Brak wpisow w tabeli settings')
            ->emptyStateDescription('Dodaj pierwsze ustawienie, aby rozpoczac konfiguracje aplikacji.');
    }
}
