<?php

namespace Database\Seeders;

use App\Models\Parish;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MassSeeder extends Seeder
{
    /**
     * Seeduje duza baze intencji:
     * - msze historyczne oraz przyszle
     * - zroznicowane typy i rodzaje
     * - realistyczne stypendia i statusy odprawienia
     */
    public function run(): void
    {
        $parishes = Parish::query()->get();

        if ($parishes->isEmpty()) {
            $this->command?->warn('Brak parafii. Pominieto seedowanie mszy swietych.');

            return;
        }

        $now = now();
        $startDate = $now->copy()->subMonths(18)->startOfDay();
        $endDate = $now->copy()->addMonths(10)->endOfDay();
        $seededAt = $now->copy();
        $totalInserted = 0;
        $totalPast = 0;
        $totalFuture = 0;
        $totalStipendiumCount = 0;
        $totalStipendiumPaidCount = 0;
        $totalStipendiumValue = 0.0;
        $totalStipendiumPaidValue = 0.0;
        $statusTotals = [
            'scheduled' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
        $parishRows = [];

        foreach ($parishes as $parish) {
            $adminIds = User::query()
                ->whereHas('managedParishes', fn ($query) => $query
                    ->where('parish_id', $parish->id)
                    ->where('parish_user.is_active', true))
                ->pluck('id')
                ->all();

            $priestNames = $this->resolvePriestNames($adminIds);
            $batch = [];
            $parishInserted = 0;
            $parishPast = 0;
            $parishFuture = 0;
            $parishStatusTotals = [
                'scheduled' => 0,
                'completed' => 0,
                'cancelled' => 0,
            ];

            for ($day = $startDate->copy(); $day->lte($endDate); $day->addDay()) {
                $times = $this->timesForDay($day);
                $numberOfMasses = min($this->numberOfMassesForDay($day), count($times));

                shuffle($times);
                $selectedTimes = array_slice($times, 0, $numberOfMasses);
                sort($selectedTimes);

                foreach ($selectedTimes as $time) {
                    $celebrationAt = $day->copy()->setTimeFromTimeString($time);
                    $massType = $this->pickMassType();
                    $intention = $this->generateIntention($massType);
                    $massKind = $this->determineMassKind($day, $intention['for_dead']);
                    $status = $this->determineStatus($celebrationAt);
                    $stipendiumAmount = $this->determineStipendium($massType);
                    $stipendiumPaidAt = $this->determineStipendiumPaidAt($stipendiumAmount, $celebrationAt);
                    $authorId = ! empty($adminIds) ? $adminIds[array_rand($adminIds)] : null;
                    $isPastMass = $celebrationAt->lt($now);

                    $batch[] = [
                        'parish_id' => $parish->id,
                        'intention_title' => $intention['title'],
                        'intention_details' => $this->generateIntentionDetails(),
                        'celebration_at' => $celebrationAt,
                        'stipendium_amount' => $stipendiumAmount,
                        'stipendium_paid_at' => $stipendiumPaidAt,
                        'mass_kind' => $massKind,
                        'mass_type' => $massType,
                        'status' => $status,
                        'celebrant_name' => random_int(1, 100) <= 90
                            ? $priestNames[array_rand($priestNames)]
                            : null,
                        'location' => fake('pl_PL')->randomElement([
                            'Kosciol parafialny',
                            'Kaplica adoracji',
                            'Kaplica sw. Jozefa',
                            'Kaplica cmentarna',
                        ]),
                        'notes' => random_int(1, 100) <= 25
                            ? fake('pl_PL')->sentence(16)
                            : null,
                        'created_by_user_id' => $authorId,
                        'updated_by_user_id' => random_int(1, 100) <= 65 ? $authorId : null,
                        'created_at' => $seededAt,
                        'updated_at' => $seededAt,
                    ];

                    $parishInserted++;
                    $parishStatusTotals[$status]++;
                    $statusTotals[$status]++;

                    if ($isPastMass) {
                        $parishPast++;
                        $totalPast++;
                    } else {
                        $parishFuture++;
                        $totalFuture++;
                    }

                    if ($stipendiumAmount !== null) {
                        $totalStipendiumCount++;
                        $totalStipendiumValue += $stipendiumAmount;

                        if ($stipendiumPaidAt !== null) {
                            $totalStipendiumPaidCount++;
                            $totalStipendiumPaidValue += $stipendiumAmount;
                        }
                    }

                    if (count($batch) >= 500) {
                        DB::table('masses')->insert($batch);
                        $totalInserted += count($batch);
                        $batch = [];
                    }
                }
            }

            if (! empty($batch)) {
                DB::table('masses')->insert($batch);
                $totalInserted += count($batch);
            }

            $parishRows[] = [
                $parish->short_name ?: $parish->name,
                $parishInserted,
                $parishPast,
                $parishFuture,
                $parishStatusTotals['scheduled'],
                $parishStatusTotals['completed'],
                $parishStatusTotals['cancelled'],
            ];
        }

        $participantsStats = $this->seedParticipants($now);

        $this->command?->info('');
        $this->command?->info('⛪ Seeder mszy swietych zakonczony.');
        $this->command?->info("Zakres dat: {$startDate->format('d.m.Y')} - {$endDate->format('d.m.Y')}");
        $this->command?->info("Lacznie dodano {$totalInserted} wpisow mszalnych.");
        $this->command?->info('');

        $this->command?->table(
            ['Parafia', 'Razem', 'Przeszle', 'Przyszle', 'Zaplanowane', 'Odprawione', 'Odwolane'],
            $parishRows,
        );

        $this->command?->table(
            ['Metryka', 'Wartosc'],
            [
                ['Status: zaplanowane', (string) $statusTotals['scheduled']],
                ['Status: odprawione', (string) $statusTotals['completed']],
                ['Status: odwolane', (string) $statusTotals['cancelled']],
                ['Msze przeszle', (string) $totalPast],
                ['Msze przyszle', (string) $totalFuture],
                ['Stypendia przypisane', (string) $totalStipendiumCount],
                ['Stypendia oplacone', (string) $totalStipendiumPaidCount],
                ['Suma stypendiow', $this->formatCurrency($totalStipendiumValue)],
                ['Suma oplaconych stypendiow', $this->formatCurrency($totalStipendiumPaidValue)],
                ['Msze z uczestnikami', (string) $participantsStats['masses_with_participants']],
                ['Liczba zapisow uczestnikow', (string) $participantsStats['registrations_total']],
                ['Srednio uczestnikow na msze (z zapisami)', (string) $participantsStats['average_per_mass']],
            ],
        );
    }

    /**
     * @return array<string>
     */
    protected function resolvePriestNames(array $adminIds): array
    {
        $names = User::query()
            ->whereIn('id', $adminIds)
            ->pluck('full_name')
            ->filter()
            ->map(function (string $name): string {
                $trimmed = trim($name);

                if (str_starts_with(mb_strtolower($trimmed), 'ks.')) {
                    return $trimmed;
                }

                return 'ks. '.$trimmed;
            })
            ->values()
            ->all();

        return array_values(array_unique(array_merge($names, [
            'ks. Jan Kowalski',
            'ks. Piotr Nowak',
            'ks. Michal Zielinski',
            'ks. Tomasz Wisniewski',
        ])));
    }

    /**
     * @return array<string>
     */
    protected function timesForDay(Carbon $day): array
    {
        if ($day->isSunday()) {
            return ['07:00', '08:30', '10:00', '11:30', '13:00', '18:00'];
        }

        if ($day->isSaturday()) {
            return ['07:00', '08:00', '18:00', '19:00'];
        }

        return ['06:30', '07:00', '08:00', '17:00', '18:00'];
    }

    protected function numberOfMassesForDay(Carbon $day): int
    {
        if ($day->isSunday()) {
            return random_int(4, 6);
        }

        if ($day->isSaturday()) {
            return random_int(2, 4);
        }

        return random_int(1, 3);
    }

    protected function pickMassType(): string
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 60 => 'individual',
            $roll <= 80 => 'collective',
            $roll <= 92 => 'occasional',
            default => 'gregorian',
        };
    }

    /**
     * @return array{title: string, for_dead: bool}
     */
    protected function generateIntention(string $massType): array
    {
        $faker = fake('pl_PL');
        $maleName = $faker->firstNameMale();
        $femaleName = $faker->firstNameFemale();
        $surname = $faker->lastName();

        if ($massType === 'gregorian') {
            return [
                'title' => "Gregorianska za sp. {$maleName} {$surname}",
                'for_dead' => true,
            ];
        }

        if ($massType === 'collective') {
            return [
                'title' => $faker->randomElement([
                    'Zbiorowa za zmarlych parafian',
                    'Zbiorowa o uzdrowienie i potrzebne laski',
                    'Zbiorowa w intencjach nowennowych',
                    'Zbiorowa za rodziny naszej parafii',
                ]),
                'for_dead' => random_int(1, 100) <= 45,
            ];
        }

        if ($massType === 'occasional') {
            return [
                'title' => $faker->randomElement([
                    "Dziekczynna z okazji rocznicy slubu {$maleName} i {$femaleName}",
                    "O blogoslawienstwo na nowy rok zycia dla {$maleName}",
                    "W intencji dzieci pierwszokomunijnych",
                    "W intencji kandydatow do bierzmowania",
                    "W intencji chorych i cierpiacych",
                ]),
                'for_dead' => false,
            ];
        }

        $forDead = random_int(1, 100) <= 55;

        return [
            'title' => $forDead
                ? "Za sp. {$maleName} {$surname} oraz zmarlych z rodziny"
                : $faker->randomElement([
                    "O zdrowie i blogoslawienstwo dla {$maleName} {$surname}",
                    "Dziekczynna za otrzymane laski dla rodziny {$surname}",
                    "W intencji narzeczonych {$maleName} i {$femaleName}",
                    "Za parafian",
                    "O szczesliwy porod dla {$femaleName}",
                ]),
            'for_dead' => $forDead,
        ];
    }

    protected function determineMassKind(Carbon $day, bool $forDead): string
    {
        $solemnities = [
            '01-01', // Boza Rodzicielka
            '01-06', // Objawienie Panskie
            '08-15', // Wniebowziecie NMP
            '11-01', // Wszystkich Swietych
            '12-25', // Boze Narodzenie
            '12-26', // Sw. Szczepana
        ];

        if (in_array($day->format('m-d'), $solemnities, true)) {
            return 'solemnity';
        }

        if ($forDead && random_int(1, 100) <= 35) {
            return 'requiem';
        }

        if ($day->isSunday()) {
            return 'sunday';
        }

        return random_int(1, 100) <= 10 ? 'votive' : 'weekday';
    }

    protected function determineStatus(Carbon $celebrationAt): string
    {
        if ($celebrationAt->isFuture()) {
            return 'scheduled';
        }

        if ($celebrationAt->lt(now()->subHours(2))) {
            return random_int(1, 100) <= 95 ? 'completed' : 'cancelled';
        }

        return 'scheduled';
    }

    protected function determineStipendium(string $massType): ?float
    {
        $chance = match ($massType) {
            'gregorian' => 90,
            'collective' => 70,
            'occasional' => 60,
            default => 65,
        };

        if (random_int(1, 100) > $chance) {
            return null;
        }

        return round((float) random_int(5000, 50000) / 100, 2);
    }

    protected function determineStipendiumPaidAt(?float $stipendium, Carbon $celebrationAt): ?Carbon
    {
        if ($stipendium === null) {
            return null;
        }

        if (random_int(1, 100) <= 20) {
            return null;
        }

        $from = $celebrationAt->copy()->subDays(45);
        $to = $celebrationAt->isFuture()
            ? ($celebrationAt->copy()->subHour()->lt(now()) ? $celebrationAt->copy()->subHour() : now())
            : $celebrationAt->copy()->addDays(3);

        if ($to->lt($from)) {
            $to = $from->copy()->addDay();
        }

        return Carbon::instance(fake('pl_PL')->dateTimeBetween($from, $to));
    }

    protected function generateIntentionDetails(): ?string
    {
        if (random_int(1, 100) > 45) {
            return null;
        }

        return fake('pl_PL')->randomElement([
            'Prosba o odczytanie intencji przed rozpoczeciem liturgii.',
            'Rodzina uczestniczy i prosi o modlitwe wspolnoty parafialnej.',
            'Intencja przekazana przez kancelarie parafialna.',
            'Prosba o modlitwe rowniez podczas nabozenstwa wieczornego.',
            'W intencji ofiarodawcow oraz dobrodziejow parafii.',
        ]);
    }

    protected function formatCurrency(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' PLN';
    }

    /**
     * @return array{masses_with_participants:int, registrations_total:int, average_per_mass:string}
     */
    protected function seedParticipants(Carbon $now): array
    {
        $userIds = User::query()
            ->where('role', 0)
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        if (empty($userIds)) {
            return [
                'masses_with_participants' => 0,
                'registrations_total' => 0,
                'average_per_mass' => '0,00',
            ];
        }

        $massCount = (int) DB::table('masses')->count();

        if ($massCount === 0) {
            return [
                'masses_with_participants' => 0,
                'registrations_total' => 0,
                'average_per_mass' => '0,00',
            ];
        }

        $massesWithParticipantsTarget = max(50, (int) round($massCount * 0.24));
        $massRows = DB::table('masses')
            ->select(['id', 'celebration_at'])
            ->inRandomOrder()
            ->limit($massesWithParticipantsTarget)
            ->get();

        $batch = [];
        $registrationsTotal = 0;
        $massesWithParticipants = 0;
        $maxParticipantsPerMass = min(14, count($userIds));

        foreach ($massRows as $massRow) {
            $participantsCount = random_int(1, max(1, $maxParticipantsPerMass));
            $participants = Arr::random($userIds, $participantsCount);
            $participantIds = is_array($participants) ? $participants : [$participants];
            $massCelebrationAt = Carbon::parse($massRow->celebration_at);

            foreach ($participantIds as $userId) {
                $registeredAt = $this->determineRegistrationDate($massCelebrationAt, $now);

                $batch[] = [
                    'mass_id' => $massRow->id,
                    'user_id' => $userId,
                    'registered_at' => $registeredAt,
                    'created_at' => $registeredAt,
                    'updated_at' => $registeredAt,
                ];
            }

            $registrationsTotal += count($participantIds);
            $massesWithParticipants++;

            if (count($batch) >= 1000) {
                DB::table('mass_user')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('mass_user')->insertOrIgnore($batch);
        }

        $averagePerMass = $massesWithParticipants > 0
            ? number_format($registrationsTotal / $massesWithParticipants, 2, ',', ' ')
            : '0,00';

        return [
            'masses_with_participants' => $massesWithParticipants,
            'registrations_total' => $registrationsTotal,
            'average_per_mass' => $averagePerMass,
        ];
    }

    protected function determineRegistrationDate(Carbon $celebrationAt, Carbon $now): Carbon
    {
        $latest = $celebrationAt->copy()->subHour();

        if ($latest->gt($now)) {
            $latest = $now->copy();
        }

        $earliest = $latest->copy()->subDays(120);

        if ($earliest->gt($latest)) {
            $earliest = $latest->copy()->subDay();
        }

        return Carbon::instance(fake('pl_PL')->dateTimeBetween($earliest, $latest));
    }
}
