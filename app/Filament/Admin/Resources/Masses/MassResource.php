<?php

namespace App\Filament\Admin\Resources\Masses;

use App\Filament\Admin\Resources\Masses\Pages\CreateMass;
use App\Filament\Admin\Resources\Masses\Pages\EditMass;
use App\Filament\Admin\Resources\Masses\Pages\ListMasses;
use App\Filament\Admin\Resources\Masses\Pages\ViewMass;
use App\Filament\Admin\Resources\Masses\RelationManagers\ParticipantsRelationManager;
use App\Filament\Admin\Resources\Masses\Schemas\MassForm;
use App\Filament\Admin\Resources\Masses\Schemas\MassInfolist;
use App\Filament\Admin\Resources\Masses\Tables\MassesTable;
use App\Models\Mass;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MassResource extends Resource
{
    protected static ?string $model = Mass::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $tenantOwnershipRelationshipName = 'parish';

    protected static ?string $modelLabel = 'msza święta';

    protected static ?string $pluralModelLabel = 'msze święte';

    protected static ?string $navigationLabel = 'Msze i intencje';

    protected static string|UnitEnum|null $navigationGroup = 'Liturgia';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return MassForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MassInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MassesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['createdBy', 'updatedBy'])
            ->withCount('participants');
    }

    public static function getRelations(): array
    {
        return [
            ParticipantsRelationManager::class,
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMasses::route('/'),
            'create' => CreateMass::route('/create'),
            'view' => ViewMass::route('/{record}'),
            'edit' => EditMass::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (! $tenantId) {
            return null;
        }

        $upcomingCount = Mass::query()
            ->where('parish_id', $tenantId)
            ->where('status', 'scheduled')
            ->where('celebration_at', '>=', now()->startOfDay())
            ->count();

        return $upcomingCount > 0 ? (string) $upcomingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (! $tenantId) {
            return null;
        }

        return Mass::query()
            ->where('parish_id', $tenantId)
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [now(), now()->addDays(7)])
            ->exists()
            ? 'info'
            : 'success';
    }
}
