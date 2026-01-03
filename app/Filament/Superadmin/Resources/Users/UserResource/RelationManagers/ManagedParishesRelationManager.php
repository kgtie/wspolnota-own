<?php

namespace App\Filament\Superadmin\Resources\Users\UserResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManagedParishesRelationManager extends RelationManager
{
    protected static string $relationship = 'parishes';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return 'Parafie zarządzane';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Parafia')->searchable(),
                TextColumn::make('slug')->label('Slug')->toggleable(),
                TextColumn::make('city')->label('Miasto')->toggleable(),
            ])
            ->headerActions([
                AttachAction::make(),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        // pokazuj tylko dla admin/superadmin (bo user role=0 nie "zarządza" parafiami)
        return (int) $ownerRecord->role >= 1;
    }
}
