<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Parish;
use App\Models\MailingList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use Database\Seeders\MassSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $parishA = Parish::create([
            'name' => 'Parafia p.w. św. Stanisława BM w Wiskitkach',
            'short_name' => 'Parafia Wiskitki',
            'city' => 'Wiskitki',
            'slug' => 'wiskitki',
        ]);

        $parishB = Parish::create([
            'name' => 'Parafia p.w. św. Krakowiaków w Krakowie',
            'short_name' => 'Parafia Krakowiaków',
            'city' => 'Kraków',
            'slug' => 'krakowiakow',
        ]);

        $superAdmin = User::create([
            'name' => 'Konrad Gruza',
            'email' => 'konrad@wspolnota.app',
            'password' => Hash::make('Pwnaged1'),
            'email_verified_at' => now(),
            'current_parish_id' => '1',
            'role' => 2, // 2 = SuperAdmin
        ]);

        // 2. Tworzymy ADMINISTRATORA (Zarządca parafii)
        $adminOne = User::create([
            'name' => 'Witold Okrasa',
            'email' => 'jan@wspolnota.pl',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 1, // 1 = Admin
        ]);

        // 3. Tworzymy ZWYKŁEGO USERA
        $userOne = User::create([
            'name' => 'Anna Nowak (User)',
            'email' => 'anna@wspolnota.pl',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 0, // 0 = User
        ]);

        // Tworzę podstawową listę mailingową
        $mailingList = MailingList::create([
            'name' => 'Oczekujący na usługę',
        ]);

        // 5. RELACJE (Test Multi-tenancy)
        
        // Przypisz Admina Jana do OBU parafii
        // To testuje czy admin może mieć wiele parafii
        $adminOne->managedParishes()->attach([$parishA->id, $parishB->id]);
        $superAdmin->managedParishes()->attach([$parishA->id, $parishB->id]);
        
        // Ustaw domyślny kontekst dla Jana na Katedrę
        $adminOne->update(['current_parish_id' => $parishA->id]);

        // 6. Dogeneruj losowe dane (dla tłumu)
        // 10 losowych parafii
        Parish::factory(30)->create();
        
        // 50 losowych userów
        User::factory(1500)->create();
        
        // Opcjonalnie: Przypisz losowych adminów do losowych parafii
        $randomAdmins = User::factory(5)->create(['role' => 1]);
        foreach($randomAdmins as $admin) {
            $randomParish = Parish::inRandomOrder()->first();
            $admin->managedParishes()->attach($randomParish->id);
            $admin->update(['current_parish_id' => $randomParish->id]);
        }
        // Seeder dla MassSeeder
        $this->call([
            MassSeeder::class,
            MassesAndAnnouncementsSeeder::class,
        ]);
    }
}