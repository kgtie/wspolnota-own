<?php

namespace Database\Seeders;

use App\Models\Parish;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $parishes = Parish::query()->get();

        if ($parishes->isEmpty()) {
            $this->command?->warn('Brak parafii. Pominieto seedowanie ogloszen.');

            return;
        }

        $now = now();
        $startWeek = $now->copy()->subWeeks(90)->startOfWeek(Carbon::SUNDAY);
        $endWeek = $now->copy()->addWeeks(72)->startOfWeek(Carbon::SUNDAY);
        $seededAt = $now->copy();

        $totalSets = 0;
        $totalItems = 0;
        $totalImportantItems = 0;
        $totalPastSets = 0;
        $totalFutureSets = 0;
        $statusTotals = [
            'draft' => 0,
            'published' => 0,
            'archived' => 0,
        ];

        $rows = [];
        $itemsBatch = [];

        foreach ($parishes as $parish) {
            $adminIds = User::query()
                ->whereHas('managedParishes', fn ($query) => $query
                    ->where('parish_id', $parish->id)
                    ->where('parish_user.is_active', true))
                ->pluck('id')
                ->all();

            $parishSetCount = 0;
            $parishItemCount = 0;
            $parishImportantCount = 0;
            $parishPast = 0;
            $parishFuture = 0;
            $parishStatusTotals = [
                'draft' => 0,
                'published' => 0,
                'archived' => 0,
            ];

            for ($weekStart = $startWeek->copy(); $weekStart->lte($endWeek); $weekStart->addWeek()) {
                $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);
                $status = $this->determineStatus($weekStart, $weekEnd, $now);
                $publishedAt = $status === 'draft'
                    ? null
                    : $weekStart->copy()->subDay()->setTime(18, random_int(0, 1) === 0 ? 0 : 30);
                $authorId = $this->pickAuthorId($adminIds);
                $weekLabel = $this->buildWeekLabel($weekStart);

                $setId = DB::table('announcement_sets')->insertGetId([
                    'parish_id' => $parish->id,
                    'title' => 'Ogloszenia parafialne - '.$weekLabel,
                    'week_label' => $weekLabel,
                    'effective_from' => $weekStart->toDateString(),
                    'effective_to' => $weekEnd->toDateString(),
                    'status' => $status,
                    'published_at' => $publishedAt,
                    'lead' => $this->buildLead($weekStart, $weekEnd),
                    'footer_notes' => $this->buildFooterNotes(),
                    'created_by_user_id' => $authorId,
                    'updated_by_user_id' => random_int(1, 100) <= 65 ? $authorId : null,
                    'created_at' => $seededAt,
                    'updated_at' => $seededAt,
                ]);

                $items = $this->buildItems(
                    setId: $setId,
                    weekStart: $weekStart,
                    weekEnd: $weekEnd,
                    status: $status,
                    adminIds: $adminIds,
                    seededAt: $seededAt,
                );

                foreach ($items as $item) {
                    $itemsBatch[] = $item;
                    $parishItemCount++;
                    $totalItems++;

                    if ($item['is_important']) {
                        $parishImportantCount++;
                        $totalImportantItems++;
                    }

                    if (count($itemsBatch) >= 1000) {
                        DB::table('announcement_items')->insert($itemsBatch);
                        $itemsBatch = [];
                    }
                }

                $parishSetCount++;
                $totalSets++;
                $parishStatusTotals[$status]++;
                $statusTotals[$status]++;

                if ($weekEnd->lt($now->copy()->startOfDay())) {
                    $parishPast++;
                    $totalPastSets++;
                } else {
                    $parishFuture++;
                    $totalFutureSets++;
                }
            }

            $rows[] = [
                $parish->short_name ?: $parish->name,
                $parishSetCount,
                $parishItemCount,
                $parishImportantCount,
                $parishPast,
                $parishFuture,
                $parishStatusTotals['draft'],
                $parishStatusTotals['published'],
                $parishStatusTotals['archived'],
            ];
        }

        if (! empty($itemsBatch)) {
            DB::table('announcement_items')->insert($itemsBatch);
        }

        $averageItemsPerSet = $totalSets > 0
            ? number_format($totalItems / $totalSets, 2, ',', ' ')
            : '0,00';

        $this->command?->info('');
        $this->command?->info('Seeder ogloszen zakonczony.');
        $this->command?->info("Zakres tygodni: {$startWeek->format('d.m.Y')} - {$endWeek->format('d.m.Y')}");
        $this->command?->info("Dodano {$totalSets} zestawow i {$totalItems} pojedynczych ogloszen.");

        $this->command?->table(
            ['Parafia', 'Zestawy', 'Ogloszenia', 'Wazne', 'Przeszle', 'Przyszle', 'Szkice', 'Publikacje', 'Archiwalne'],
            $rows,
        );

        $this->command?->table(
            ['Metryka', 'Wartosc'],
            [
                ['Liczba zestawow', (string) $totalSets],
                ['Liczba pojedynczych ogloszen', (string) $totalItems],
                ['Liczba ogloszen waznych', (string) $totalImportantItems],
                ['Srednia liczba ogloszen na zestaw', $averageItemsPerSet],
                ['Zestawy przeszle', (string) $totalPastSets],
                ['Zestawy przyszle', (string) $totalFutureSets],
                ['Status: szkic', (string) $statusTotals['draft']],
                ['Status: opublikowany', (string) $statusTotals['published']],
                ['Status: archiwalny', (string) $statusTotals['archived']],
            ],
        );
    }

    protected function determineStatus(Carbon $weekStart, Carbon $weekEnd, Carbon $now): string
    {
        if ($weekEnd->lt($now->copy()->subWeeks(26))) {
            return random_int(1, 100) <= 30 ? 'archived' : 'published';
        }

        if ($weekStart->lte($now->copy()->addWeeks(2))) {
            return 'published';
        }

        return random_int(1, 100) <= 80 ? 'draft' : 'published';
    }

    protected function buildWeekLabel(Carbon $weekStart): string
    {
        $season = $this->seasonForMonth((int) $weekStart->month);
        $romanWeek = $this->toRoman((int) $weekStart->isoWeek());

        return "{$romanWeek} tydzien - {$season}";
    }

    protected function buildLead(Carbon $weekStart, Carbon $weekEnd): string
    {
        $templates = [
            'Drodzy Parafianie, powierzamy ten tydzien opiece Matki Bozej i dziekujemy za Wasza obecnosc we wspolnocie.',
            'Witamy wszystkich gosci i parafian. Niech ten tydzien bedzie czasem modlitwy, pojednania i dobrych uczynkow.',
            'Przypominamy, ze kancelaria oraz duszpasterze sa do Waszej dyspozycji. Zapraszamy do kontaktu i wspolnej modlitwy.',
            'Dziekujemy za wsparcie parafii. Prosimy o dalsza modlitwe za chorych, rodziny i osoby starsze z naszej wspolnoty.',
        ];

        $intro = $templates[array_rand($templates)];

        return $intro.' Okres ogloszen obejmuje '.$weekStart->format('d.m.Y').' - '.$weekEnd->format('d.m.Y').'.';
    }

    protected function buildFooterNotes(): ?string
    {
        if (random_int(1, 100) > 55) {
            return null;
        }

        $notes = [
            'Bog zaplac za wszystkie ofiary skladane na potrzeby parafii i dziela milosierdzia.',
            'Za tydzien po mszach odbedzie sie zborka na remont kaplicy adoracji.',
            'W razie potrzeby odwiedzin chorych prosimy o kontakt z kancelaria.',
            'Prosimy o modlitwe za zmarlych parafian z minionego tygodnia.',
            'Proboszcz i duszpasterze dziekuja za zaangazowanie wszystkich wolontariuszy.',
        ];

        return $notes[array_rand($notes)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildItems(
        int $setId,
        Carbon $weekStart,
        Carbon $weekEnd,
        string $status,
        array $adminIds,
        Carbon $seededAt,
    ): array {
        $items = [];
        $position = 1;

        $authorId = $this->pickAuthorId($adminIds);

        $baseTemplates = [
            [
                'title' => 'Porzadek mszy',
                'content' => 'W dni powszednie msze swiete odprawiane sa o 7:00 i 18:00. W niedziele o 7:00, 9:00, 11:00 i 18:00.',
                'important' => false,
            ],
            [
                'title' => 'Kancelaria parafialna',
                'content' => 'Kancelaria parafialna czynna od poniedzialku do piatku w godzinach 16:00-17:30. W sobote po telefonicznym umowieniu.',
                'important' => false,
            ],
            [
                'title' => 'Spowiedz',
                'content' => 'Spowiedz swieta codziennie 20 minut przed kazda msza wieczorna oraz w pierwszy piatek miesiaca od godziny 17:00.',
                'important' => false,
            ],
            [
                'title' => 'Wspolnota rozancowa',
                'content' => 'Spotkanie wspolnoty rozancowej odbedzie sie w '.$weekStart->copy()->addDays(2)->format('l, d.m').' po mszy wieczornej.',
                'important' => false,
            ],
            [
                'title' => 'Katecheza doroslych',
                'content' => 'Katecheza dla doroslych i narzeczonych odbedzie sie w salce parafialnej '.$weekStart->copy()->addDays(4)->format('d.m').' o godz. 19:00.',
                'important' => false,
            ],
        ];

        $optionalTemplates = [
            [
                'title' => 'Zbiorka charytatywna',
                'content' => 'Przez caly tydzien zbieramy trwale produkty zywnosciowe dla rodzin potrzebujacych. Kosz znajduje sie przy glownym wejsciu do kosciola.',
                'important' => true,
            ],
            [
                'title' => 'Remont i ofiary',
                'content' => 'Trwa kolejny etap prac remontowych w zakrystii. Ofiary mozna skladac do skarbony remontowej oraz przez przelew parafialny.',
                'important' => true,
            ],
            [
                'title' => 'Spotkanie ministrantow',
                'content' => 'Zbiorka ministrantow i lektorow w sobote o godz. 10:00 w domu parafialnym. Zapraszamy takze nowych kandydatow.',
                'important' => false,
            ],
            [
                'title' => 'Duszpasterstwo mlodziezy',
                'content' => 'Spotkanie mlodziezy "Wspolnota 18+" w piatek '.$weekStart->copy()->addDays(5)->format('d.m').' o 19:30 w sali sw. Jana Pawla II.',
                'important' => false,
            ],
            [
                'title' => 'Krzyzowa i adoracja',
                'content' => 'Nabozenstwo Drogi Krzyzowej w piatek o 17:15, a adoracja Najswietszego Sakramentu w ciszy po mszy wieczornej do 20:00.',
                'important' => false,
            ],
            [
                'title' => 'Parafialny wolontariat',
                'content' => 'Poszukujemy osob do pomocy przy porzadkach w kosciele i organizacji wydarzen parafialnych. Zapisy w zakrystii.',
                'important' => false,
            ],
            [
                'title' => 'Modlitwa za chorych',
                'content' => 'W '.$weekStart->copy()->addDays(3)->format('l').' o 18:00 msza swieta z modlitwa o uzdrowienie i blogoslawienstwo dla chorych.',
                'important' => false,
            ],
            [
                'title' => 'Intencje mszalne',
                'content' => 'Intencje mszalne na kolejny miesiac przyjmujemy do '.$weekEnd->copy()->addDays(2)->format('d.m.Y').'. Prosimy o wczesniejsze zglaszanie terminow.',
                'important' => false,
            ],
            [
                'title' => 'Zapowiedzi przedmalzenskie',
                'content' => 'Podajemy do publicznej wiadomosci zapowiedzi przedmalzenskie. Ewentualne przeszkody kanoniczne nalezy zglosic w kancelarii.',
                'important' => false,
            ],
            [
                'title' => 'Pielgrzymka parafialna',
                'content' => 'Rozpoczynamy zapisy na parafialna pielgrzymke autokarowa. Szczegoly na stronie parafii i w gablocie.',
                'important' => false,
            ],
        ];

        $templates = $baseTemplates;
        shuffle($optionalTemplates);

        $extraCount = random_int(4, 8);
        $templates = array_merge($templates, array_slice($optionalTemplates, 0, $extraCount));

        if ($status === 'draft' && random_int(1, 100) <= 35) {
            $templates[] = [
                'title' => 'Robocza notatka redakcyjna',
                'content' => 'To ogloszenie pozostaje robocze i wymaga potwierdzenia terminow przed publikacja.',
                'important' => false,
            ];
        }

        $importantPresent = false;

        foreach ($templates as $template) {
            $isImportant = (bool) $template['important'];

            if (! $importantPresent && $status !== 'draft' && random_int(1, 100) <= 30) {
                $isImportant = true;
            }

            $isActive = random_int(1, 100) <= 93;

            $items[] = [
                'announcement_set_id' => $setId,
                'position' => $position,
                'title' => $template['title'],
                'content' => $template['content'],
                'is_important' => $isImportant,
                'is_active' => $isActive,
                'created_by_user_id' => $authorId,
                'updated_by_user_id' => random_int(1, 100) <= 70 ? $authorId : null,
                'created_at' => $seededAt,
                'updated_at' => $seededAt,
            ];

            $importantPresent = $importantPresent || $isImportant;
            $position++;
        }

        if (! $importantPresent && ! empty($items)) {
            $idx = array_rand($items);
            $items[$idx]['is_important'] = true;
        }

        return $items;
    }

    protected function seasonForMonth(int $month): string
    {
        return match (true) {
            in_array($month, [12, 1], true) => 'okres Bozego Narodzenia',
            in_array($month, [2, 3], true) => 'okres zwykly',
            in_array($month, [4, 5], true) => 'okres wielkanocny',
            in_array($month, [6, 7, 8], true) => 'okres wakacyjny',
            default => 'okres zwykly',
        };
    }

    protected function toRoman(int $number): string
    {
        $map = [
            1000 => 'M',
            900 => 'CM',
            500 => 'D',
            400 => 'CD',
            100 => 'C',
            90 => 'XC',
            50 => 'L',
            40 => 'XL',
            10 => 'X',
            9 => 'IX',
            5 => 'V',
            4 => 'IV',
            1 => 'I',
        ];

        $result = '';

        foreach ($map as $value => $roman) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }

        return $result;
    }

    protected function pickAuthorId(array $adminIds): ?int
    {
        if (empty($adminIds)) {
            return null;
        }

        return (int) $adminIds[array_rand($adminIds)];
    }
}
