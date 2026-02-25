<?php

namespace Database\Factories;

use App\Models\Mass;
use App\Models\Parish;
use Illuminate\Database\Eloquent\Factories\Factory;

class MassFactory extends Factory
{
    protected $model = Mass::class;

    public function definition(): array
    {
        $celebrationAt = fake('pl_PL')->dateTimeBetween('-8 months', '+8 months');
        $stipendium = fake('pl_PL')->boolean(65)
            ? fake('pl_PL')->randomFloat(2, 50, 600)
            : null;

        $status = $celebrationAt < now()->subHour()
            ? fake('pl_PL')->randomElement(['completed', 'completed', 'completed', 'cancelled'])
            : 'scheduled';

        return [
            'parish_id' => Parish::factory(),
            'intention_title' => fake('pl_PL')->randomElement([
                'Za parafian',
                'O zdrowie i Boze blogoslawienstwo dla rodziny',
                'Dziekczynna za otrzymane laski',
                'W intencji malzonkow',
                'Za sp. Jana i Marie',
                'Za sp. rodzicow i dziadkow',
            ]),
            'intention_details' => fake('pl_PL')->boolean(40)
                ? fake('pl_PL')->sentence(14)
                : null,
            'celebration_at' => $celebrationAt,
            'stipendium_amount' => $stipendium,
            'stipendium_paid_at' => $stipendium !== null && fake('pl_PL')->boolean(70)
                ? fake('pl_PL')->dateTimeBetween('-10 months', $celebrationAt)
                : null,
            'mass_kind' => fake('pl_PL')->randomElement(array_keys(Mass::getMassKindOptions())),
            'mass_type' => fake('pl_PL')->randomElement(array_keys(Mass::getMassTypeOptions())),
            'status' => $status,
            'celebrant_name' => fake('pl_PL')->boolean(85) ? 'ks. '.fake('pl_PL')->name() : null,
            'location' => fake('pl_PL')->randomElement([
                'Kosciol parafialny',
                'Kaplica sw. Jozefa',
                'Kaplica adoracji',
            ]),
            'notes' => fake('pl_PL')->boolean(25) ? fake('pl_PL')->sentence(18) : null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }
}
