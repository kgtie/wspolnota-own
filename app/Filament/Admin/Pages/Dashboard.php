<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\CurrentAnnouncementsGuardWidget;
use App\Filament\Admin\Widgets\MassesStatsWidget;
use App\Filament\Admin\Widgets\MassesWeekCoverageWidget;
use App\Filament\Admin\Widgets\NextWeekAnnouncementsPrepWidget;
use App\Filament\Admin\Widgets\OfficeConversationsStatusWidget;
use App\Filament\Admin\Widgets\ParishionersStatsWidget;
use App\Filament\Admin\Widgets\UpcomingMassesTableWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Panel parafii';

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            ParishionersStatsWidget::class,
            MassesStatsWidget::class,
            CurrentAnnouncementsGuardWidget::class,
            NextWeekAnnouncementsPrepWidget::class,
            MassesWeekCoverageWidget::class,
            OfficeConversationsStatusWidget::class,
            UpcomingMassesTableWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 12,
            'xl' => 12,
        ];
    }
}
