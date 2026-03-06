<?php

use App\Models\Parish;
use App\Models\User;
use App\Notifications\ApiVerifyEmailNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

it('returns api not found payload for missing routes', function (): void {
    $this->getJson('/api/v1/not-existing-endpoint')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('changes default parish and resets parish approval with a new code', function (): void {
    $oldParish = Parish::factory()->create();
    $newParish = Parish::factory()->create();

    $user = User::factory()->verified()->create([
        'email' => 'parish-change@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
        'home_parish_id' => $oldParish->getKey(),
        'verification_code' => '123456789',
    ]);

    $tokens = loginForApi($user);

    $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->patchJson('/api/v1/me', [
            'default_parish_id' => $newParish->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.user.default_parish_id', (string) $newParish->getKey())
        ->assertJsonPath('data.user.is_parish_approved', false)
        ->assertJsonPath('data.user.parish_approval_code', fn ($value) => is_string($value) && strlen($value) === 9 && $value !== '123456789');
});

it('changes email, clears verification and sends new verification notification', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'before-change@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $tokens = loginForApi($user);

    $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->patchJson('/api/v1/me/email', [
            'email' => 'after-change@example.com',
            'current_password' => 'Secret#2026',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'EMAIL_UPDATED_VERIFICATION_REQUIRED')
        ->assertJsonPath('data.user.email', 'after-change@example.com')
        ->assertJsonPath('data.user.is_email_verified', false)
        ->assertJsonPath('data.requires_email_verification', true);

    Notification::assertSentTo(
        User::query()->where('email', 'after-change@example.com')->firstOrFail(),
        ApiVerifyEmailNotification::class,
    );
});

it('changes password and revokes current api session', function (): void {
    $user = User::factory()->create([
        'email' => 'password-change@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $tokens = loginForApi($user);

    $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->patchJson('/api/v1/me/password', [
            'current_password' => 'Secret#2026',
            'password' => 'EvenStronger#2027',
            'password_confirmation' => 'EvenStronger#2027',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'PASSWORD_CHANGED');

    $this->withHeader('Authorization', 'Bearer '.$tokens['access'])
        ->getJson('/api/v1/me')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'AUTH_TOKEN_INVALID');

    $this->postJson('/api/v1/auth/login', [
        'login' => 'password-change@example.com',
        'password' => 'EvenStronger#2027',
        'device' => [
            'platform' => 'android',
            'device_id' => 'device-password-change',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
        ],
    ])->assertOk();
});
