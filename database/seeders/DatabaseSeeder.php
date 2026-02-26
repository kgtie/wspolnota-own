<?php

namespace Database\Seeders;

use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\NewsPost;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Tworzy realistyczny zestaw danych developerskich:
     * - 5 predefiniowanych parafii (polskie)
     * - 1 SuperAdmin z dostępem do wszystkich parafii
     * - 2 Administratorów, każdy z 1-2 parafiami
     * - 20 parafian rozdzielonych między parafie
     */
    public function run(): void
    {
        // =============================================
        // 1. PARAFIE
        // =============================================

        $parishes = collect();

        // 5 predefiniowanych parafii z realistycznymi danymi
        for ($i = 0; $i < 5; $i++) {
            $parishes->push(
                Parish::factory()->predefined($i)->create()
            );
        }

        // =============================================
        // 2. SUPERADMIN
        // =============================================

        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'superadmin',
            'full_name' => 'Super Administrator',
            'email' => 'konrad@wspolnota.app',
            'home_parish_id' => $parishes->first()->id,
            'password' => Hash::make('Pwnaged1'),
        ]);

        // SuperAdmin ma dostęp do WSZYSTKICH parafii
        foreach ($parishes as $parish) {
            $superAdmin->managedParishes()->attach($parish->id, [
                'is_active' => true,
                'assigned_at' => now(),
                'note' => 'SuperAdmin — pełny dostęp',
            ]);
        }

        $superAdmin->update(['last_managed_parish_id' => $parishes->first()->id]);

        // =============================================
        // 3. ADMINISTRATORZY (proboszczowie)
        // =============================================

        // Admin 1: zarządza parafiami Wiskitki i Radziejowice
        $admin1 = User::factory()->admin()->create([
            'name' => 'ks.jan',
            'full_name' => 'ks. Jan Kowalski',
            'email' => 'jan@wspolnota.app',
            'home_parish_id' => $parishes[0]->id,
            'last_managed_parish_id' => $parishes[0]->id,
        ]);

        $admin1->managedParishes()->attach($parishes[0]->id, [
            'is_active' => true,
            'assigned_at' => now(),
            'note' => 'Proboszcz',
        ]);
        $admin1->managedParishes()->attach($parishes[4]->id, [
            'is_active' => true,
            'assigned_at' => now(),
            'note' => 'Administrator pomocniczy',
        ]);

        // Admin 2: zarządza parafią Żyrardów
        $admin2 = User::factory()->admin()->create([
            'name' => 'ks.piotr',
            'full_name' => 'ks. Piotr Nowak',
            'email' => 'piotr@wspolnota.app',
            'home_parish_id' => $parishes[1]->id,
            'last_managed_parish_id' => $parishes[1]->id,
        ]);

        $admin2->managedParishes()->attach($parishes[1]->id, [
            'is_active' => true,
            'assigned_at' => now(),
            'note' => 'Proboszcz',
        ]);

        // =============================================
        // 4. PARAFIANIE
        // =============================================

        // 5 zatwierdzonych parafian per parafia (pierwsze 2 parafie)
        foreach ($parishes->take(2) as $parish) {
            User::factory(5)
                ->verified()
                ->create([
                    'home_parish_id' => $parish->id,
                ]);
        }

        // 9 niezatwierdzonych parafian (3 parafie po 3 osoby)
        foreach ($parishes->skip(2)->take(3) as $parish) {
            User::factory(3)
                ->create([
                    'home_parish_id' => $parish->id,
                ]);
        }

        // 1 parafianin bez zweryfikowanego emaila
        User::factory()
            ->unverifiedEmail()
            ->create([
                'home_parish_id' => $parishes[0]->id,
            ]);

        // =============================================
        // 5. MSZE SWIETE, INTENCJE I OGLOSZENIA
        // =============================================

        $this->call(MassSeeder::class);
        $this->call(AnnouncementSeeder::class);
        $this->call(NewsPostSeeder::class);

        $massesCount = Mass::query()->count();
        $announcementSetsCount = AnnouncementSet::query()->count();
        $announcementItemsCount = (int) DB::table('announcement_items')->count();
        $newsPostsCount = NewsPost::query()->count();

        $adminsCount = User::query()->where('role', 1)->count();
        $verifiedParishionersCount = User::query()
            ->where('role', 0)
            ->where('is_user_verified', true)
            ->count();
        $pendingParishionersCount = User::query()
            ->where('role', 0)
            ->where('is_user_verified', false)
            ->whereNotNull('email_verified_at')
            ->count();
        $withoutEmailVerificationCount = User::query()
            ->where('role', 0)
            ->whereNull('email_verified_at')
            ->count();

        $pastMassesCount = Mass::query()->where('celebration_at', '<', now())->count();
        $futureMassesCount = Mass::query()->where('celebration_at', '>=', now())->count();
        $completedMassesCount = Mass::query()->where('status', 'completed')->count();
        $scheduledMassesCount = Mass::query()->where('status', 'scheduled')->count();
        $cancelledMassesCount = Mass::query()->where('status', 'cancelled')->count();
        $stipendiumCount = Mass::query()->whereNotNull('stipendium_amount')->count();
        $outstandingStipendiumCount = Mass::query()
            ->whereNotNull('stipendium_amount')
            ->whereNull('stipendium_paid_at')
            ->where('status', '!=', 'cancelled')
            ->count();
        $massesWithParticipantsCount = Mass::query()
            ->has('participants')
            ->count();
        $massRegistrationsCount = (int) DB::table('mass_user')->count();
        $publishedAnnouncementSetsCount = AnnouncementSet::query()->where('status', 'published')->count();
        $draftAnnouncementSetsCount = AnnouncementSet::query()->where('status', 'draft')->count();
        $archivedAnnouncementSetsCount = AnnouncementSet::query()->where('status', 'archived')->count();
        $importantAnnouncementItemsCount = (int) DB::table('announcement_items')
            ->where('is_important', true)
            ->count();
        $publishedNewsPostsCount = NewsPost::query()->where('status', 'published')->count();
        $draftNewsPostsCount = NewsPost::query()->where('status', 'draft')->count();
        $scheduledNewsPostsCount = NewsPost::query()->where('status', 'scheduled')->count();
        $archivedNewsPostsCount = NewsPost::query()->where('status', 'archived')->count();
        $pinnedNewsPostsCount = NewsPost::query()->where('is_pinned', true)->count();

        // =============================================
        // PODSUMOWANIE
        // =============================================

        $this->command?->info('');
        $this->command?->info('✅ Seeder zakonczony pomyslnie!');
        $this->command?->info('');
        $this->command?->table(
            ['Typ', 'Ilość', 'Dane logowania'],
            [
                ['Parafie', $parishes->count(), '—'],
                ['SuperAdmin', '1', $superAdmin->email.' / Pwnaged1'],
                ['Administratorzy', (string) $adminsCount, 'jan@wspolnota.app / password; piotr@wspolnota.app / password'],
                ['Parafianie (zatwierdzeni)', (string) $verifiedParishionersCount, 'losowe / password'],
                ['Parafianie (oczekujacy)', (string) $pendingParishionersCount, 'losowe / password'],
                ['Parafianie (bez weryfikacji email)', (string) $withoutEmailVerificationCount, 'losowe / password'],
                ['Msze swiete + intencje', (string) $massesCount, 'historia + przyszle terminy'],
                ['Zestawy ogloszen', (string) $announcementSetsCount, 'przeszle + przyszle tygodnie'],
                ['Pojedyncze ogloszenia', (string) $announcementItemsCount, 'z pozycjonowaniem i waznoscia'],
                ['Aktualnosci parafialne', (string) $newsPostsCount, 'blog parafialny: szkice, publikacje, harmonogram'],
            ]
        );

        $this->command?->table(
            ['Metryki liturgiczne', 'Wartosc'],
            [
                ['Msze przeszle', (string) $pastMassesCount],
                ['Msze przyszle', (string) $futureMassesCount],
                ['Status: odprawione', (string) $completedMassesCount],
                ['Status: zaplanowane', (string) $scheduledMassesCount],
                ['Status: odwolane', (string) $cancelledMassesCount],
                ['Msze ze stypendium', (string) $stipendiumCount],
                ['Nierozliczone stypendia', (string) $outstandingStipendiumCount],
                ['Msze z uczestnikami', (string) $massesWithParticipantsCount],
                ['Liczba zapisow uczestnikow', (string) $massRegistrationsCount],
                ['Zestawy ogloszen: opublikowane', (string) $publishedAnnouncementSetsCount],
                ['Zestawy ogloszen: szkice', (string) $draftAnnouncementSetsCount],
                ['Zestawy ogloszen: archiwalne', (string) $archivedAnnouncementSetsCount],
                ['Ogloszenia oznaczone jako wazne', (string) $importantAnnouncementItemsCount],
                ['Aktualnosci: opublikowane', (string) $publishedNewsPostsCount],
                ['Aktualnosci: szkice', (string) $draftNewsPostsCount],
                ['Aktualnosci: zaplanowane', (string) $scheduledNewsPostsCount],
                ['Aktualnosci: archiwalne', (string) $archivedNewsPostsCount],
                ['Aktualnosci przypiete', (string) $pinnedNewsPostsCount],
            ]
        );
    }
}
