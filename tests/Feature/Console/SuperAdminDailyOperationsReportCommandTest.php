<?php

use App\Mail\SuperAdminDailyOperationsReportMessage;
use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\PushDelivery;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

it('queues a detailed daily operations report for the main superadmin', function (): void {
    Mail::fake();

    config()->set('services.wspolnota.daily_operations_recipient', 'konrad@wspolnota.app');

    Carbon::setTestNow('2026-03-17 09:00:00');

    $parish = Parish::factory()->create([
        'name' => 'Parafia Dobowa',
    ]);

    $priest = User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'home_parish_id' => $parish->getKey(),
    ]);

    $parishioner = User::factory()->create([
        'email' => 'parafianin@example.com',
        'home_parish_id' => $parish->getKey(),
        'status' => 'active',
    ]);

    \App\Models\NewsPost::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Dobowa aktualnosc',
        'content' => 'Tresc',
        'status' => 'published',
        'published_at' => now(),
    ]);

    AnnouncementSet::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Dobowe ogloszenia',
        'week_label' => 'Test',
        'effective_from' => '2026-03-17',
        'effective_to' => '2026-03-23',
        'status' => 'published',
        'published_at' => now()->addHour(),
    ]);

    $mass = Mass::query()->create([
        'parish_id' => $parish->getKey(),
        'intention_title' => 'Msza dobowa',
        'celebration_at' => now()->addHours(8),
        'status' => 'scheduled',
        'mass_kind' => 'weekday',
        'mass_type' => 'individual',
    ]);

    $conversation = OfficeConversation::query()->create([
        'parish_id' => $parish->getKey(),
        'parishioner_user_id' => $parishioner->getKey(),
        'priest_user_id' => $priest->getKey(),
        'status' => OfficeConversation::STATUS_OPEN,
        'last_message_at' => now()->addHours(2),
    ]);

    Carbon::setTestNow('2026-03-17 14:00:00');

    OfficeMessage::query()->create([
        'office_conversation_id' => $conversation->getKey(),
        'sender_user_id' => $parishioner->getKey(),
        'body' => 'Nowa wiadomosc',
        'has_attachments' => false,
    ]);

    PushDelivery::query()->create([
        'user_id' => $parishioner->getKey(),
        'provider' => 'fcm',
        'platform' => 'ios',
        'type' => 'NEWS_CREATED',
        'status' => PushDelivery::STATUS_SENT,
        'sent_at' => now(),
    ]);

    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'database',
        'notifiable_type' => User::class,
        'notifiable_id' => $parishioner->getKey(),
        'data' => json_encode(['type' => 'NEWS_CREATED'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'mail',
        'payload' => '{}',
        'exception' => 'Synthetic failure for report test',
        'failed_at' => now(),
    ]);

    activity('daily-report-test')
        ->event('manual_probe')
        ->causedBy($priest)
        ->performedOn($mass)
        ->log('Synthetic activity entry');

    Carbon::setTestNow('2026-03-18 06:00:00');

    $this->artisan('superadmin:send-daily-operations-report')
        ->assertSuccessful();

    Mail::assertQueued(SuperAdminDailyOperationsReportMessage::class, function (SuperAdminDailyOperationsReportMessage $mail): bool {
        return $mail->hasTo('konrad@wspolnota.app')
            && $mail->report['overview']['new_users'] >= 2
            && $mail->report['overview']['published_news'] === 1
            && $mail->report['overview']['published_announcements'] === 1
            && $mail->report['overview']['office_conversations'] === 1
            && $mail->report['overview']['office_messages'] === 1
            && $mail->report['overview']['push_sent'] === 1
            && $mail->report['overview']['failed_jobs'] === 1;
    });

    Carbon::setTestNow();
});
