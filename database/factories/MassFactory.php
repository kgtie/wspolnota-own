<?php

namespace Database\Factories;

use App\Models\Mass;
use App\Models\Parish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mass>
 */
class MassFactory extends Factory
{
    protected $model = Mass::class;

    /**
     * Przykładowe intencje mszalne
     */
    protected array $intentions = [
        'Za śp. Jana Kowalskiego w 10. rocznicę śmierci',
        'Za śp. Marię Nowak w 1. rocznicę śmierci',
        'O zdrowie dla całej rodziny',
        'Dziękczynna za otrzymane łaski',
        'W intencji młodzieży przygotowującej się do bierzmowania',
        'Za dusze w czyśćcu cierpiące',
        'O powołania kapłańskie i zakonne',
        'Za chorych i cierpiących parafian',
        'W intencji misji świętych',
        'Dziękczynna z okazji 25. rocznicy ślubu',
        'O błogosławieństwo Boże dla dzieci zdających egzaminy',
        'Za śp. rodziców Annę i Stanisława',
        'W intencji parafii',
        'O pokój na świecie',
        'Za zmarłych z rodziny Wiśniewskich',
        'Dziękczynna za 50 lat małżeństwa',
        'O szczęśliwe rozwiązanie dla oczekującej potomstwa',
        'Za śp. sąsiadów i przyjaciół',
        'W intencji służby zdrowia',
        'O Boże błogosławieństwo dla nowożeńców',
    ];

    /**
     * Celebransi
     */
    protected array $celebrants = [
        'ks. Jan Kowalski',
        'ks. Piotr Nowak',
        'ks. Andrzej Wiśniewski',
        'ks. Tomasz Kamiński',
        'ks. Marek Lewandowski',
        null, // czasem nieznany
        null,
    ];

    public function definition(): array
    {
        $types = array_keys(Mass::getTypeOptions());
        $rites = array_keys(Mass::getRiteOptions());
        $locations = Mass::getLocationOptions() ?? [
            'Kościół główny',
            'Kaplica boczna', 
            'Kaplica cmentarna',
        ];

        return [
            'parish_id' => Parish::factory(),
            'start_time' => fake()->dateTimeBetween('-1 week', '+2 weeks')
                ->setTime(fake()->randomElement([7, 8, 9, 10, 11, 12, 17, 18, 19]), 0),
            'location' => fake()->randomElement($locations),
            'intention' => fake()->randomElement($this->intentions),
            'type' => fake()->randomElement($types),
            'rite' => fake()->randomElement($rites),
            'celebrant' => fake()->randomElement($this->celebrants),
            'stipend' => fake()->optional(0.7)->randomFloat(2, 20, 200),
        ];
    }

    /**
     * Msza nadchodząca (w przyszłości)
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => fake()->dateTimeBetween('now', '+2 weeks')
                ->setTime(fake()->randomElement([7, 8, 9, 10, 11, 12, 17, 18, 19]), 0),
        ]);
    }

    /**
     * Msza w przeszłości
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => fake()->dateTimeBetween('-2 months', '-1 day')
                ->setTime(fake()->randomElement([7, 8, 9, 10, 11, 12, 17, 18, 19]), 0),
        ]);
    }

    /**
     * Msza dzisiejsza
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => now()->setTime(fake()->randomElement([7, 8, 9, 10, 11, 12, 17, 18, 19]), 0),
        ]);
    }

    /**
     * Msza pogrzebowa
     */
    public function funeral(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pogrzebowa',
            'intention' => 'Za śp. ' . fake()->name() . ' - Msza św. pogrzebowa',
        ]);
    }

    /**
     * Msza ślubna
     */
    public function wedding(): static
    {
        $bride = fake()->firstNameFemale();
        $groom = fake()->firstNameMale();
        
        return $this->state(fn (array $attributes) => [
            'type' => 'ślubna',
            'intention' => "Ślub {$bride} i {$groom}",
        ]);
    }
}
