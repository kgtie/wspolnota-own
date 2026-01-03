<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\Parish;
use Illuminate\Database\Seeder;

/**
 * MassesAndAnnouncementsSeeder
 * 
 * Wypełnia bazę danych przykładowymi mszami i ogłoszeniami.
 * Używany w środowisku developerskim.
 */
class MassesAndAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        // Pobierz istniejące parafie lub stwórz testowe
        $parishes = Parish::all();

        if ($parishes->isEmpty()) {
            $this->command->warn('Brak parafii w bazie danych. Uruchom najpierw ParishSeeder.');
            return;
        }

        foreach ($parishes as $parish) {
            $this->command->info("Generowanie danych dla parafii: {$parish->name}");

            // === MSZE ŚWIĘTE ===
            
            // Msze z przeszłości (dla historii)
            Mass::factory()
                ->count(20)
                ->past()
                ->create(['parish_id' => $parish->id]);

            // Msze na dziś
            Mass::factory()
                ->count(3)
                ->today()
                ->create(['parish_id' => $parish->id]);

            // Msze nadchodzące
            Mass::factory()
                ->count(30)
                ->upcoming()
                ->create(['parish_id' => $parish->id]);

            // Kilka mszy specjalnych
            Mass::factory()
                ->funeral()
                ->upcoming()
                ->create(['parish_id' => $parish->id]);

            Mass::factory()
                ->wedding()
                ->upcoming()
                ->create(['parish_id' => $parish->id]);

            // === OGŁOSZENIA ===

            // Aktualny zestaw ogłoszeń (na ten tydzień)
            $currentSet = AnnouncementSet::factory()
                ->current()
                ->create([
                    'parish_id' => $parish->id,
                    'title' => 'Ogłoszenia na ' . now()->translatedFormat('j F Y'),
                ]);

            // 5-8 ogłoszeń w aktualnym zestawie
            for ($i = 0; $i < fake()->numberBetween(5, 8); $i++) {
                Announcement::factory()->create([
                    'announcement_set_id' => $currentSet->id,
                    'sort_order' => $i,
                    'is_highlighted' => $i === 0, // pierwsze jest wyróżnione
                ]);
            }

            // Kilka przeszłych zestawów ogłoszeń
            for ($week = 1; $week <= 4; $week++) {
                $pastSet = AnnouncementSet::factory()
                    ->archived()
                    ->create([
                        'parish_id' => $parish->id,
                        'valid_from' => now()->subWeeks($week)->startOfWeek(),
                        'valid_until' => now()->subWeeks($week)->endOfWeek(),
                    ]);

                // 4-6 ogłoszeń w każdym zestawie
                for ($i = 0; $i < fake()->numberBetween(4, 6); $i++) {
                    Announcement::factory()->create([
                        'announcement_set_id' => $pastSet->id,
                        'sort_order' => $i,
                    ]);
                }
            }

            // Jeden szkic (przygotowywany na następny tydzień)
            $draftSet = AnnouncementSet::factory()
                ->draft()
                ->create([
                    'parish_id' => $parish->id,
                    'valid_from' => now()->addWeek()->startOfWeek(),
                    'valid_until' => now()->addWeek()->endOfWeek(),
                    'title' => 'Ogłoszenia na następną niedzielę (SZKIC)',
                ]);

            // 2-3 ogłoszenia w szkicu
            for ($i = 0; $i < fake()->numberBetween(2, 3); $i++) {
                Announcement::factory()->create([
                    'announcement_set_id' => $draftSet->id,
                    'sort_order' => $i,
                ]);
            }
        }

        $this->command->info('Wygenerowano msze i ogłoszenia dla ' . $parishes->count() . ' parafii.');
    }
}
