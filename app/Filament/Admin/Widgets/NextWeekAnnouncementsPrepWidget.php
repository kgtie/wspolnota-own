<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Models\AnnouncementSet;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NextWeekAnnouncementsPrepWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Przygotowanie kolejnego tygodnia';

    protected ?string $description = 'Przypomina o przygotowaniu i publikacji ogloszen na przyszly tydzien.';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return [];
        }

        $nextWeekStart = now()->startOfWeek(Carbon::SUNDAY)->addWeek();
        $nextWeekEnd = $nextWeekStart->copy()->endOfWeek(Carbon::SATURDAY);

        $baseQuery = AnnouncementSet::query()
            ->where('parish_id', $tenant->getKey())
            ->whereDate('effective_from', '<=', $nextWeekEnd->toDateString())
            ->where(function ($query) use ($nextWeekStart) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $nextWeekStart->toDateString());
            });

        $publishedSet = (clone $baseQuery)
            ->where('status', 'published')
            ->withCount([
                'items as active_items_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('effective_from')
            ->first();

        $draftSet = (clone $baseQuery)
            ->where('status', 'draft')
            ->withCount([
                'items as active_items_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('effective_from')
            ->first();

        $weekLabel = $nextWeekStart->format('d.m').' - '.$nextWeekEnd->format('d.m.Y');
        $url = AnnouncementSetResource::getUrl('index');

        if ($publishedSet) {
            $count = (int) ($publishedSet->active_items_count ?? 0);

            return [
                Stat::make('Ogloszenia na przyszly tydzien', 'Gotowe i opublikowane')
                    ->description("{$weekLabel} | {$publishedSet->title} | Aktywne: {$count}")
                    ->descriptionIcon('heroicon-o-check-badge')
                    ->color('success')
                    ->url($url),
            ];
        }

        if ($draftSet) {
            $count = (int) ($draftSet->active_items_count ?? 0);

            return [
                Stat::make('Ogloszenia na przyszly tydzien', 'Szkic w przygotowaniu')
                    ->description("{$weekLabel} | Aktywne pozycje: {$count}. Dokoncz i opublikuj zestaw.")
                    ->descriptionIcon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->url($url),
            ];
        }

        return [
            Stat::make('Ogloszenia na przyszly tydzien', 'Brak przygotowanego zestawu')
                ->description("{$weekLabel} | Utworz zestaw, aby parafia miala gotowe ogloszenia.")
                ->descriptionIcon('heroicon-o-bell-alert')
                ->color('danger')
                ->url($url),
        ];
    }
}
