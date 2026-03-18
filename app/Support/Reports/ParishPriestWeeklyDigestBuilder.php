<?php

namespace App\Support\Reports;

use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\NewsPost;
use App\Models\OfficeConversation;
use App\Models\Parish;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ParishPriestWeeklyDigestBuilder
{
    public function build(Parish $parish, User $priest, CarbonInterface $generatedAt): array
    {
        $timezone = config('app.timezone');
        $generatedAt = $generatedAt->copy()->timezone($timezone);
        $today = $generatedAt->copy()->startOfDay();
        $massEnd = $today->copy()->addDays(9)->endOfDay();
        $nextWeekStart = $generatedAt->copy()->startOfWeek(Carbon::SUNDAY)->addWeek()->startOfDay();
        $nextWeekEnd = $nextWeekStart->copy()->endOfWeek(Carbon::SATURDAY)->endOfDay();

        $massWindowSummary = $this->buildMassWindowSummary($parish, $today, $massEnd);
        $announcementSummary = $this->buildAnnouncementSummary($parish, $nextWeekStart, $nextWeekEnd);
        $officeSummary = $this->buildOfficeSummary($parish, $priest);
        $newsSummary = $this->buildNewsSummary($parish, $generatedAt);

        return [
            'generated_at' => $generatedAt,
            'parish' => [
                'id' => $parish->getKey(),
                'name' => $parish->name,
                'city' => $parish->city,
                'email' => $parish->email,
            ],
            'recipient' => [
                'id' => $priest->getKey(),
                'name' => $priest->full_name ?: $priest->name ?: $priest->email,
                'email' => $priest->email,
            ],
            'checklist' => [
                'mass_calendar' => $massWindowSummary,
                'announcements' => $announcementSummary,
                'office' => $officeSummary,
                'news' => $newsSummary,
            ],
            'stats' => [
                'parishioners_total' => $parish->parishioners()->where('status', 'active')->count(),
                'parishioners_verified' => $parish->verifiedParishioners()->where('status', 'active')->count(),
                'admins_total' => $parish->admins()->where('users.status', 'active')->where('users.role', '>=', 1)->count(),
                'announcement_sets_total' => $parish->announcementSets()->count(),
                'announcement_sets_published' => $parish->announcementSets()->where('status', 'published')->count(),
                'masses_total' => $parish->masses()->count(),
                'masses_next_10_days' => $parish->masses()
                    ->where('status', 'scheduled')
                    ->whereBetween('celebration_at', [$today, $massEnd])
                    ->count(),
                'news_total' => $parish->newsPosts()->count(),
                'news_published_30d' => $this->countPublishedNewsSince($parish, $generatedAt->copy()->subDays(30), $generatedAt),
                'office_open_for_priest' => $officeSummary['open_count'],
                'office_unread_for_priest' => $officeSummary['unread_count'],
            ],
        ];
    }

    private function buildMassWindowSummary(Parish $parish, CarbonInterface $start, CarbonInterface $end): array
    {
        $days = collect(range(0, 9))
            ->map(fn (int $offset): CarbonInterface => $start->copy()->addDays($offset));

        $coveredDays = Mass::query()
            ->where('parish_id', $parish->getKey())
            ->where('status', 'scheduled')
            ->whereBetween('celebration_at', [$start, $end])
            ->get()
            ->groupBy(fn (Mass $mass): string => $mass->celebration_at?->timezone(config('app.timezone'))->toDateString() ?? '')
            ->keys()
            ->filter()
            ->values();

        $missingDays = $days
            ->reject(fn (CarbonInterface $day): bool => $coveredDays->contains($day->toDateString()))
            ->map(fn (CarbonInterface $day): string => $day->format('d.m.Y (D)'))
            ->values();

        if ($missingDays->isEmpty()) {
            return [
                'tone' => 'success',
                'headline' => 'Kalendarz mszalny na najblizsze 10 dni jest uzupelniony.',
                'description' => 'Kazdego dnia od dzisiaj do '.$end->format('d.m.Y').' jest zaplanowana przynajmniej jedna msza.',
                'missing_days' => [],
            ];
        }

        return [
            'tone' => 'danger',
            'headline' => 'Kalendarz mszalny wymaga uzupelnienia.',
            'description' => 'W najblizszych 10 dniach brakuje co najmniej jednej zaplanowanej mszy w niektorych dniach. Warto uzupelnic terminy jak najszybciej.',
            'missing_days' => $missingDays->all(),
        ];
    }

    private function buildAnnouncementSummary(Parish $parish, CarbonInterface $start, CarbonInterface $end): array
    {
        $baseQuery = AnnouncementSet::query()
            ->where('parish_id', $parish->getKey())
            ->whereDate('effective_from', '<=', $end->toDateString())
            ->where(function ($query) use ($start): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $start->toDateString());
            });

        $publishedSet = (clone $baseQuery)
            ->where('status', 'published')
            ->withCount([
                'items as active_items_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('effective_from')
            ->first();

        if ($publishedSet) {
            return [
                'tone' => 'success',
                'headline' => 'Ogloszenia na przyszly tydzien sa gotowe i opublikowane.',
                'description' => $publishedSet->title.' | Aktywne pozycje: '.(int) ($publishedSet->active_items_count ?? 0).'. Okres: '.$start->format('d.m').' - '.$end->format('d.m.Y').'.',
            ];
        }

        $draftSet = (clone $baseQuery)
            ->where('status', 'draft')
            ->withCount([
                'items as active_items_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('effective_from')
            ->first();

        if ($draftSet) {
            return [
                'tone' => 'warning',
                'headline' => 'Na przyszly tydzien jest szkic ogloszen, ale nie jest jeszcze opublikowany.',
                'description' => $draftSet->title.' | Aktywne pozycje: '.(int) ($draftSet->active_items_count ?? 0).'. Warto dokonczyc zestaw i opublikowac go przed rozpoczeciem tygodnia.',
            ];
        }

        return [
            'tone' => 'danger',
            'headline' => 'Brakuje przygotowanych ogloszen na przyszly tydzien.',
            'description' => 'Nie znaleziono ani opublikowanego zestawu, ani szkicu obejmujacego kolejny tydzien. Warto utworzyc zestaw i opublikowac go zawczasu.',
        ];
    }

    private function buildOfficeSummary(Parish $parish, User $priest): array
    {
        $openQuery = OfficeConversation::query()
            ->where('parish_id', $parish->getKey())
            ->where('priest_user_id', $priest->getKey())
            ->where('status', OfficeConversation::STATUS_OPEN);

        $openCount = (clone $openQuery)->count();
        $unreadCount = (clone $openQuery)
            ->whereHas('messages', fn ($query) => $query
                ->whereNull('read_by_priest_at')
                ->where('sender_user_id', '!=', $priest->getKey()))
            ->count();

        if ($openCount === 0) {
            return [
                'tone' => 'success',
                'headline' => 'Kancelaria online jest uporzadkowana.',
                'description' => 'Nie masz obecnie otwartych konwersacji oczekujacych na domkniecie.',
                'open_count' => 0,
                'unread_count' => 0,
            ];
        }

        return [
            'tone' => $openCount >= 10 ? 'danger' : 'warning',
            'headline' => 'Masz otwarte konwersacje w kancelarii online.',
            'description' => 'Otwarte watki: '.$openCount.'. Nieprzeczytane lub wymagajace reakcji: '.$unreadCount.'. Warto odpowiadac parafianom mozliwie szybko i zamykac zakonczone sprawy.',
            'open_count' => $openCount,
            'unread_count' => $unreadCount,
        ];
    }

    private function buildNewsSummary(Parish $parish, CarbonInterface $generatedAt): array
    {
        $lastPublished = NewsPost::query()
            ->where('parish_id', $parish->getKey())
            ->published()
            ->orderByRaw('COALESCE(published_at, created_at) desc')
            ->first();

        $publishedLast30Days = $this->countPublishedNewsSince($parish, $generatedAt->copy()->subDays(30), $generatedAt);

        if ($publishedLast30Days >= 4) {
            return [
                'tone' => 'success',
                'headline' => 'Parafia publikuje aktualnosci regularnie.',
                'description' => 'W ostatnich 30 dniach opublikowano '.$publishedLast30Days.' wpisow. Ostatnia publikacja: '.$this->formatNewsDateLabel($lastPublished).'.',
            ];
        }

        if ($publishedLast30Days > 0) {
            return [
                'tone' => 'warning',
                'headline' => 'Warto publikowac aktualnosci czesciej.',
                'description' => 'W ostatnich 30 dniach opublikowano '.$publishedLast30Days.' wpisow. Ostatnia publikacja: '.$this->formatNewsDateLabel($lastPublished).'. Regularne wpisy pomagaja parafianom byc na biezaco.',
            ];
        }

        return [
            'tone' => 'danger',
            'headline' => 'Brakuje swiezych aktualnosci na stronie parafii.',
            'description' => 'W ostatnich 30 dniach nie opublikowano zadnej aktualnosci. Warto przygotowac wpis, aby parafianie byli dobrze poinformowani o tym, co dzieje sie w parafii.',
        ];
    }

    private function countPublishedNewsSince(Parish $parish, CarbonInterface $since, CarbonInterface $until): int
    {
        return NewsPost::query()
            ->where('parish_id', $parish->getKey())
            ->where('status', 'published')
            ->where(function ($query) use ($since, $until): void {
                $query->whereBetween('published_at', [$since, $until])
                    ->orWhere(function ($inner) use ($since, $until): void {
                        $inner->whereNull('published_at')
                            ->whereBetween('created_at', [$since, $until]);
                    });
            })
            ->count();
    }

    private function formatNewsDateLabel(?NewsPost $post): string
    {
        if (! $post) {
            return 'brak publikacji';
        }

        $timestamp = $post->published_at ?? $post->created_at;

        return $timestamp?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? 'brak daty';
    }
}
