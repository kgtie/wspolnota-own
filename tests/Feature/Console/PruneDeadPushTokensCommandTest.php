<?php

use App\Models\User;
use App\Models\UserDevice;

it('prunes only stale dead push tokens', function (): void {
    $user = User::factory()->create();

    $staleDeadDevice = UserDevice::query()->create([
        'user_id' => $user->getKey(),
        'provider' => 'fcm',
        'platform' => 'android',
        'push_token' => 'dead-token-1',
        'device_id' => 'dead-device-1',
        'device_name' => 'Pixel 1',
        'app_version' => '1.0.0',
        'permission_status' => 'authorized',
        'last_push_error_at' => now()->subDays(2),
        'last_push_error' => 'UNREGISTERED | Requested entity was not found.',
        'disabled_at' => now()->subDays(2),
        'last_seen_at' => now()->subDays(2),
    ]);

    $recentDeadDevice = UserDevice::query()->create([
        'user_id' => $user->getKey(),
        'provider' => 'fcm',
        'platform' => 'ios',
        'push_token' => 'dead-token-2',
        'device_id' => 'dead-device-2',
        'device_name' => 'iPhone 1',
        'app_version' => '1.0.0',
        'permission_status' => 'authorized',
        'last_push_error_at' => now()->subHours(2),
        'last_push_error' => 'INVALID_ARGUMENT | The registration token is not a valid FCM registration token',
        'disabled_at' => now()->subHours(2),
        'last_seen_at' => now()->subHours(2),
    ]);

    $healthyDevice = UserDevice::query()->create([
        'user_id' => $user->getKey(),
        'provider' => 'fcm',
        'platform' => 'android',
        'push_token' => 'healthy-token-1',
        'device_id' => 'healthy-device-1',
        'device_name' => 'Pixel 2',
        'app_version' => '1.0.0',
        'permission_status' => 'authorized',
        'last_seen_at' => now(),
    ]);

    $this->artisan('push:prune-dead-tokens', [
        '--dry-run' => true,
        '--invalid-hours' => 24,
    ])->assertSuccessful();

    expect(UserDevice::query()->count())->toBe(3);

    $this->artisan('push:prune-dead-tokens', [
        '--invalid-hours' => 24,
    ])->assertSuccessful();

    expect(UserDevice::query()->whereKey($staleDeadDevice->getKey())->exists())->toBeFalse()
        ->and(UserDevice::query()->whereKey($recentDeadDevice->getKey())->exists())->toBeTrue()
        ->and(UserDevice::query()->whereKey($healthyDevice->getKey())->exists())->toBeTrue();
});
