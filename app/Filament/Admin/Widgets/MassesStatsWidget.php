<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Masses\MassResource;
use App\Models\Mass;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class MassesStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Msze i intencje';

    protected ?string $description = 'Najwazniejsze liczby liturgiczne dla aktualnej parafii.';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return [];
        }

        $baseQuery = Mass::query()->where('parish_id', $tenant->getKey());

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $nextWeekEnd = now()->addDays(7)->endOfDay();
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        $todayCount = (clone $baseQuery)
            ->whereBetween('celebration_at', [$todayStart, $todayEnd])
            ->count();
        $todayScheduled = (clone $baseQuery)
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [$todayStart, $todayEnd])
            ->count();
        $todayCompleted = (clone $baseQuery)
            ->where('status', 'completed')
            ->whereBetween('celebration_at', [$todayStart, $todayEnd])
            ->count();

        $upcomingWeekCount = (clone $baseQuery)
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [now(), $nextWeekEnd])
            ->count();
        $tomorrowCount = (clone $baseQuery)
            ->whereBetween('celebration_at', [now()->addDay()->startOfDay(), now()->addDay()->endOfDay()])
            ->count();

        $outstandingStipendiumsQuery = (clone $baseQuery)
            ->whereNotNull('stipendium_amount')
            ->whereNull('stipendium_paid_at')
            ->where('status', '!=', 'cancelled');
        $outstandingStipendiumsCount = (clone $outstandingStipendiumsQuery)->count();
        $outstandingStipendiumsValue = (float) ((clone $outstandingStipendiumsQuery)->sum('stipendium_amount') ?? 0);

        $completedLast30DaysCount = (clone $baseQuery)
            ->where('status', 'completed')
            ->whereBetween('celebration_at', [$thirtyDaysAgo, now()])
            ->count();
        $paidStipendiumsLast30Days = (float) ((clone $baseQuery)
            ->whereNotNull('stipendium_paid_at')
            ->whereBetween('stipendium_paid_at', [$thirtyDaysAgo, now()])
            ->sum('stipendium_amount') ?? 0);
        $massesUrl = MassResource::getUrl('index');

        return [
            Stat::make('Msze dzisiaj', number_format($todayCount, 0, ',', ' '))
                ->description("Zaplanowane: {$todayScheduled} | Odprawione: {$todayCompleted}")
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info')
                ->chart($this->buildPastDailyTrend(
                    (clone $baseQuery),
                    'celebration_at',
                    7,
                ))
                ->url($massesUrl),

            Stat::make('Nadchodzace 7 dni', number_format($upcomingWeekCount, 0, ',', ' '))
                ->description("Jutro: {$tomorrowCount}")
                ->descriptionIcon('heroicon-o-clock')
                ->color('info')
                ->chart($this->buildFutureDailyTrend(
                    (clone $baseQuery)->where('status', 'scheduled'),
                    'celebration_at',
                    7,
                ))
                ->url($massesUrl),

            Stat::make('Nierozliczone stypendia', number_format($outstandingStipendiumsCount, 0, ',', ' '))
                ->description('Kwota: '.$this->formatCurrency($outstandingStipendiumsValue))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color(match (true) {
                    $outstandingStipendiumsCount >= 20 => 'danger',
                    $outstandingStipendiumsCount > 0 => 'warning',
                    default => 'success',
                })
                ->chart($this->buildPastDailyTrend(
                    (clone $outstandingStipendiumsQuery),
                    'celebration_at',
                    14,
                ))
                ->url($massesUrl),

            Stat::make('Odprawione (30 dni)', number_format($completedLast30DaysCount, 0, ',', ' '))
                ->description('Przyjete stypendia: '.$this->formatCurrency($paidStipendiumsLast30Days))
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success')
                ->chart($this->buildPastDailyTrend(
                    (clone $baseQuery)->where('status', 'completed'),
                    'celebration_at',
                    30,
                ))
                ->url($massesUrl),
        ];
    }

    protected function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }

    /**
     * @return array<float>
     */
    protected function buildPastDailyTrend(Builder $query, string $column, int $days): array
    {
        $trend = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $start = now()->subDays($offset)->startOfDay();
            $end = (clone $start)->endOfDay();

            $trend[] = (float) (clone $query)->whereBetween($column, [$start, $end])->count();
        }

        return $trend;
    }

    /**
     * @return array<float>
     */
    protected function buildFutureDailyTrend(Builder $query, string $column, int $days): array
    {
        $trend = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $start = now()->addDays($offset)->startOfDay();
            $end = (clone $start)->endOfDay();

            $trend[] = (float) (clone $query)->whereBetween($column, [$start, $end])->count();
        }

        return $trend;
    }

    protected function formatCurrency(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' PLN';
    }
}
