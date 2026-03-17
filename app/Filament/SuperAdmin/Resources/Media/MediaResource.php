<?php

namespace App\Filament\SuperAdmin\Resources\Media;

use App\Filament\SuperAdmin\Resources\Media\Pages\CreateMedia;
use App\Filament\SuperAdmin\Resources\Media\Pages\EditMedia;
use App\Filament\SuperAdmin\Resources\Media\Pages\ListMedia;
use App\Filament\SuperAdmin\Resources\Media\Pages\ViewMedia;
use App\Filament\SuperAdmin\Resources\Media\Schemas\MediaForm;
use App\Filament\SuperAdmin\Resources\Media\Schemas\MediaInfolist;
use App\Filament\SuperAdmin\Resources\Media\Tables\MediaTable;
use App\Models\NewsPost;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $modelLabel = 'medium';

    protected static ?string $pluralModelLabel = 'media';

    protected static ?string $navigationLabel = 'Media';

    protected static string|UnitEnum|null $navigationGroup = 'Media i pliki';

    protected static ?int $navigationSort = 10;

    /**
     * @return array<class-string,string>
     */
    public static function getAttachableModelOptions(): array
    {
        return [
            Parish::class => 'Parafia',
            User::class => 'Uzytkownik',
            NewsPost::class => 'Aktualnosc',
            OfficeMessage::class => 'Wiadomosc kancelarii online',
        ];
    }

    public static function resolveModelTypeLabel(?string $modelType): string
    {
        if (! $modelType) {
            return 'Nieznany';
        }

        $options = static::getAttachableModelOptions();

        return $options[$modelType] ?? class_basename($modelType);
    }

    public static function form(Schema $schema): Schema
    {
        return MediaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MediaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderByDesc('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'view' => ViewMedia::route('/{record}'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
