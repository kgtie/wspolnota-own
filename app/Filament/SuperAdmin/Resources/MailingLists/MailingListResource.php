<?php

namespace App\Filament\SuperAdmin\Resources\MailingLists;

use App\Filament\SuperAdmin\Resources\MailingLists\Pages\CreateMailingList;
use App\Filament\SuperAdmin\Resources\MailingLists\Pages\EditMailingList;
use App\Filament\SuperAdmin\Resources\MailingLists\Pages\ListMailingLists;
use App\Filament\SuperAdmin\Resources\MailingLists\Pages\ViewMailingList;
use App\Filament\SuperAdmin\Resources\MailingLists\Schemas\MailingListForm;
use App\Filament\SuperAdmin\Resources\MailingLists\Schemas\MailingListInfolist;
use App\Filament\SuperAdmin\Resources\MailingLists\Tables\MailingListsTable;
use App\Models\MailingList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MailingListResource extends Resource
{
    protected static ?string $model = MailingList::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $modelLabel = 'lista mailingowa';

    protected static ?string $pluralModelLabel = 'listy mailingowe';

    protected static ?string $navigationLabel = 'Listy mailingowe';

    protected static string|UnitEnum|null $navigationGroup = 'Komunikacja';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return MailingListForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MailingListInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailingListsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('mails');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailingLists::route('/'),
            'create' => CreateMailingList::route('/create'),
            'view' => ViewMailingList::route('/{record}'),
            'edit' => EditMailingList::route('/{record}/edit'),
        ];
    }
}
