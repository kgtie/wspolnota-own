<?php

namespace App\Filament\Admin\Resources\Masses;

use App\Filament\Admin\Resources\Masses\MassResource\Pages;
use App\Filament\Admin\Resources\Masses\MassResource\RelationManagers\AttendeesRelationManager;
use App\Filament\Admin\Resources\Masses\Schemas\MassForm;
use App\Filament\Admin\Resources\Masses\Tables\MassesTable;
use App\Models\Mass;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MassResource extends Resource
{
    protected static ?string $model = Mass::class;

    public static function getNavigationLabel(): string
    {
        return 'Msze święte';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Liturgia';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function form(Schema $schema): Schema
    {
        return MassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MassesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AttendeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMasses::route('/'),
            'create' => Pages\CreateMass::route('/create'),
            'edit'   => Pages\EditMass::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('attendees');
    }
}
