<?php

namespace Database\Factories;

use App\Models\AnnouncementSet;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnnouncementSet>
 */
class AnnouncementSetFactory extends Factory
{
    protected $model = AnnouncementSet::class;

    /**
     * Tytuły zestawów ogłoszeń (niedziele roku liturgicznego)
     */
    protected array $titles = [
        'Ogłoszenia na I Niedzielę Adwentu',
        'Ogłoszenia na II Niedzielę Adwentu',
        'Ogłoszenia na III Niedzielę Adwentu',
        'Ogłoszenia na IV Niedzielę Adwentu',
        'Ogłoszenia na Niedzielę Chrztu Pańskiego',
        'Ogłoszenia na II Niedzielę Zwykłą',
        'Ogłoszenia na III Niedzielę Zwykłą',
        'Ogłoszenia na Niedzielę Palmową',
        'Ogłoszenia na Niedzielę Zmartwychwstania Pańskiego',
        'Ogłoszenia na II Niedzielę Wielkanocną',
        'Ogłoszenia na Niedzielę Zesłania Ducha Świętego',
        'Ogłoszenia na Uroczystość Najświętszej Trójcy',
        'Ogłoszenia na Uroczystość Bożego Ciała',
        'Ogłoszenia na XV Niedzielę Zwykłą',
        'Ogłoszenia na XVI Niedzielę Zwykłą',
        'Ogłoszenia na XVII Niedzielę Zwykłą',
        'Ogłoszenia na Uroczystość Wniebowzięcia NMP',
        'Ogłoszenia na XXIV Niedzielę Zwykłą',
        'Ogłoszenia na Uroczystość Wszystkich Świętych',
        'Ogłoszenia na Uroczystość Chrystusa Króla',
    ];

    public function definition(): array
    {
        $validFrom = fake()->dateTimeBetween('-1 month', '+1 month');
        $validUntil = (clone $validFrom)->modify('+6 days');

        return [
            'parish_id' => Parish::factory(),
            'title' => fake()->randomElement($this->titles),
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'ai_summary' => null,
            'ai_summary_generated_at' => null,
            'status' => fake()->randomElement(['draft', 'published', 'published', 'published']),
            'published_at' => function (array $attributes) {
                return $attributes['status'] === 'published' ? now() : null;
            },
            'created_by' => User::factory(),
            'published_by' => function (array $attributes) {
                return $attributes['status'] === 'published' ? $attributes['created_by'] : null;
            },
        ];
    }

    /**
     * Szkic (nieopublikowany)
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
            'published_by' => null,
        ]);
    }

    /**
     * Opublikowany
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Aktualnie obowiązujący (ten tydzień)
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now()->subDay(),
            'valid_from' => now()->startOfWeek(),
            'valid_until' => now()->endOfWeek(),
        ]);
    }

    /**
     * Przeszły (zarchiwizowany)
     */
    public function archived(): static
    {
        $validFrom = fake()->dateTimeBetween('-3 months', '-1 month');
        $validUntil = (clone $validFrom)->modify('+6 days');

        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
        ]);
    }
}
