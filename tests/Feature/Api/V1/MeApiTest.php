<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function loginForApi(User $user): array
{
    $response = test()->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'android',
            'device_id' => 'device-me-tests',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
        ],
    ])->assertOk();

    return [
        'access' => $response->json('data.tokens.access_token'),
        'refresh' => $response->json('data.tokens.refresh_token'),
    ];
}

it('returns AUTH_UNAUTHENTICATED on /me without token', function (): void {
    $this->getJson('/api/v1/me')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'AUTH_UNAUTHENTICATED');
});

it('returns current user profile', function (): void {
    $user = User::factory()->create([
        'email' => 'me@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $tokens = loginForApi($user);

    $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', 'me@example.com');
});

it('registers and removes device', function (): void {
    $user = User::factory()->create([
        'email' => 'devices@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $tokens = loginForApi($user);

    $store = $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->postJson('/api/v1/me/devices', [
            'provider' => 'fcm',
            'platform' => 'android',
            'push_token' => 'push-token-xyz',
            'device_id' => 'device-abc-123456',
            'device_name' => 'Pixel 9',
            'app_version' => '1.0.0',
            'locale' => 'pl-PL',
            'timezone' => 'Europe/Warsaw',
        ])
        ->assertOk();

    $deviceId = $store->json('data.id');

    $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->deleteJson('/api/v1/me/devices/'.$deviceId)
        ->assertNoContent();
});

it('updates notification preferences and marks notification as read', function (): void {
    $user = User::factory()->create([
        'email' => 'notify@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $tokens = loginForApi($user);

    $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->patchJson('/api/v1/me/notification-preferences', [
            'news' => ['push' => true, 'email' => false],
            'announcements' => ['push' => true, 'email' => true],
            'office_messages' => ['push' => true, 'email' => true],
            'parish_approval_status' => ['push' => true, 'email' => true],
            'auth_security' => ['push' => false, 'email' => true],
        ])
        ->assertOk()
        ->assertJsonPath('data.updated', true);

    $id = (string) Str::uuid();

    DB::table('notifications')->insert([
        'id' => $id,
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->getKey(),
        'data' => json_encode([
            'type' => 'OFFICE_MESSAGE_RECEIVED',
            'title' => 'Nowa wiadomość',
            'body' => 'Masz nową wiadomość.',
            'data' => ['chat_id' => '1'],
        ], JSON_THROW_ON_ERROR),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->getJson('/api/v1/me/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.id', $id)
        ->assertJsonPath('data.0.type', 'OFFICE_MESSAGE_RECEIVED');

    $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->postJson('/api/v1/me/notifications/'.$id.'/read')
        ->assertOk()
        ->assertJsonPath('data.id', $id)
        ->assertJsonPath('data.read_at', fn ($value) => is_string($value) && $value !== '');
});
