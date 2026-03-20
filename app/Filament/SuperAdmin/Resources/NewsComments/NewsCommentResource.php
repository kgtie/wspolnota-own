<?php

namespace App\Filament\SuperAdmin\Resources\NewsComments;

use App\Filament\SuperAdmin\Resources\NewsComments\Pages\EditNewsComment;
use App\Filament\SuperAdmin\Resources\NewsComments\Pages\ListNewsComments;
use App\Filament\SuperAdmin\Resources\NewsPosts\NewsPostResource as NewsPostSuperAdminResource;
use App\Filament\Support\NewsComments\NewsCommentForm;
use App\Filament\Support\NewsComments\NewsCommentsTable;
use App\Models\NewsComment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class NewsCommentResource extends Resource
{
    protected static ?string $model = NewsComment::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $modelLabel = 'komentarz';

    protected static ?string $pluralModelLabel = 'komentarze';

    protected static ?string $navigationLabel = 'Komentarze';

    protected static string|UnitEnum|null $navigationGroup = 'Tresci i liturgia';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return NewsCommentForm::configure($schema, isSuperAdmin: true);
    }

    public static function table(Table $table): Table
    {
        return NewsCommentsTable::configure(
            $table,
            isSuperAdmin: true,
            postResourceClass: NewsPostSuperAdminResource::class,
            commentResourceClass: static::class,
        );
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['newsPost', 'user', 'parent', 'hiddenBy']);
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
