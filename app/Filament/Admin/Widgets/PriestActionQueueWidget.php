<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Parish;
use App\Support\Admin\PriestActionQueue;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class PriestActionQueueWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.priest-action-queue-widget';

    protected ?string $pollingInterval = '120s';

    protected function getViewData(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Parish) {
            return [
                'cards' => [],
            ];
        }

        $queue = app(PriestActionQueue::class);

        return [
            'cards' => [
                $queue->currentAnnouncements($tenant),
                $queue->nextWeekAnnouncements($tenant),
                $queue->massCoverage($tenant, 14),
            ],
        ];
    }
}
