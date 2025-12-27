<?php

namespace App\Filament\Superadmin\Resources\ParishResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

/**
 * AdminsRelationManager - Zarządzanie administratorami parafii (Filament 4)
 */
class AdminsRelationManager extends RelationManager
{
    protected static string $relationship = 'admins';

    protected static ?string $title = 'Administratorzy';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nazwa użytkownika')
                    ->disabled(),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=f59e0b&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Imię i nazwisko')
                    ->placeholder('Nie podano')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Przypisano')
                    ->dateTime('d.m.Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->label('Przypisz administratora')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function () {
                        return User::whereIn('role', [1, 2]);
                    })
                    ->recordTitle(fn (User $record) => "{$record->name} ({$record->email})")
                    ->modalHeading('Przypisz administratora do parafii')
                    ->modalSubmitActionLabel('Przypisz'),
            ])
            ->recordActions([
                Actions\DetachAction::make()
                    ->label('Odpisz')
                    ->modalHeading('Odpisz administratora')
                    ->modalDescription('Czy na pewno chcesz odpisać tego administratora od parafii?'),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DetachBulkAction::make()
                        ->label('Odpisz zaznaczonych'),
                ]),
            ])
            ->emptyStateHeading('Brak administratorów')
            ->emptyStateDescription('Przypisz administratorów do tej parafii.')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
