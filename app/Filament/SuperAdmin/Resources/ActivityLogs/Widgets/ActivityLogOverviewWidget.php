<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs\Widgets;

use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Activitylog\Models\Activity;

/**
 * Szybki pulpit audytowy dla activity_log. Pokazuje skale ruchu,
 * niepowodzenia i wrazliwe zdarzenia bez opuszczania listy logow.
 */
class ActivityLogOverviewWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Centrum audytu aktywnosci';

    protected ?string $description = 'Szybka diagnoza bezpieczenstwa, API i krytycznych zdarzen biznesowych.';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $baseQuery = Activity::query();
        $lastHourQuery = (clone $baseQuery)->where('created_at', '>=', now()->subHour());
        $lastDayQuery = (clone $baseQuery)->where('created_at', '>=', now()->subDay());
        $failures24h = ActivityLogResource::applyFailureScope(clone $lastDayQuery)->count();
        $security24h = ActivityLogResource::applySecurityScope(clone $lastDayQuery)->count();
        $apiAuth24h = ActivityLogResource::applyLogNamesScope(clone $lastDayQuery, ['api-auth'])->count();
        $uniqueCausers24h = (clone $lastDayQuery)
            ->whereNotNull('causer_type')
            ->whereNotNull('causer_id')
            ->get(['causer_type', 'causer_id'])
            ->unique(fn (Activity $activity): string => "{$activity->causer_type}:{$activity->causer_id}")
            ->count();

        return [
            Stat::make('Zdarzenia 1h', number_format($lastHourQuery->count(), 0, ',', ' '))
                ->description('Wszystkie nowe wpisy z ostatniej godziny')
                ->descriptionIcon('heroicon-o-bolt')
                ->color('info'),
            Stat::make('Zdarzenia 24h', number_format($lastDayQuery->count(), 0, ',', ' '))
                ->description('Pelny wolumen audytu z ostatniej doby')
                ->descriptionIcon('heroicon-o-clock')
                ->color('primary'),
            Stat::make('Niepowodzenia 24h', number_format($failures24h, 0, ',', ' '))
                ->description('Eventy z failed / invalid / blocked / denied')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($failures24h > 0 ? 'danger' : 'success'),
            Stat::make('Zdarzenia wrazliwe 24h', number_format($security24h, 0, ',', ' '))
                ->description('Logowania, hasla, weryfikacje, approval flow')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($security24h > 0 ? 'warning' : 'success'),
            Stat::make('API auth 24h', number_format($apiAuth24h, 0, ',', ' '))
                ->description('Ruch logowania, refresh i logout dla aplikacji mobilnej')
                ->descriptionIcon('heroicon-o-key')
                ->color($apiAuth24h > 0 ? 'info' : 'gray'),
            Stat::make('Unikalni sprawcy 24h', number_format((int) $uniqueCausers24h, 0, ',', ' '))
                ->description('Rozni aktorzy wykonujacy operacje w systemie')
                ->descriptionIcon('heroicon-o-users')
                ->color('gray'),
        ];
    }
}
