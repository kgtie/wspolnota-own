<?php

namespace App\Filament\Superadmin\Resources\Parishes\ParishResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;

class AdminsRelationManager extends RelationManager
{
    protected static string $relationship = 'admins';

    protected static ?string $title = 'Administratorzy (proboszczowie)';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Login')->searchable(),
                TextColumn::make('full_name')->label('Imię i nazwisko')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('role')->label('Rola'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('role', '>=', 1)),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->bulkActions([
                DetachBulkAction::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        // BelongsToMany attach/detach w tym przypadku nie wymaga osobnego formularza
        return $schema->components([]);
    }
}
