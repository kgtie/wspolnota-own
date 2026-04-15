<?php

use App\Jobs\SendManualPushToDeviceJob;
use App\Mail\CommunicationBroadcastMessage;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserNotificationPreference;
use App\Support\SuperAdmin\CommunicationAudienceResolver;
use App\Support\SuperAdmin\InstantCommunicationService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

it('filters manual email communication by user preferences', function (): void {
    Mail::fake();

    $allowed = User::factory()->create([
        'email' => 'manual-email-allowed@example.com',
        'status' => 'active',
    ]);

    $blocked = User::factory()->create([
        'email' => 'manual-email-blocked@example.com',
        'status' => 'active',
    ]);

    UserNotificationPreference::query()->create([
        'user_id' => $allowed->getKey(),
        'news_push' => true,
        'news_email' => false,
        'announcements_push' => true,
        'announcements_email' => true,
        'mass_reminders_push' => true,
        'mass_reminders_email' => true,
        'office_messages_push' => true,
        'office_messages_email' => true,
        'parish_approval_status_push' => true,
        'parish_approval_status_email' => true,
        'auth_security_push' => false,
        'auth_security_email' => true,
        'manual_messages_push' => false,
        'manual_messages_email' => true,
    ]);

    UserNotificationPreference::query()->create([
        'user_id' => $blocked->getKey(),
        'news_push' => true,
        'news_email' => false,
        'announcements_push' => true,
        'announcements_email' => true,
        'mass_reminders_push' => true,
        'mass_reminders_email' => true,
        'office_messages_push' => true,
        'office_messages_email' => true,
        'parish_approval_status_push' => true,
        'parish_approval_status_email' => true,
        'auth_security_push' => false,
        'auth_security_email' => true,
        'manual_messages_push' => false,
        'manual_messages_email' => false,
    ]);

    $result = app(InstantCommunicationService::class)->sendEmailToUsers(
        users: collect([$allowed, $blocked]),
        subjectLine: 'Test',
        messageBody: 'Tresc testowa',
    );

    expect($result)->toBe([
        'users' => 1,
        'queued' => 1,
        'skipped' => 1,
    ]);

    Mail::assertQueued(CommunicationBroadcastMessage::class, fn (CommunicationBroadcastMessage $mail): bool => $mail->hasTo('manual-email-allowed@example.com'));
    Mail::assertNotQueued(CommunicationBroadcastMessage::class, fn (CommunicationBroadcastMessage $mail): bool => $mail->hasTo('manual-email-blocked@example.com'));
});

it('filters manual push communication by user preferences', function (): void {
    Bus::fake();

    $allowed = User::factory()->create([
        'email' => 'manual-push-allowed@example.com',
        'status' => 'active',
    ]);

    $blocked = User::factory()->create([
        'email' => 'manual-push-blocked@example.com',
        'status' => 'active',
    ]);

    UserNotificationPreference::query()->create([
        'user_id' => $allowed->getKey(),
        'news_push' => true,
        'news_email' => false,
        'announcements_push' => true,
        'announcements_email' => true,
        'mass_reminders_push' => true,
        'mass_reminders_email' => true,
        'office_messages_push' => true,
        'office_messages_email' => true,
        'parish_approval_status_push' => true,
        'parish_approval_status_email' => true,
        'auth_security_push' => false,
        'auth_security_email' => true,
        'manual_messages_push' => true,
        'manual_messages_email' => true,
    ]);

    UserNotificationPreference::query()->create([
        'user_id' => $blocked->getKey(),
        'news_push' => true,
        'news_email' => false,
        'announcements_push' => true,
        'announcements_email' => true,
        'mass_reminders_push' => true,
        'mass_reminders_email' => true,
        'office_messages_push' => true,
        'office_messages_email' => true,
        'parish_approval_status_push' => true,
        'parish_approval_status_email' => true,
        'auth_security_push' => false,
        'auth_security_email' => true,
        'manual_messages_push' => false,
        'manual_messages_email' => true,
    ]);

    foreach ([$allowed, $blocked] as $user) {
        UserDevice::query()->create([
            'user_id' => $user->getKey(),
            'provider' => 'fcm',
            'platform' => 'android',
            'push_token' => 'token-'.$user->getKey(),
            'device_id' => 'device-'.$user->getKey(),
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
            'permission_status' => 'authorized',
            'push_token_updated_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    $result = app(InstantCommunicationService::class)->queuePushToUsers(
        users: collect([$allowed->fresh('devices', 'notificationPreference'), $blocked->fresh('devices', 'notificationPreference')]),
        title: 'Test',
        body: 'Push testowy',
    );

    expect($result)->toBe([
        'users' => 1,
        'devices' => 1,
        'skipped' => 0,
    ]);

    Bus::assertDispatched(SendManualPushToDeviceJob::class, fn (SendManualPushToDeviceJob $job): bool => $job->userId === $allowed->getKey());
    Bus::assertNotDispatched(SendManualPushToDeviceJob::class, fn (SendManualPushToDeviceJob $job): bool => $job->userId === $blocked->getKey());
});

it('resolves push recipients using push preferences instead of email preferences', function (): void {
    $allowed = User::factory()->create([
        'email' => 'push-topic-allowed@example.com',
        'status' => 'active',
    ]);

    $blocked = User::factory()->create([
        'email' => 'push-topic-blocked@example.com',
        'status' => 'active',
    ]);

    UserNotificationPreference::query()->create([
        'user_id' => $allowed->getKey(),
        'news_push' => true,
        'news_email' => false,
        'announcements_push' => true,
        'announcements_email' => true,
        'mass_reminders_push' => true,
        'mass_reminders_email' => true,
        'office_messages_push' => true,
        'office_messages_email' => true,
        'parish_approval_status_push' => true,
        'parish_approval_status_email' => true,
        'auth_security_push' => false,
        'auth_security_email' => true,
        'manual_messages_push' => false,
        'manual_messages_email' => true,
    ]);

    UserNotificationPreference::query()->create([
        'user_id' => $blocked->getKey(),
        'news_push' => false,
        'news_email' => true,
        'announcements_push' => true,
        'announcements_email' => true,
        'mass_reminders_push' => true,
        'mass_reminders_email' => true,
        'office_messages_push' => true,
        'office_messages_email' => true,
        'parish_approval_status_push' => true,
        'parish_approval_status_email' => true,
        'auth_security_push' => false,
        'auth_security_email' => true,
        'manual_messages_push' => false,
        'manual_messages_email' => true,
    ]);

    foreach ([$allowed, $blocked] as $user) {
        UserDevice::query()->create([
            'user_id' => $user->getKey(),
            'provider' => 'fcm',
            'platform' => 'android',
            'push_token' => 'news-token-'.$user->getKey(),
            'device_id' => 'news-device-'.$user->getKey(),
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
            'permission_status' => 'authorized',
            'push_token_updated_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    $recipients = app(CommunicationAudienceResolver::class)->resolvePushRecipients([
        'recipient_scope' => 'single_users',
        'selected_user_ids' => [$allowed->getKey(), $blocked->getKey()],
        'notification_preference_topic' => 'news',
    ]);

    expect($recipients->pluck('id')->all())->toBe([$allowed->getKey()]);
});
