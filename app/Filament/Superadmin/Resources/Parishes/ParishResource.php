<?php

namespace App\Filament\Superadmin\Resources\Parishes;

use App\Filament\Superadmin\Resources\Parishes\ParishResource\Pages;
use App\Filament\Superadmin\Resources\Parishes\Schemas\ParishForm;
use App\Filament\Superadmin\Resources\Parishes\Tables\ParishesTable;
use App\Models\Parish;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ParishResource extends Resource
{
    protected static ?string $model = Parish::class;

    public static function getNavigationLabel(): string
    {
        return 'Klienci';
    }
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-office-2';
    }

    public static function form(Schema $schema): Schema
    {
        return ParishForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParishesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ParishResource\RelationManagers\AdminsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParishes::route('/'),
            'create' => Pages\CreateParish::route('/create'),
            'edit' => Pages\EditParish::route('/{record}/edit'),
        ];
    }
}
