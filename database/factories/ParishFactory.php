<?php

namespace Database\Factories;

use App\Models\Parish;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ParishFactory extends Factory
{
    protected $model = Parish::class;

    /**
     * Polskie dane parafii do seedowania
     */
    private static array $parishes = [
        [
            'name' => 'Parafia p.w. św. Stanisława biskupa i męczennika',
            'short_name' => 'Parafia Wiskitki',
            'city' => 'Wiskitki',
            'diocese' => 'Diecezja Łowicka',
            'decanate' => 'Dekanat Wiskitki',
        ],
        [
            'name' => 'Parafia p.w. Wniebowzięcia Najświętszej Maryi Panny',
            'short_name' => 'Parafia Żyrardów',
            'city' => 'Żyrardów',
            'diocese' => 'Diecezja Łowicka',
            'decanate' => 'Dekanat Żyrardów',
        ],
        [
            'name' => 'Parafia p.w. św. Jana Chrzciciela',
            'short_name' => 'Parafia Mszczonów',
            'city' => 'Mszczonów',
            'diocese' => 'Diecezja Łowicka',
            'decanate' => 'Dekanat Mszczonów',
        ],
        [
            'name' => 'Parafia p.w. Matki Bożej Częstochowskiej',
            'short_name' => 'Parafia Grodzisk Mazowiecki',
            'city' => 'Grodzisk Mazowiecki',
            'diocese' => 'Archidiecezja Warszawska',
            'decanate' => 'Dekanat Grodzisk Mazowiecki',
        ],
        [
            'name' => 'Parafia p.w. św. Andrzeja Apostoła',
            'short_name' => 'Parafia Radziejowice',
            'city' => 'Radziejowice',
            'diocese' => 'Diecezja Łowicka',
            'decanate' => 'Dekanat Wiskitki',
        ],
    ];

    public function definition(): array
    {
        $city = fake('pl_PL')->city();
        $slug = Str::slug($city);

        return [
            'name' => 'Parafia p.w. ' . fake('pl_PL')->randomElement([
                'św. Jana Chrzciciela',
                'św. Stanisława biskupa i męczennika',
                'Wniebowzięcia NMP',
                'Matki Bożej Częstochowskiej',
                'św. Andrzeja Apostoła',
                'św. Wojciecha',
                'Najświętszego Serca Pana Jezusa',
                'św. Floriana',
            ]),
            'short_name' => 'Parafia ' . $city,
            'slug' => $slug . '-' . fake()->unique()->numberBetween(1, 9999),
            'email' => 'parafia@' . $slug . '.pl',
            'phone' => fake('pl_PL')->phoneNumber(),
            'website' => 'https://www.parafia-' . $slug . '.pl',
            'street' => fake('pl_PL')->streetAddress(),
            'postal_code' => fake('pl_PL')->postcode(),
            'city' => $city,
            'diocese' => fake()->randomElement([
                'Diecezja Łowicka',
                'Archidiecezja Warszawska',
                'Diecezja Płocka',
                'Diecezja Radomska',
            ]),
            'decanate' => 'Dekanat ' . $city,
            'is_active' => true,
            'activated_at' => now(),
            'settings' => null,
        ];
    }

    /**
     * Stan: nieaktywna parafia
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Tworzy jedną z predefiniowanych parafii
     */
    public function predefined(int $index): static
    {
        $data = self::$parishes[$index] ?? self::$parishes[0];

        return $this->state(fn (array $attributes) => [
            'name' => $data['name'],
            'short_name' => $data['short_name'],
            'slug' => Str::slug($data['city']),
            'city' => $data['city'],
            'diocese' => $data['diocese'],
            'decanate' => $data['decanate'],
            'email' => 'parafia@' . Str::slug($data['city']) . '.pl',
        ]);
    }
}
