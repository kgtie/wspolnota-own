<?php

namespace App\Filament\SuperAdmin\Resources\NewsPosts;

use App\Filament\SuperAdmin\Resources\NewsPosts\Pages\CreateNewsPost;
use App\Filament\SuperAdmin\Resources\NewsPosts\Pages\EditNewsPost;
use App\Filament\SuperAdmin\Resources\NewsPosts\Pages\ListNewsPosts;
use App\Filament\SuperAdmin\Resources\NewsPosts\Pages\ViewNewsPost;
use App\Filament\SuperAdmin\Resources\NewsPosts\Schemas\NewsPostForm;
use App\Filament\SuperAdmin\Resources\NewsPosts\Schemas\NewsPostInfolist;
use App\Filament\SuperAdmin\Resources\NewsPosts\Tables\NewsPostsTable;
use App\Models\NewsPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class NewsPostResource extends Resource
{
    protected static ?string $model = NewsPost::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $modelLabel = 'aktualnosc';

    protected static ?string $pluralModelLabel = 'aktualnosci';

    protected static ?string $navigationLabel = 'Aktualnosci';

    protected static string|UnitEnum|null $navigationGroup = 'Tresci i liturgia';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return NewsPostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NewsPostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NewsPostsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parish', 'createdBy', 'updatedBy', 'media']);
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
            'index' => ListNewsPosts::route('/'),
            'create' => CreateNewsPost::route('/create'),
            'view' => ViewNewsPost::route('/{record}'),
            'edit' => EditNewsPost::route('/{record}/edit'),
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
        $openCount = static::getEloquentQuery()
            ->whereIn('status', ['draft', 'scheduled'])
            ->count();

        return $openCount > 0 ? (string) $openCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getEloquentQuery()->where('status', 'scheduled')->exists()
            ? 'warning'
            : 'success';
    }
}
