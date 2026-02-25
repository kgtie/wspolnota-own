<?php

namespace App\Filament\SuperAdmin\Resources\Settings;

use App\Filament\SuperAdmin\Resources\Settings\Pages\CreateSetting;
use App\Filament\SuperAdmin\Resources\Settings\Pages\EditSetting;
use App\Filament\SuperAdmin\Resources\Settings\Pages\ListSettings;
use App\Filament\SuperAdmin\Resources\Settings\Pages\ViewSetting;
use App\Filament\SuperAdmin\Resources\Settings\Schemas\SettingForm;
use App\Filament\SuperAdmin\Resources\Settings\Schemas\SettingInfolist;
use App\Filament\SuperAdmin\Resources\Settings\Tables\SettingsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelSettings\Models\SettingsProperty;
use UnitEnum;

class SettingResource extends Resource
{
    protected static ?string $model = SettingsProperty::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $modelLabel = 'ustawienie';

    protected static ?string $pluralModelLabel = 'ustawienia';

    protected static ?string $navigationLabel = 'Ustawienia aplikacji';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return SettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('group')
            ->orderBy('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'view' => ViewSetting::route('/{record}'),
            'edit' => EditSetting::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function normalizePayload(?string $payload): string
    {
        $payload = trim((string) $payload);

        if ($payload === '') {
            return 'null';
        }

        $decoded = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $payload;
        }

        $normalized = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($normalized) ? $normalized : $payload;
    }

    public static function prettyPayload(?string $payload): string
    {
        $payload = trim((string) $payload);

        if ($payload === '') {
            return '';
        }

        $decoded = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $payload;
        }

        $pretty = json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return is_string($pretty) ? $pretty : $payload;
    }

    public static function payloadPreview(?string $payload, int $limit = 100): string
    {
        return (string) str(static::normalizePayload($payload))
            ->replace("\n", ' ')
            ->limit($limit);
    }
}
