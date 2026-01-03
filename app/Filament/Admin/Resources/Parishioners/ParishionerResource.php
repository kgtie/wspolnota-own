<?php

namespace App\Filament\Admin\Resources\Parishioners;

use App\Filament\Admin\Resources\Parishioners\ParishionerResource\Pages;
use App\Filament\Admin\Resources\Parishioners\Tables\ParishionersTable;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ParishionerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $tenantOwnershipRelationshipName = 'homeParish';
    
    protected static ?string $tenantRelationshipName = 'parishioners';

    public static function getNavigationLabel(): string
    {
        return 'Parafianie';
    }

    public static function getModelLabel(): string
    {
        return 'Parafianin';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Parafianie';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-identification';
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('role', User::ROLE_USER)
            ->when($tenant, fn (Builder $query) => $query->where('home_parish_id', $tenant->id));
    }

    public static function table(Table $table): Table
    {
        return ParishionersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParishioners::route('/'),
        ];
    }
}
