<?php

namespace App\Filament\Superadmin\Widgets;

use App\Models\Mass;
use App\Models\Parish;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * SystemStatsWidget - Główne statystyki systemu (Filament 4)
 */
class SystemStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Parafie', Parish::count())
                ->description(Parish::where('is_active', true)->count() . ' aktywnych')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('primary')
                ->chart($this->getParishesChart()),

            Stat::make('Użytkownicy', User::count())
                ->description($this->getUserDescription())
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->chart($this->getUsersChart()),

            Stat::make('Administratorzy', User::where('role', '>=', 1)->count())
                ->description(User::where('role', 2)->count() . ' superadminów')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('warning'),

            Stat::make('Msze (7 dni)', Mass::where('start_time', '>=', now())->where('start_time', '<=', now()->addDays(7))->count())
                ->description('w najbliższym tygodniu')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),
        ];
    }

    private function getUserDescription(): string
    {
        $unverified = User::where('is_user_verified', false)
            ->whereNotNull('home_parish_id')
            ->count();

        if ($unverified > 0) {
            return $unverified . ' oczekuje na weryfikację';
        }

        return 'wszyscy zweryfikowani';
    }

    private function getParishesChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = Parish::whereDate('created_at', '<=', now()->subDays($i))->count();
        }
        return $data;
    }

    private function getUsersChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = User::whereDate('created_at', '<=', now()->subDays($i))->count();
        }
        return $data;
    }
}
