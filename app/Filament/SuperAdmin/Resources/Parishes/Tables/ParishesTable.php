<?php

namespace App\Filament\SuperAdmin\Resources\Parishes\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ParishesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('Avatar')
                    ->collection('avatar')
                    ->conversion('thumb')
                    ->circular(),

                TextColumn::make('name')
                    ->label('Parafia')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->city),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->copyable()
                    ->copyMessage('Skopiowano slug')
                    ->searchable(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktywna' : 'Nieaktywna')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('parishioners_count')->label('Parafianie')->badge()->sortable(),
                TextColumn::make('admins_count')->label('Admini')->badge()->sortable(),
                TextColumn::make('office_conversations_count')->label('Konwersacje')->badge()->sortable(),

                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktywna')
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
