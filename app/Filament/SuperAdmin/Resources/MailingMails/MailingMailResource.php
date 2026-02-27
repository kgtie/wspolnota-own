<?php

namespace App\Filament\SuperAdmin\Resources\MailingMails;

use App\Filament\SuperAdmin\Resources\MailingMails\Pages\CreateMailingMail;
use App\Filament\SuperAdmin\Resources\MailingMails\Pages\EditMailingMail;
use App\Filament\SuperAdmin\Resources\MailingMails\Pages\ListMailingMails;
use App\Filament\SuperAdmin\Resources\MailingMails\Pages\ViewMailingMail;
use App\Filament\SuperAdmin\Resources\MailingMails\Schemas\MailingMailForm;
use App\Filament\SuperAdmin\Resources\MailingMails\Schemas\MailingMailInfolist;
use App\Filament\SuperAdmin\Resources\MailingMails\Tables\MailingMailsTable;
use App\Models\MailingMail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MailingMailResource extends Resource
{
    protected static ?string $model = MailingMail::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $modelLabel = 'subskrybent';

    protected static ?string $pluralModelLabel = 'subskrybenci';

    protected static ?string $navigationLabel = 'Subskrybenci mailingowi';

    protected static string|UnitEnum|null $navigationGroup = 'Komunikacja';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return MailingMailForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MailingMailInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailingMailsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('mailingList');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailingMails::route('/'),
            'create' => CreateMailingMail::route('/create'),
            'view' => ViewMailingMail::route('/{record}'),
            'edit' => EditMailingMail::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
