<?php

namespace App\Filament\Admin\Resources\AnnouncementSets;

use App\Filament\Admin\Resources\AnnouncementSets\Pages\CreateAnnouncementSet;
use App\Filament\Admin\Resources\AnnouncementSets\Pages\EditAnnouncementSet;
use App\Filament\Admin\Resources\AnnouncementSets\Pages\ListAnnouncementSets;
use App\Filament\Admin\Resources\AnnouncementSets\Pages\ViewAnnouncementSet;
use App\Filament\Admin\Resources\AnnouncementSets\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\AnnouncementSets\Schemas\AnnouncementSetForm;
use App\Filament\Admin\Resources\AnnouncementSets\Schemas\AnnouncementSetInfolist;
use App\Filament\Admin\Resources\AnnouncementSets\Tables\AnnouncementSetsTable;
use App\Models\AnnouncementSet;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AnnouncementSetResource extends Resource
{
    protected static ?string $model = AnnouncementSet::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $tenantOwnershipRelationshipName = 'parish';

    protected static ?string $modelLabel = 'zestaw ogloszen';

    protected static ?string $pluralModelLabel = 'zestawy ogloszen';

    protected static ?string $navigationLabel = 'Ogloszenia mszalne';

    protected static string|UnitEnum|null $navigationGroup = 'Liturgia';

    protected static ?int $navigationSort = 21;

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
            ->with(['createdBy', 'updatedBy'])
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
        $tenantId = Filament::getTenant()?->getKey();

        if (! $tenantId) {
            return null;
        }

        $draftCount = AnnouncementSet::query()
            ->where('parish_id', $tenantId)
            ->where('status', 'draft')
            ->count();

        return $draftCount > 0 ? (string) $draftCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (! $tenantId) {
            return null;
        }

        return AnnouncementSet::query()
            ->where('parish_id', $tenantId)
            ->where('status', 'draft')
            ->exists()
            ? 'warning'
            : 'success';
    }
}
