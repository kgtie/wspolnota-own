<?php

namespace Database\Seeders;

use App\Models\Mass;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Database\Seeder;

class MassSeeder extends Seeder
{
    public function run(): void
    {
        $parishes = Parish::all();

        foreach ($parishes as $parish) {
            // Generuj msze na tydzień w tył i 2 tygodnie w przód
            for ($i = -7; $i <= 14; $i++) {
                $date = now()->addDays($i);
                
                // Rano
                Mass::create([
                    'parish_id' => $parish->id,
                    'start_time' => $date->copy()->setTime(7, 0),
                    'intention' => 'Za parafian',
                    'location' => 'Kościół główny',
                    'stipend' => 0,
                    'celebrant' => 'Proboszcz'
                ]);

                // Wieczorem
                $mass = Mass::create([
                    'parish_id' => $parish->id,
                    'start_time' => $date->copy()->setTime(18, 0),
                    'intention' => 'Za duszę św. p. ' . fake()->name(),
                    'location' => 'Kościół główny',
                    'stipend' => 100.00,
                ]);
                
                // Dodaj losowych zapisanych użytkowników
                $users = User::inRandomOrder()->take(rand(0, 5))->get();
                $mass->attendees()->attach($users);
            }
        }
    }
}