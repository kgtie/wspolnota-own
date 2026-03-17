<?php

namespace App\Filament\SuperAdmin\Resources\AnnouncementSets;

use App\Filament\SuperAdmin\Resources\AnnouncementSets\Pages\CreateAnnouncementSet;
use App\Filament\SuperAdmin\Resources\AnnouncementSets\Pages\EditAnnouncementSet;
use App\Filament\SuperAdmin\Resources\AnnouncementSets\Pages\ListAnnouncementSets;
use App\Filament\SuperAdmin\Resources\AnnouncementSets\Pages\ViewAnnouncementSet;
use App\Filament\SuperAdmin\Resources\AnnouncementSets\RelationManagers\ItemsRelationManager;
use App\Filament\SuperAdmin\Resources\AnnouncementSets\Schemas\AnnouncementSetForm;
use App\Filament\SuperAdmin\Resources\AnnouncementSets\Schemas\AnnouncementSetInfolist;
use App\Filament\SuperAdmin\Resources\AnnouncementSets\Tables\AnnouncementSetsTable;
use App\Models\AnnouncementSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AnnouncementSetResource extends Resource
{
    protected static ?string $model = AnnouncementSet::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $modelLabel = 'zestaw ogloszen';

    protected static ?string $pluralModelLabel = 'zestawy ogloszen';

    protected static ?string $navigationLabel = 'Ogloszenia mszalne';

    protected static string|UnitEnum|null $navigationGroup = 'Tresci i liturgia';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return AnnouncementSetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AnnouncementSetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementSetsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parish', 'createdBy', 'updatedBy'])
            ->withCount([
                'items',
                'items as important_items_count' => fn (Builder $query): Builder => $query->where('is_important', true),
                'items as active_items_count' => fn (Builder $query): Builder => $query->where('is_active', true),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncementSets::route('/'),
            'create' => CreateAnnouncementSet::route('/create'),
            'view' => ViewAnnouncementSet::route('/{record}'),
            'edit' => EditAnnouncementSet::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingDispatchCount = static::getEloquentQuery()
            ->where('status', 'published')
            ->whereNull('push_notification_sent_at')
            ->count();

        return $pendingDispatchCount > 0 ? (string) $pendingDispatchCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() !== null
            ? 'warning'
            : 'success';
    }
}
