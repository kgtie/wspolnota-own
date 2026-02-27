<?php

namespace App\Filament\SuperAdmin\Resources\Parishes;

use App\Filament\SuperAdmin\Resources\Parishes\Pages\CreateParish;
use App\Filament\SuperAdmin\Resources\Parishes\Pages\EditParish;
use App\Filament\SuperAdmin\Resources\Parishes\Pages\ListParishes;
use App\Filament\SuperAdmin\Resources\Parishes\Pages\ViewParish;
use App\Filament\SuperAdmin\Resources\Parishes\Schemas\ParishForm;
use App\Filament\SuperAdmin\Resources\Parishes\Schemas\ParishInfolist;
use App\Filament\SuperAdmin\Resources\Parishes\Tables\ParishesTable;
use App\Models\Parish;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ParishResource extends Resource
{
    protected static ?string $model = Parish::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $modelLabel = 'parafia';

    protected static ?string $pluralModelLabel = 'parafie';

    protected static ?string $navigationLabel = 'Parafie';

    protected static string|UnitEnum|null $navigationGroup = 'Platforma';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return ParishForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParishInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParishesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'parishioners',
                'admins',
                'officeConversations',
                'newsPosts',
                'masses',
                'announcementSets',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParishes::route('/'),
            'create' => CreateParish::route('/create'),
            'view' => ViewParish::route('/{record}'),
            'edit' => EditParish::route('/{record}/edit'),
        ];
    }
}
