<?php

use App\Models\Parish;
use App\Models\User;
use App\Notifications\ApiResetPasswordNotification;
use App\Notifications\ApiVerifyEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('registers user and returns tokens payload', function (): void {
    Notification::fake();

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

    Notification::assertSentTo(
        User::query()->where('email', 'jan.kowalski@example.com')->firstOrFail(),
        ApiVerifyEmailNotification::class,
    );
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

    $rotatedAccess = $rotated->json('data.tokens.access_token');

    expect($rotated->json('data.tokens.refresh_token'))->not->toBe($firstRefresh);

    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $firstRefresh,
    ])->assertStatus(401)
        ->assertJsonPath('error.code', 'AUTH_REFRESH_REUSED');

    $this->withHeader('Authorization', "Bearer {$rotatedAccess}")
        ->getJson('/api/v1/me')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'AUTH_TOKEN_INVALID');
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

it('revokes current refresh token on logout without requiring it in payload', function (): void {
    $user = User::factory()->create([
        'email' => 'logout@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'login' => 'logout@example.com',
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'ios',
            'device_id' => 'device-logout-one',
            'device_name' => 'iPhone',
            'app_version' => '1.0.0',
        ],
    ])->assertOk();

    $accessToken = $loginResponse->json('data.tokens.access_token');
    $refreshToken = $loginResponse->json('data.tokens.refresh_token');

    $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('data.status', 'LOGGED_OUT');

    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
    ])->assertStatus(401)
        ->assertJsonPath('error.code', 'AUTH_REFRESH_REVOKED');
});

it('rejects inactive parishes during registration', function (): void {
    $parish = Parish::factory()->inactive()->create();

    $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'email' => 'inactive-parish@example.com',
        'password' => 'StrongPass#2026',
        'password_confirmation' => 'StrongPass#2026',
        'default_parish_id' => $parish->getKey(),
    ])->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('resends verification email for authenticated user and verifies email through signed api route', function (): void {
    Notification::fake();

    $user = User::factory()->unverifiedEmail()->create([
        'email' => 'verify-me@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'login' => 'verify-me@example.com',
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'android',
            'device_id' => 'device-verify-1234',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
        ],
    ])->assertOk();

    $accessToken = $loginResponse->json('data.tokens.access_token');

    $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->postJson('/api/v1/auth/email/verification-notification')
        ->assertOk()
        ->assertJsonPath('data.status', 'EMAIL_VERIFICATION_SENT');

    Notification::assertSentTo($user, ApiVerifyEmailNotification::class);

    $verificationUrl = URL::temporarySignedRoute(
        'api.v1.auth.verify-email',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->email),
        ],
    );

    $this->getJson($verificationUrl)
        ->assertOk()
        ->assertJsonPath('data.status', 'EMAIL_VERIFIED')
        ->assertJsonPath('data.user.is_email_verified', true);
});

it('uses api-friendly reset password notification links', function (): void {
    Notification::fake();

    config()->set('api_auth.mobile_password_reset_url', 'wspolnota://reset-password');

    $user = User::factory()->create([
        'email' => 'forgot@example.com',
        'status' => 'active',
    ]);

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ])->assertOk()
        ->assertJsonPath('data.status', 'PASSWORD_RESET_EMAIL_SENT_IF_EXISTS');

    Notification::assertSentTo($user, ApiResetPasswordNotification::class, function (ApiResetPasswordNotification $notification, array $channels, User $notifiable): bool {
        $mailMessage = $notification->toMail($notifiable);

        return str_contains((string) $mailMessage->actionUrl, 'wspolnota://reset-password')
            && str_contains((string) $mailMessage->actionUrl, 'email=');
    });
});
