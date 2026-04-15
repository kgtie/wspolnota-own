<?php

namespace App\Filament\Admin\Resources\NewsComments;

use App\Filament\Admin\Resources\NewsComments\Pages\EditNewsComment;
use App\Filament\Admin\Resources\NewsComments\Pages\ListNewsComments;
use App\Filament\Admin\Resources\NewsPosts\NewsPostResource as NewsPostAdminResource;
use App\Filament\Support\NewsComments\NewsCommentForm;
use App\Filament\Support\NewsComments\NewsCommentsTable;
use App\Models\NewsComment;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class NewsCommentResource extends Resource
{
    protected static ?string $model = NewsComment::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $modelLabel = 'komentarz';

    protected static ?string $pluralModelLabel = 'komentarze';

    protected static ?string $navigationLabel = 'Komentarze';

    protected static string|UnitEnum|null $navigationGroup = 'Komunikacja';

    protected static ?int $navigationSort = 41;

    public static function form(Schema $schema): Schema
    {
        return NewsCommentForm::configure($schema, isSuperAdmin: false);
    }

    public static function table(Table $table): Table
    {
        return NewsCommentsTable::configure(
            $table,
            isSuperAdmin: false,
            postResourceClass: NewsPostAdminResource::class,
            commentResourceClass: static::class,
        );
    }

    public static function getEloquentQuery(): Builder
    {
        $tenantId = Filament::getTenant()?->getKey();

        return parent::getEloquentQuery()
            ->with(['newsPost', 'user', 'parent', 'hiddenBy'])
            ->whereHas('newsPost', fn (Builder $query) => $query->where('parish_id', $tenantId));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsComments::route('/'),
            'edit' => EditNewsComment::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
