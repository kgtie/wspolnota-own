<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * ParishStatsWidget - Statystyki parafii (Filament 4)
 * 
 * Wyświetla podstawowe statystyki dla aktualnej parafii:
 * - Liczba parafian
 * - Zweryfikowani parafianie
 * - Oczekujący na weryfikację
 * - Nadchodzące msze w tym tygodniu
 */
class ParishStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $parish = Filament::getTenant();

        if (!$parish) {
            return [];
        }

        // Parafianie - użytkownicy z home_parish_id = aktualna parafia
        $totalParishioners = User::where('home_parish_id', $parish->id)->count();
        $verifiedParishioners = User::where('home_parish_id', $parish->id)
            ->where('is_user_verified', true)
            ->count();
        $pendingVerification = User::where('home_parish_id', $parish->id)
            ->where('is_user_verified', false)
            ->count();

        // Nadchodzące msze w tym tygodniu (placeholder - dostosuj gdy będzie model Mass)
        $upcomingMasses = 0;
        // if (class_exists(\App\Models\Mass::class)) {
        //     $upcomingMasses = \App\Models\Mass::where('parish_id', $parish->id)
        //         ->whereBetween('scheduled_at', [now(), now()->endOfWeek()])
        //         ->count();
        // }

        return [
            Stat::make('Parafianie', $totalParishioners)
                ->description('Zarejestrowani w parafii')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([
                    $totalParishioners > 0 ? $totalParishioners - 2 : 0,
                    $totalParishioners > 0 ? $totalParishioners - 1 : 0,
                    $totalParishioners,
                ]),

            Stat::make('Zweryfikowani', $verifiedParishioners)
                ->description('Potwierdzeni przez proboszcza')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->chart([
                    max(0, $verifiedParishioners - 3),
                    max(0, $verifiedParishioners - 2),
                    max(0, $verifiedParishioners - 1),
                    $verifiedParishioners,
                ]),

            Stat::make('Oczekujący', $pendingVerification)
                ->description('Do weryfikacji')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingVerification > 0 ? 'warning' : 'gray')
                ->chart([
                    $pendingVerification,
                    max(0, $pendingVerification - 1),
                    max(0, $pendingVerification - 2),
                ]),

            Stat::make('Msze w tym tygodniu', $upcomingMasses)
                ->description('Zaplanowane')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}
