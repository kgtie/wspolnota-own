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
