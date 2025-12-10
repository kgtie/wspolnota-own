<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Parish>
 */
class ParishFactory extends Factory
{
    public function definition(): array
    {
        $city = fake()->city();
        $name = 'Parafia pw. św. ' . fake()->firstName();
        
        return [
            'name' => $name,
            'city' => $city,
            'short_name' => 'Parafia ' . fake()->lastName(),
            'slug' => Str::slug($name . '-' . $city),
            'is_active' => false,
        ];
    }
}
