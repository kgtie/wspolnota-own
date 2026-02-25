<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs;

use App\Filament\SuperAdmin\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\SuperAdmin\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Filament\SuperAdmin\Resources\ActivityLogs\Schemas\ActivityLogInfolist;
use App\Filament\SuperAdmin\Resources\ActivityLogs\Tables\ActivityLogsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'wpis logu';

    protected static ?string $pluralModelLabel = 'wpisy logow';

    protected static ?string $navigationLabel = 'Logi aktywnosci';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 20;

    public static function infolist(Schema $schema): Schema
    {
        return ActivityLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['causer', 'subject'])
            ->orderByDesc('created_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getEloquentQuery()
            ->where('created_at', '>=', now()->subHour())
            ->exists()
            ? 'warning'
            : 'success';
    }

    public static function relationLabel(?Model $model, ?string $type, ?int $id): string
    {
        if ($model instanceof Model) {
            $label = $model->getAttribute('full_name')
                ?? $model->getAttribute('name')
                ?? $model->getAttribute('title')
                ?? null;

            $typeLabel = class_basename($type ?: $model::class);

            if (filled($label)) {
                return "{$typeLabel}: {$label} (#{$model->getKey()})";
            }

            return "{$typeLabel} #{$model->getKey()}";
        }

        if (blank($type) && blank($id)) {
            return 'Brak';
        }

        $typeLabel = class_basename((string) $type);

        return trim("{$typeLabel} #{$id}");
    }

    public static function propertiesPretty(Activity $record): string
    {
        $properties = $record->properties;

        if ($properties instanceof Collection) {
            $properties = $properties->toArray();
        }

        if ($properties === null) {
            return '{}';
        }

        $encoded = json_encode(
            $properties,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return is_string($encoded) ? $encoded : '{}';
    }

    public static function propertiesPreview(Activity $record, int $limit = 120): string
    {
        return (string) str(static::propertiesPretty($record))
            ->replace("\n", ' ')
            ->limit($limit);
    }

    public static function changesPretty(Activity $record): string
    {
        $changes = $record->changes()->toArray();

        if ($changes === []) {
            return '{}';
        }

        $encoded = json_encode(
            $changes,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return is_string($encoded) ? $encoded : '{}';
    }
}
