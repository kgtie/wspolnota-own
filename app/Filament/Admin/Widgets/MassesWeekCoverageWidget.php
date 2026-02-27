<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Mass;
use App\Filament\Admin\Resources\Masses\MassResource;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class MassesWeekCoverageWidget extends Widget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    protected string $view = 'filament.admin.widgets.masses-week-coverage-widget';

    protected function getViewData(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return [
                'isComplete' => false,
                'coveredDays' => 0,
                'daysTotal' => 7,
                'missingDays' => [],
                'severity' => 'warning',
                'massesIndexUrl' => null,
                'massesCreateUrl' => null,
            ];
        }

        $start = now()->startOfDay();
        $end = now()->addDays(6)->endOfDay();

        $coveredDateKeys = Mass::query()
            ->where('parish_id', $tenant->getKey())
            ->where('status', '!=', 'cancelled')
            ->whereBetween('celebration_at', [$start, $end])
            ->get(['celebration_at'])
            ->map(fn (Mass $mass): string => $mass->celebration_at->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        $coveredMap = array_fill_keys($coveredDateKeys, true);
        $missingDays = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day = $start->copy()->addDays($offset);
            $key = $day->format('Y-m-d');

            if (isset($coveredMap[$key])) {
                continue;
            }

            $missingDays[] = [
                'date' => $day->format('d.m.Y'),
                'label' => $day->translatedFormat('l'),
            ];
        }

        $coveredDays = 7 - count($missingDays);
        $severity = count($missingDays) >= 3 ? 'danger' : 'warning';

        return [
            'isComplete' => count($missingDays) === 0,
            'coveredDays' => $coveredDays,
            'daysTotal' => 7,
            'missingDays' => $missingDays,
            'severity' => $severity,
            'massesIndexUrl' => MassResource::getUrl('index'),
            'massesCreateUrl' => MassResource::getUrl('create'),
        ];
    }
}
