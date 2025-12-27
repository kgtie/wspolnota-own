<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Dashboard - Strona główna panelu administratora parafii
 * 
 * Wyświetla statystyki parafii, nadchodzące msze, ostatnich parafian
 * oraz przypomnienia o zadaniach do wykonania.
 */
class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Panel Parafii';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    /**
     * Widgety wyświetlane na dashboardzie
     */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\ParishStatsWidget::class,
            \App\Filament\Admin\Widgets\UpcomingMassesWidget::class,
            \App\Filament\Admin\Widgets\RecentParishionersWidget::class,
        ];
    }
}