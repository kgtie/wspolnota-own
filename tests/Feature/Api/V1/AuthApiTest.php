<?php

use App\Models\Parish;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers user and returns tokens payload', function (): void {
    $parish = Parish::factory()->create();

    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'email' => 'jan.kowalski@example.com',
        'password' => 'StrongPass#2026',
        'password_confirmation' => 'StrongPass#2026',
        'default_parish_id' => $parish->getKey(),
        'device' => [
            'platform' => 'ios',
            'device_id' => 'device-12345678',
            'device_name' => 'iPhone',
            'app_version' => '1.0.0',
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.user.email', 'jan.kowalski@example.com')
        ->assertJsonPath('data.tokens.token_type', 'Bearer')
        ->assertJsonPath('data.tokens.access_expires_in', 900)
        ->assertJsonPath('data.tokens.refresh_expires_in', 2592000)
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'first_name', 'last_name', 'email', 'is_parish_approved'],
                'tokens' => ['access_token', 'refresh_token'],
            ],
        ]);

    expect(User::query()->where('email', 'jan.kowalski@example.com')->exists())->toBeTrue();
});

it('logs in with email and username', function (): void {
    $user = User::factory()->create([
        'name' => 'jan.kowalski',
        'email' => 'jan.login@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $payload = [
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'android',
            'device_id' => 'device-login-1234',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
        ],
    ];

    $this->postJson('/api/v1/auth/login', array_merge($payload, ['login' => $user->email]))
        ->assertOk()
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonPath('data.tokens.token_type', 'Bearer');

    $this->postJson('/api/v1/auth/login', array_merge($payload, ['login' => $user->name]))
        ->assertOk()
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonPath('data.tokens.token_type', 'Bearer');
});

it('rotates refresh token and rejects reused refresh token', function (): void {
    $user = User::factory()->create([
        'email' => 'refresh@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'login' => 'refresh@example.com',
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'android',
            'device_id' => 'device-refresh-1111',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
        ],
    ])->assertOk();

    $firstRefresh = $loginResponse->json('data.tokens.refresh_token');

    $rotated = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $firstRefresh,
    ])->assertOk();

    expect($rotated->json('data.tokens.refresh_token'))->not->toBe($firstRefresh);

    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $firstRefresh,
    ])->assertStatus(401)
        ->assertJsonPath('error.code', 'AUTH_REFRESH_REUSED');
});

it('logs out all sessions only with valid password', function (): void {
    $user = User::factory()->create([
        'email' => 'logout-all@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'login' => 'logout-all@example.com',
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'ios',
            'device_id' => 'device-logout-all',
            'device_name' => 'iPhone',
            'app_version' => '1.0.0',
        ],
    ])->assertOk();

    $accessToken = $loginResponse->json('data.tokens.access_token');

    $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->postJson('/api/v1/auth/logout-all', [
            'password' => 'wrong-pass',
        ])
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'AUTH_PASSWORD_INVALID');

    $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->postJson('/api/v1/auth/logout-all', [
            'password' => 'Secret#2026',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'LOGGED_OUT_ALL');
});
