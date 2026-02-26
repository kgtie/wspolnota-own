<?php

namespace Database\Seeders;

use App\Models\Parish;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsPostSeeder extends Seeder
{
    public function run(): void
    {
        $parishes = Parish::query()->get();

        if ($parishes->isEmpty()) {
            $this->command?->warn('Brak parafii. Pominieto seedowanie aktualnosci.');

            return;
        }

        $now = now();
        $startWeek = $now->copy()->subMonths(20)->startOfWeek(Carbon::MONDAY);
        $endWeek = $now->copy()->addMonths(5)->endOfWeek(Carbon::SUNDAY);
        $seededAt = $now->copy();

        $totalPosts = 0;
        $totalPast = 0;
        $totalFuture = 0;
        $totalPinned = 0;
        $statusTotals = [
            'draft' => 0,
            'scheduled' => 0,
            'published' => 0,
            'archived' => 0,
        ];
        $rows = [];
        $batch = [];

        foreach ($parishes as $parish) {
            $adminIds = User::query()
                ->whereHas('managedParishes', fn ($query) => $query
                    ->where('parish_id', $parish->id)
                    ->where('parish_user.is_active', true))
                ->pluck('id')
                ->all();

            $parishPosts = 0;
            $parishPast = 0;
            $parishFuture = 0;
            $parishPinned = 0;
            $parishStatusTotals = [
                'draft' => 0,
                'scheduled' => 0,
                'published' => 0,
                'archived' => 0,
            ];

            $weekIndex = 0;

            for ($weekStart = $startWeek->copy(); $weekStart->lte($endWeek); $weekStart->addWeek()) {
                $postsInWeek = random_int(2, 5);

                for ($postIndex = 1; $postIndex <= $postsInWeek; $postIndex++) {
                    $publicationMoment = $this->randomPublicationMomentForWeek($weekStart);
                    $status = $this->determineStatus($publicationMoment, $now);
                    [$publishedAt, $scheduledFor] = $this->resolvePublicationDates($status, $publicationMoment, $now);

                    $title = $this->buildTitle($publicationMoment);
                    $content = $this->buildContent($title, $publicationMoment, $status);
                    $slug = $this->buildSlug($title, $parish->id, $publicationMoment, $weekIndex, $postIndex);
                    $authorId = $this->pickAuthorId($adminIds);
                    $isPinned = $this->shouldPin($status, $publicationMoment, $now);
                    [$createdAt, $updatedAt] = $this->resolveAuditDates($status, $publicationMoment, $now, $seededAt);

                    $batch[] = [
                        'parish_id' => $parish->id,
                        'title' => $title,
                        'slug' => $slug,
                        'content' => $content,
                        'status' => $status,
                        'published_at' => $publishedAt,
                        'scheduled_for' => $scheduledFor,
                        'is_pinned' => $isPinned,
                        'created_by_user_id' => $authorId,
                        'updated_by_user_id' => random_int(1, 100) <= 70 ? $authorId : null,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ];

                    $parishPosts++;
                    $totalPosts++;
                    $parishStatusTotals[$status]++;
                    $statusTotals[$status]++;

                    if ($isPinned) {
                        $parishPinned++;
                        $totalPinned++;
                    }

                    if ($publicationMoment->lt($now->copy()->startOfDay())) {
                        $parishPast++;
                        $totalPast++;
                    } else {
                        $parishFuture++;
                        $totalFuture++;
                    }

                    if (count($batch) >= 500) {
                        DB::table('news_posts')->insert($batch);
                        $batch = [];
                    }
                }

                $weekIndex++;
            }

            $rows[] = [
                $parish->short_name ?: $parish->name,
                $parishPosts,
                $parishPast,
                $parishFuture,
                $parishPinned,
                $parishStatusTotals['draft'],
                $parishStatusTotals['scheduled'],
                $parishStatusTotals['published'],
                $parishStatusTotals['archived'],
            ];
        }

        if (! empty($batch)) {
            DB::table('news_posts')->insert($batch);
        }

        $this->command?->info('');
        $this->command?->info('Seeder aktualnosci zakonczony.');
        $this->command?->info("Zakres tygodni: {$startWeek->format('d.m.Y')} - {$endWeek->format('d.m.Y')}");
        $this->command?->info("Dodano {$totalPosts} wpisow aktualnosci.");

        $this->command?->table(
            ['Parafia', 'Wpisy', 'Przeszle', 'Przyszle', 'Przypiete', 'Szkice', 'Zaplanowane', 'Opublikowane', 'Archiwalne'],
            $rows,
        );

        $this->command?->table(
            ['Metryka', 'Wartosc'],
            [
                ['Liczba wpisow', (string) $totalPosts],
                ['Wpisy przeszle', (string) $totalPast],
                ['Wpisy przyszle', (string) $totalFuture],
                ['Wpisy przypiete', (string) $totalPinned],
                ['Status: szkic', (string) $statusTotals['draft']],
                ['Status: zaplanowany', (string) $statusTotals['scheduled']],
                ['Status: opublikowany', (string) $statusTotals['published']],
                ['Status: archiwalny', (string) $statusTotals['archived']],
            ],
        );
    }

    protected function determineStatus(Carbon $publicationMoment, Carbon $now): string
    {
        if ($publicationMoment->gt($now)) {
            if ($publicationMoment->lte($now->copy()->addDays(21))) {
                return random_int(1, 100) <= 70 ? 'scheduled' : 'draft';
            }

            return random_int(1, 100) <= 80 ? 'scheduled' : 'draft';
        }

        if ($publicationMoment->lte($now->copy()->subMonths(6))) {
            return match (true) {
                random_int(1, 100) <= 20 => 'archived',
                random_int(1, 100) <= 30 => 'draft',
                default => 'published',
            };
        }

        if ($publicationMoment->lte($now->copy()->subDays(2))) {
            return match (true) {
                random_int(1, 100) <= 10 => 'archived',
                random_int(1, 100) <= 12 => 'draft',
                default => 'published',
            };
        }

        return random_int(1, 100) <= 70 ? 'published' : 'draft';
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function resolvePublicationDates(string $status, Carbon $publicationMoment, Carbon $now): array
    {
        return match ($status) {
            'published' => [
                $publicationMoment->copy()->subMinutes(random_int(0, 180))->min($now->copy()),
                null,
            ],
            'archived' => [
                $publicationMoment->copy()->subHours(random_int(2, 48))->min($now->copy()),
                null,
            ],
            'scheduled' => [
                null,
                $publicationMoment->copy()->max($now->copy()->addMinutes(30)),
            ],
            default => [
                null,
                random_int(1, 100) <= 35
                    ? $publicationMoment->copy()->max($now->copy()->addHours(random_int(6, 72)))
                    : null,
            ],
        };
    }

    protected function randomPublicationMomentForWeek(Carbon $weekStart): Carbon
    {
        $hours = [7, 8, 10, 12, 15, 17, 19, 20];

        return $weekStart
            ->copy()
            ->addDays(random_int(0, 6))
            ->setTime($hours[array_rand($hours)], random_int(1, 100) <= 60 ? 0 : 30);
    }

    protected function buildTitle(Carbon $publicationMoment): string
    {
        $templates = [
            'Zaproszenie na adoracje Najswietszego Sakramentu',
            'Wolontariat parafialny - nowe dyzury i zapisy',
            'Spotkanie wspolnoty rodzin',
            'Zmiany godzin kancelarii parafialnej',
            'Parafialna akcja pomocy potrzebujacym',
            'Przygotowanie liturgii na najblizsza niedziele',
            'Podsumowanie wydarzen z zycia parafii',
            'Informacje o pielgrzymce parafialnej',
            'Nowe inicjatywy duszpasterskie',
            'Zapisy na katechezy i kursy formacyjne',
            'Nabozenstwa i intencje modlitewne na ten tydzien',
            'Wspolna modlitwa za chorych i seniorow',
        ];

        $suffixes = [
            'szczegoly i harmonogram',
            'aktualne informacje',
            'komunikat duszpasterski',
            'wazne terminy',
            'dla parafian i gosci',
            'zapraszamy do wspolpracy',
        ];

        return $templates[array_rand($templates)]
            .' - '
            .$suffixes[array_rand($suffixes)]
            .' ('
            .$publicationMoment->format('d.m.Y')
            .')';
    }

    protected function buildContent(string $title, Carbon $publicationMoment, string $status): string
    {
        $openingTemplates = [
            'Drodzy Parafianie, przekazujemy najnowsze informacje dotyczace zycia naszej wspolnoty.',
            'W tym wpisie zebrane zostaly najwazniejsze sprawy organizacyjne i duszpasterskie.',
            'Zapraszamy do zapoznania sie z komunikatem oraz aktywnego wlaczenia sie w zycie parafii.',
        ];

        $detailsTemplates = [
            'W najblizszych dniach odbeda sie spotkania formacyjne, dyzury kancelarii oraz wydarzenia modlitewne. Szczegoly publikujemy ponizej.',
            'Prosmy o przekazanie informacji osobom starszym i tym, ktorzy nie korzystaja na co dzien z internetu. Dziekujemy za wsparcie.',
            'Wspolnota parafialna rozwija kolejne inicjatywy, dlatego regularnie aktualizujemy kalendarz i prosimy o sledzenie kolejnych wpisow.',
        ];

        $ctaTemplates = [
            'Jesli chcesz pomoc przy organizacji wydarzen, skontaktuj sie z kancelaria lub zglos sie po mszy.',
            'Prosmy o modlitwe za wszystkich zaangazowanych w przygotowanie liturgii i spotkan wspolnotowych.',
            'Dziekujemy za kazda forme wsparcia i obecnosci. Do zobaczenia na wspolnych wydarzeniach.',
        ];

        $bullets = [
            'Spotkanie organizacyjne: '.$publicationMoment->copy()->addDay()->format('d.m.Y').' o 19:00.',
            'Spowiedz i adoracja codziennie 20 minut przed msza wieczorna.',
            'Zapisy do wolontariatu prowadzone sa w kancelarii parafialnej.',
            'Intencje mszalne mozna zglaszac osobiscie lub telefonicznie.',
        ];

        if ($status === 'scheduled') {
            $openingTemplates[] = 'Wpis jest przygotowany do publikacji. Po publikacji tresc bedzie widoczna dla parafian.';
        }

        return implode("\n", [
            '<h2>'.$title.'</h2>',
            '<p>'.$openingTemplates[array_rand($openingTemplates)].'</p>',
            '<p>'.$detailsTemplates[array_rand($detailsTemplates)].'</p>',
            '<ul>',
            '<li>'.$bullets[array_rand($bullets)].'</li>',
            '<li>'.$bullets[array_rand($bullets)].'</li>',
            '<li>'.$bullets[array_rand($bullets)].'</li>',
            '</ul>',
            '<p>'.$ctaTemplates[array_rand($ctaTemplates)].'</p>',
        ]);
    }

    protected function buildSlug(
        string $title,
        int $parishId,
        Carbon $publicationMoment,
        int $weekIndex,
        int $postIndex
    ): string {
        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'aktualnosc';

        return "{$baseSlug}-{$parishId}-{$publicationMoment->format('YmdHis')}-{$weekIndex}{$postIndex}";
    }

    protected function pickAuthorId(array $adminIds): ?int
    {
        if (empty($adminIds)) {
            return null;
        }

        if (random_int(1, 100) <= 85) {
            return $adminIds[array_rand($adminIds)];
        }

        return null;
    }

    protected function shouldPin(string $status, Carbon $publicationMoment, Carbon $now): bool
    {
        if ($status !== 'published') {
            return false;
        }

        if (! $publicationMoment->between($now->copy()->subWeeks(3), $now->copy()->addWeek())) {
            return false;
        }

        return random_int(1, 100) <= 12;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveAuditDates(
        string $status,
        Carbon $publicationMoment,
        Carbon $now,
        Carbon $seededAt,
    ): array {
        if (in_array($status, ['published', 'archived'], true)) {
            $createdAt = $publicationMoment->copy()->subDays(random_int(2, 45));
        } else {
            $createdAt = $seededAt->copy()->subDays(random_int(0, 75))->subHours(random_int(0, 12));
        }

        if ($createdAt->gt($now)) {
            $createdAt = $now->copy()->subHours(random_int(2, 10));
        }

        $updatedAt = $createdAt->copy()->addHours(random_int(1, 120));

        if ($updatedAt->gt($now)) {
            $updatedAt = $now->copy()->subMinutes(random_int(1, 240));
        }

        return [$createdAt, $updatedAt];
    }
}
