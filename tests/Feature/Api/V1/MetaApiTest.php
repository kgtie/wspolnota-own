<?php

use Illuminate\Support\Facades\DB;

it('returns current service version from global settings', function (): void {
    DB::table('settings')->updateOrInsert(
        ['group' => 'general', 'name' => 'service_version'],
        [
            'payload' => json_encode('0.9', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'locked' => false,
        ]
    );

    $this->getJson('/api/v1/meta/service-version')
        ->assertOk()
        ->assertExactJson([
            'service_version' => '0.9',
        ]);
});
