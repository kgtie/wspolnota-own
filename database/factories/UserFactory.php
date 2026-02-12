<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Domyślne hasło dla seedów
     */
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake('pl_PL')->userName(),
            'full_name' => fake('pl_PL')->name(),
            'email' => fake('pl_PL')->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 0,
            'status' => 'active',
            'home_parish_id' => null,
            'verification_code' => str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'is_user_verified' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Bez weryfikacji emaila
     */
    public function unverifiedEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Zatwierdzony przez proboszcza
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_user_verified' => true,
            'user_verified_at' => now(),
        ]);
    }

    /**
     * Administrator (rola = 1)
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 1,
            'is_user_verified' => true,
            'user_verified_at' => now(),
        ]);
    }

    /**
     * SuperAdmin (rola = 2)
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 2,
            'is_user_verified' => true,
            'user_verified_at' => now(),
        ]);
    }
}
