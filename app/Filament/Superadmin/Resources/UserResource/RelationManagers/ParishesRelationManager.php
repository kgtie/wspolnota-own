<?php

namespace App\Filament\Superadmin\Resources\UserResource\RelationManagers;

use App\Models\Parish;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

/**
 * ParishesRelationManager - Zarządzanie parafiami użytkownika (Filament 4)
 */
class ParishesRelationManager extends RelationManager
{
    protected static string $relationship = 'parishes';

    protected static ?string $title = 'Zarządzane parafie';

    protected static ?string $recordTitleAttribute = 'short_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('short_name')
                    ->label('Nazwa')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('short_name')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->short_name) . '&background=3b82f6&color=fff'),

                Tables\Columns\TextColumn::make('short_name')
                    ->label('Nazwa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Miejscowość')
                    ->searchable(),

                Tables\Columns\TextColumn::make('diocese')
                    ->label('Diecezja')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktywna')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->label('Przypisz parafię')
                    ->preloadRecordSelect()
                    ->recordTitle(fn (Parish $record) => "{$record->short_name} ({$record->city})")
                    ->modalHeading('Przypisz parafię do użytkownika')
                    ->modalSubmitActionLabel('Przypisz'),
            ])
            ->recordActions([
                Actions\DetachAction::make()
                    ->label('Odpisz'),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DetachBulkAction::make()
                        ->label('Odpisz zaznaczone'),
                ]),
            ])
            ->emptyStateHeading('Brak przypisanych parafii')
            ->emptyStateDescription('Przypisz parafie, którymi użytkownik będzie mógł zarządzać.')
            ->emptyStateIcon('heroicon-o-building-library');
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->role >= 1;
    }
}
