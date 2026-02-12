<?php

namespace Database\Seeders;

use App\Models\Parish;
use App\Models\User;
use Illuminate\Database\Seeder;

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
            'password' => bcrypt('Pwnaged1'),
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

        // 5 niezatwierdzonych parafian (różne parafie)
        foreach ($parishes->skip(2)->take(3) as $parish) {
            User::factory(2)
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
        // PODSUMOWANIE
        // =============================================

        $this->command->info('');
        $this->command->info('✅ Seeder zakończony pomyślnie!');
        $this->command->info('');
        $this->command->table(
            ['Typ', 'Ilość', 'Dane logowania'],
            [
                ['Parafie', $parishes->count(), '—'],
                ['SuperAdmin', '1', 'wspolnota@wspolnota.app / Pwnaged1'],
                ['Admin 1', '1', 'jan@wspolnota.app / password'],
                ['Admin 2', '1', 'piotr@wspolnota.app / password'],
                ['Parafianie (zatwierdzeni)', '10', 'losowe / password'],
                ['Parafianie (niezatwierdzeni)', '6', 'losowe / password'],
                ['Parafianie (bez weryfikacji email)', '1', 'losowe / password'],
            ]
        );
    }
}
