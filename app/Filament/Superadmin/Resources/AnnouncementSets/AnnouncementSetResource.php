<?php

namespace App\Filament\Superadmin\Resources\AnnouncementSets;

use App\Filament\Superadmin\Resources\AnnouncementSets\Pages\CreateAnnouncementSet;
use App\Filament\Superadmin\Resources\AnnouncementSets\Pages\EditAnnouncementSet;
use App\Filament\Superadmin\Resources\AnnouncementSets\Pages\ListAnnouncementSets;
use App\Filament\Superadmin\Resources\AnnouncementSets\Schemas\AnnouncementSetForm;
use App\Filament\Superadmin\Resources\AnnouncementSets\Tables\AnnouncementSetsTable;
use App\Models\AnnouncementSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AnnouncementSetResource extends Resource
{
    protected static ?string $model = AnnouncementSet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Ogłoszenia';

    public static function form(Schema $schema): Schema
    {
        return AnnouncementSetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementSetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncementSets::route('/'),
            'create' => CreateAnnouncementSet::route('/create'),
            'edit' => EditAnnouncementSet::route('/{record}/edit'),
        ];
    }
}
