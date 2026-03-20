<?php

use App\Models\Mass;

it('ignores withCount attributes when replicating and saving a mass', function (): void {
    $mass = Mass::factory()->create();

    $source = Mass::query()
        ->withCount('participants')
        ->findOrFail($mass->getKey());

    $clone = $source->replicate();
    $clone->celebration_at = $source->celebration_at?->copy()->addWeek();
    $clone->save();

    expect(Mass::count())->toBe(2);

    $persistedClone = Mass::query()
        ->latest('id')
        ->firstOrFail();

    expect($persistedClone->getAttributes())->not->toHaveKey('participants_count');
});
