<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Models\AnnouncementSet;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CurrentAnnouncementsGuardWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Kontrola ogloszen biezacych';

    protected ?string $description = 'Pilnuje, czy na dzisiaj istnieje opublikowany zestaw ogloszen.';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return [];
        }

        $today = now()->toDateString();

        $currentPublished = AnnouncementSet::query()
            ->where('parish_id', $tenant->getKey())
            ->where('status', 'published')
            ->currentForDate($today)
            ->withCount([
                'items as active_items_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderByDesc('effective_from')
            ->first();

        $currentDraft = AnnouncementSet::query()
            ->where('parish_id', $tenant->getKey())
            ->where('status', 'draft')
            ->currentForDate($today)
            ->first();

        $url = AnnouncementSetResource::getUrl('index');

        if ($currentPublished) {
            $itemsCount = (int) ($currentPublished->active_items_count ?? 0);
            $itemsLabel = "Aktywne ogloszenia: {$itemsCount}";

            return [
                Stat::make('Ogloszenia na dzisiaj', 'Opublikowane')
                    ->description($currentPublished->title.' | '.$itemsLabel)
                    ->descriptionIcon('heroicon-o-check-circle')
                    ->color($itemsCount > 0 ? 'success' : 'warning')
                    ->url($url),
            ];
        }

        if ($currentDraft) {
            return [
                Stat::make('Ogloszenia na dzisiaj', 'Szkic wymaga publikacji')
                    ->description('Istnieje szkic aktualnego zestawu. Opublikuj go, aby parafianie widzieli ogloszenia.')
                    ->descriptionIcon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->url($url),
            ];
        }

        return [
            Stat::make('Ogloszenia na dzisiaj', 'Brak aktualnego zestawu')
                ->description('Utworz i opublikuj zestaw ogloszen obowiazujacy na dzisiaj.')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->url($url),
        ];
    }
}
