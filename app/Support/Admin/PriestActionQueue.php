<?php

namespace App\Support\Admin;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Filament\Admin\Resources\Masses\MassResource;
use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\Parish;
use Carbon\Carbon;

class PriestActionQueue
{
    /**
     * @return array<string, mixed>
     */
    public function currentAnnouncements(Parish $tenant): array
    {
        $today = now()->startOfDay();

        return $this->resolveAnnouncementWindowStatus(
            tenant: $tenant,
            start: $today,
            end: $today->copy()->endOfDay(),
            contextLabel: 'na dzisiaj',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function nextWeekAnnouncements(Parish $tenant): array
    {
        $start = now()->startOfWeek(Carbon::SUNDAY)->addWeek();

        return $this->resolveAnnouncementWindowStatus(
            tenant: $tenant,
            start: $start,
            end: $start->copy()->endOfWeek(Carbon::SATURDAY),
            contextLabel: 'na przyszly tydzien',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function massCoverage(Parish $tenant, int $days = 14): array
    {
        $days = max($days, 1);
        $start = now()->startOfDay();
        $end = $start->copy()->addDays($days - 1)->endOfDay();

        $coveredDates = Mass::query()
            ->where('parish_id', $tenant->getKey())
            ->where('status', '!=', 'cancelled')
            ->whereBetween('celebration_at', [$start, $end])
            ->get(['celebration_at'])
            ->map(fn (Mass $mass): string => $mass->celebration_at->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        $coveredMap = array_fill_keys($coveredDates, true);
        $missingDays = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->copy()->addDays($offset);
            $key = $day->format('Y-m-d');

            if (isset($coveredMap[$key])) {
                continue;
            }

            $missingDays[] = [
                'date' => $day->format('d.m.Y'),
                'short_date' => $day->format('d.m'),
                'label' => $day->translatedFormat('l'),
            ];
        }

        $missingCount = count($missingDays);
        $coveredDays = $days - $missingCount;
        $tone = match (true) {
            $missingCount >= 5 => 'danger',
            $missingCount > 0 => 'warning',
            default => 'success',
        };
        $periodLabel = $start->format('d.m').' - '.$end->format('d.m.Y');

        return [
            'tone' => $tone,
            'state_label' => match ($tone) {
                'danger' => 'pilne',
                'warning' => 'do uzupelnienia',
                default => 'gotowe',
            },
            'title' => $missingCount > 0
                ? 'Brakuje mszy do wpisania na najblizsze 14 dni'
                : 'Plan mszalny na najblizsze 14 dni jest gotowy',
            'summary' => $missingCount > 0
                ? 'To jeden z najwazniejszych sygnalow operacyjnych. Proboszcz od razu widzi, ze kalendarz liturgiczny wymaga dopiecia.'
                : 'Kalendarz jest zapelniony, wiec mozna przejsc do innych obszarow bez obawy o dziury w planie celebracji.',
            'meta' => "Pokrycie: {$coveredDays}/{$days} dni | {$periodLabel}",
            'days_total' => $days,
            'covered_days' => $coveredDays,
            'missing_days_count' => $missingCount,
            'missing_days' => $missingDays,
            'summary_inline' => $missingCount > 0
                ? implode(' | ', array_map(
                    fn (array $day): string => $day['label'].', '.$day['short_date'],
                    array_slice($missingDays, 0, 5),
                )).($missingCount > 5 ? ' | ...' : '')
                : 'Pelne pokrycie na 14 dni',
            'url' => $missingCount > 0 ? MassResource::getUrl('create') : MassResource::getUrl('index'),
            'secondary_url' => MassResource::getUrl('index'),
            'action_label' => $missingCount > 0 ? 'Uzupelnij kalendarz' : 'Przejdz do kalendarza',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveAnnouncementWindowStatus(
        Parish $tenant,
        Carbon $start,
        Carbon $end,
        string $contextLabel,
    ): array {
        $query = AnnouncementSet::query()
            ->where('parish_id', $tenant->getKey())
            ->whereDate('effective_from', '<=', $end->toDateString())
            ->where(function ($builder) use ($start) {
                $builder->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $start->toDateString());
            });

        $publishedSet = (clone $query)
            ->where('status', 'published')
            ->withCount([
                'items as active_items_count' => fn ($builder) => $builder->where('is_active', true),
            ])
            ->orderBy('effective_from')
            ->first();

        $draftSet = (clone $query)
            ->where('status', 'draft')
            ->withCount([
                'items as active_items_count' => fn ($builder) => $builder->where('is_active', true),
            ])
            ->orderBy('effective_from')
            ->first();

        $windowLabel = $start->isSameDay($end)
            ? $start->format('d.m.Y')
            : $start->format('d.m').' - '.$end->format('d.m.Y');

        if ($publishedSet instanceof AnnouncementSet) {
            $itemsCount = (int) ($publishedSet->active_items_count ?? 0);
            $tone = $itemsCount > 0 ? 'success' : 'warning';

            return [
                'tone' => $tone,
                'state_label' => $itemsCount > 0 ? 'gotowe' : 'do przejrzenia',
                'title' => "Ogloszenia {$contextLabel} sa opublikowane",
                'summary' => 'Zestaw jest juz widoczny dla parafian. W razie potrzeby mozna szybko przejsc do edycji, druku lub przygotowania kolejnego tygodnia.',
                'meta' => "{$windowLabel} | {$publishedSet->title} | Aktywne pozycje: {$itemsCount}",
                'hero_value' => 'gotowe',
                'hero_description' => "{$contextLabel}: opublikowane",
                'priority_title' => "Ogloszenia {$contextLabel} sa opublikowane",
                'priority_body' => 'Zestaw jest juz widoczny dla parafian. Mozesz przejsc do edycji, druku lub kolejnego tygodnia.',
                'url' => AnnouncementSetResource::getUrl('view', ['record' => $publishedSet]),
                'secondary_url' => AnnouncementSetResource::getUrl('index'),
                'action_label' => 'Podglad zestawu',
            ];
        }

        if ($draftSet instanceof AnnouncementSet) {
            $itemsCount = (int) ($draftSet->active_items_count ?? 0);

            return [
                'tone' => 'warning',
                'state_label' => 'do publikacji',
                'title' => "Ogloszenia {$contextLabel} sa jeszcze szkicem",
                'summary' => 'Tresci sa juz w przygotowaniu, ale parafianie ich jeszcze nie zobacza. To powinno motywowac do domkniecia pracy i publikacji.',
                'meta' => "{$windowLabel} | Aktywne pozycje: {$itemsCount}",
                'hero_value' => 'szkic',
                'hero_description' => "{$contextLabel}: do publikacji",
                'priority_title' => "Ogloszenia {$contextLabel} sa jeszcze szkicem",
                'priority_body' => 'Tresci sa juz w przygotowaniu, ale parafianie ich jeszcze nie zobacza, dopoki zestaw nie zostanie opublikowany.',
                'url' => AnnouncementSetResource::getUrl('edit', ['record' => $draftSet]),
                'secondary_url' => AnnouncementSetResource::getUrl('index'),
                'action_label' => 'Edytuj szkic',
            ];
        }

        return [
            'tone' => 'danger',
            'state_label' => 'pilne',
            'title' => "Brakuje ogloszen {$contextLabel}",
            'summary' => 'To bezposredni sygnal, ze jest robota do zrobienia. Bez tego wierni nie maja gotowej informacji liturgicznej na czas.',
            'meta' => $windowLabel,
            'hero_value' => 'brak',
            'hero_description' => "{$contextLabel}: niegotowe",
            'priority_title' => "Brakuje ogloszen {$contextLabel}",
            'priority_body' => 'W dashboardzie to powinien byc sygnal najwyzszego priorytetu, bo wierni nie maja kompletnej informacji liturgicznej.',
            'url' => AnnouncementSetResource::getUrl('create'),
            'secondary_url' => AnnouncementSetResource::getUrl('index'),
            'action_label' => 'Utworz zestaw',
        ];
    }
}
