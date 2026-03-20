<?php

use App\Models\Mass;
use App\Models\Parish;

it('requires explicit date range when listing masses', function (): void {
    $parish = Parish::factory()->create();

    $this->getJson('/api/v1/parishes/'.$parish->getKey().'/masses')
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('lists masses in chronological order within requested range', function (): void {
    $parish = Parish::factory()->create();

    Mass::factory()->create([
        'parish_id' => $parish->getKey(),
        'celebration_at' => now()->addDays(2),
    ]);

    Mass::factory()->create([
        'parish_id' => $parish->getKey(),
        'celebration_at' => now()->addDay(),
    ]);

    $response = $this->getJson('/api/v1/parishes/'.$parish->getKey().'/masses?from='
        .urlencode(now()->startOfDay()->toISOString())
        .'&to='
        .urlencode(now()->addDays(3)->endOfDay()->toISOString()));

    $response->assertOk();

    $first = $response->json('data.0.celebration_at');
    $second = $response->json('data.1.celebration_at');

    expect($first <= $second)->toBeTrue();
});

it('returns the five latest past masses for a parish', function (): void {
    $parish = Parish::factory()->create();

    foreach (range(1, 7) as $offset) {
        Mass::factory()->create([
            'parish_id' => $parish->getKey(),
            'celebration_at' => now()->subDays($offset),
            'status' => 'completed',
        ]);
    }

    $response = $this->getJson('/api/v1/parishes/'.$parish->getKey().'/masses/recent-past');

    $response
        ->assertOk()
        ->assertJsonCount(5, 'data.masses');

    $celebrationDates = collect($response->json('data.masses'))
        ->pluck('celebration_at')
        ->values()
        ->all();

    expect($celebrationDates)->toBe(array_values($celebrationDates))
        ->and($celebrationDates[0] >= $celebrationDates[1])->toBeTrue();
});

it('returns the ten nearest upcoming masses for a parish', function (): void {
    $parish = Parish::factory()->create();

    foreach (range(1, 12) as $offset) {
        Mass::factory()->create([
            'parish_id' => $parish->getKey(),
            'celebration_at' => now()->addHours($offset),
            'status' => 'scheduled',
        ]);
    }

    Mass::factory()->create([
        'parish_id' => $parish->getKey(),
        'celebration_at' => now()->addHours(2),
        'status' => 'cancelled',
    ]);

    $response = $this->getJson('/api/v1/parishes/'.$parish->getKey().'/masses/upcoming');

    $response
        ->assertOk()
        ->assertJsonCount(10, 'data.masses');

    $celebrationDates = collect($response->json('data.masses'))
        ->pluck('celebration_at')
        ->values()
        ->all();

    expect($celebrationDates[0] <= $celebrationDates[1])->toBeTrue()
        ->and(collect($response->json('data.masses'))->contains(fn (array $mass): bool => $mass['status'] === 'cancelled'))->toBeFalse();
});
