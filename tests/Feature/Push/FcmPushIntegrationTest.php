<?php

use App\Contracts\PushSender;
use App\Models\Parish;
use App\Models\PushDelivery;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserNotificationPreference;
use App\Notifications\ParishApprovalStatusChangedNotification;
use App\Settings\FcmSettings;
use App\Support\Push\PushMessage;
use App\Support\Push\PushSendResult;
use Illuminate\Support\Facades\Hash;

function loginForPushApi(User $user): string
{
    return test()->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'android',
            'device_id' => 'device-push-tests',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
        ],
    ])->assertOk()->json('data.tokens.access_token');
}

it('stores extended device metadata for fcm registration', function (): void {
    $parish = Parish::factory()->create();

    $user = User::factory()->create([
        'email' => 'push-device@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
    ]);

    $accessToken = loginForPushApi($user);

    $this->withHeader('Authorization', 'Bearer '.$accessToken)
        ->postJson('/api/v1/me/devices', [
            'provider' => 'fcm',
            'platform' => 'ios',
            'push_token' => 'push-token-fcm-123',
            'device_id' => 'ios-device-123456',
            'device_name' => 'iPhone 16',
            'app_version' => '2.0.0',
            'locale' => 'pl-PL',
            'timezone' => 'Europe/Warsaw',
            'permission_status' => 'authorized',
            'parish_id' => $parish->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.provider', 'fcm')
        ->assertJsonPath('data.platform', 'ios')
        ->assertJsonPath('data.permission_status', 'authorized')
        ->assertJsonPath('data.parish_id', (string) $parish->getKey());

    expect(UserDevice::query()->where('user_id', $user->getKey())->first())
        ->not->toBeNull()
        ->and(UserDevice::query()->where('user_id', $user->getKey())->first()->permission_status)
        ->toBe('authorized');
});

it('dispatches push after database notification is stored', function (): void {
    config()->set('queue.default', 'sync');

    app(FcmSettings::class)->fill([
        'enabled' => true,
        'project_id' => 'firebase-test-project',
        'service_account_json' => json_encode([
            'project_id' => 'firebase-test-project',
            'client_email' => 'firebase-adminsdk@example.test',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nTEST\n-----END PRIVATE KEY-----\n",
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], JSON_UNESCAPED_SLASHES),
        'request_timeout_seconds' => 5,
        'android_ttl_seconds' => 3600,
        'ios_ttl_seconds' => 3600,
        'news_collapsible' => true,
        'announcements_collapsible' => true,
        'office_messages_collapsible' => false,
        'parish_approval_collapsible' => false,
    ])->save();

    app()->instance(PushSender::class, new class implements PushSender
    {
        public function send(PushMessage $message, bool $validateOnly = false): PushSendResult
        {
            return PushSendResult::success('projects/firebase-test-project/messages/123', [
                'name' => 'projects/firebase-test-project/messages/123',
                'type' => $message->type,
            ]);
        }
    });

    $user = User::factory()->verified()->create([
        'email' => 'push-notification@example.com',
        'status' => 'active',
    ]);

    UserNotificationPreference::query()->create([
        'user_id' => $user->getKey(),
        'news_push' => true,
        'news_email' => false,
        'announcements_push' => true,
        'announcements_email' => false,
        'office_messages_push' => true,
        'office_messages_email' => false,
        'parish_approval_status_push' => true,
        'parish_approval_status_email' => false,
        'auth_security_push' => false,
        'auth_security_email' => false,
    ]);

    UserDevice::query()->create([
        'user_id' => $user->getKey(),
        'provider' => 'fcm',
        'platform' => 'android',
        'push_token' => 'device-token-xyz',
        'device_id' => 'device-xyz-123456',
        'device_name' => 'Pixel 9',
        'app_version' => '1.0.0',
        'permission_status' => 'authorized',
        'push_token_updated_at' => now(),
        'last_seen_at' => now(),
    ]);

    $user->notify(new ParishApprovalStatusChangedNotification(true));

    $delivery = PushDelivery::query()->latest('id')->first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe(PushDelivery::STATUS_SENT)
        ->and($delivery->type)->toBe('PARISH_APPROVAL_STATUS_CHANGED')
        ->and($delivery->message_id)->toContain('projects/firebase-test-project/messages/123');
});

it('does not dispatch push when the user has not explicitly saved push preferences', function (): void {
    config()->set('queue.default', 'sync');

    app(FcmSettings::class)->fill([
        'enabled' => true,
        'project_id' => 'firebase-test-project',
        'service_account_json' => json_encode([
            'project_id' => 'firebase-test-project',
            'client_email' => 'firebase-adminsdk@example.test',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nTEST\n-----END PRIVATE KEY-----\n",
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], JSON_UNESCAPED_SLASHES),
    ])->save();

    app()->instance(PushSender::class, new class implements PushSender
    {
        public function send(PushMessage $message, bool $validateOnly = false): PushSendResult
        {
            return PushSendResult::success('projects/firebase-test-project/messages/123', [
                'name' => 'projects/firebase-test-project/messages/123',
                'type' => $message->type,
            ]);
        }
    });

    $user = User::factory()->verified()->create([
        'email' => 'no-prefs-push@example.com',
        'status' => 'active',
    ]);

    UserDevice::query()->create([
        'user_id' => $user->getKey(),
        'provider' => 'fcm',
        'platform' => 'android',
        'push_token' => 'device-token-no-prefs',
        'device_id' => 'device-no-prefs-123456',
        'device_name' => 'Pixel 9',
        'app_version' => '1.0.0',
        'permission_status' => 'authorized',
        'push_token_updated_at' => now(),
        'last_seen_at' => now(),
    ]);

    $user->notify(new ParishApprovalStatusChangedNotification(true));

    expect(PushDelivery::query()->count())->toBe(0);
});
