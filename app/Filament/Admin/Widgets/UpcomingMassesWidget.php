<?php

namespace App\Filament\Admin\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;

/**
 * UpcomingMassesWidget - Nadchodzące msze święte
 * 
 * Widget wyświetlający najbliższe zaplanowane msze święte w parafii.
 * Placeholder - pełna implementacja po utworzeniu modelu Mass.
 */
class UpcomingMassesWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    protected string $view = 'filament.admin.widgets.upcoming-masses-widget';

    public function getParish()
    {
        return Filament::getTenant();
    }

    public function getUpcomingMasses(): array
    {
        $parish = $this->getParish();
        if (!$parish) return [];
        
        return \App\Models\Mass::where('parish_id', $parish->id)
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->toArray();

        return [];
    }

    public function hasNoMasses(): bool
    {
        return count($this->getUpcomingMasses()) === 0;
    }
}
