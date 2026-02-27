<?php

namespace App\Filament\SuperAdmin\Resources\OfficeConversations;

use App\Filament\SuperAdmin\Resources\OfficeConversations\Pages\CreateOfficeConversation;
use App\Filament\SuperAdmin\Resources\OfficeConversations\Pages\EditOfficeConversation;
use App\Filament\SuperAdmin\Resources\OfficeConversations\Pages\ListOfficeConversations;
use App\Filament\SuperAdmin\Resources\OfficeConversations\Pages\ViewOfficeConversation;
use App\Filament\SuperAdmin\Resources\OfficeConversations\RelationManagers\MessagesRelationManager;
use App\Filament\SuperAdmin\Resources\OfficeConversations\Schemas\OfficeConversationForm;
use App\Filament\SuperAdmin\Resources\OfficeConversations\Schemas\OfficeConversationInfolist;
use App\Filament\SuperAdmin\Resources\OfficeConversations\Tables\OfficeConversationsTable;
use App\Models\OfficeConversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class OfficeConversationResource extends Resource
{
    protected static ?string $model = OfficeConversation::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $modelLabel = 'konwersacja online';

    protected static ?string $pluralModelLabel = 'konwersacje online';

    protected static ?string $navigationLabel = 'Konwersacje online';

    protected static string|UnitEnum|null $navigationGroup = 'Komunikacja';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return OfficeConversationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OfficeConversationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OfficeConversationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfficeConversations::route('/'),
            'create' => CreateOfficeConversation::route('/create'),
            'view' => ViewOfficeConversation::route('/{record}'),
            'edit' => EditOfficeConversation::route('/{record}/edit'),
        ];
    }
}
