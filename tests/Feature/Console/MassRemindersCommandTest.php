<?php

use App\Models\Mass;
use App\Models\Parish;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

it('dispatches push reminder for pending mass and sends morning email once', function (): void {
    config()->set('queue.default', 'sync');
    Mail::fake();
    Carbon::setTestNow('2026-03-17 05:00:00');

    $parish = Parish::factory()->create();
    $user = User::factory()->create([
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'email' => 'participant@example.com',
    ]);

    UserNotificationPreference::query()->create([
        'user_id' => $user->getKey(),
        'news_push' => true,
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
        'auth_security_email' => false,
    ]);

    $mass = Mass::query()->create([
        'parish_id' => $parish->getKey(),
        'intention_title' => 'Za parafian',
        'celebration_at' => now()->addHours(8),
        'mass_kind' => 'weekday',
        'mass_type' => 'individual',
        'status' => 'scheduled',
    ]);

    $mass->participants()->attach($user->getKey(), [
        'registered_at' => now()->subDay(),
    ]);

    $this->artisan('masses:dispatch-pending-reminders', [
        '--limit' => 50,
    ])->assertSuccessful();

    $this->artisan('masses:dispatch-morning-email-reminders', [
        '--limit' => 50,
    ])->assertSuccessful();

    $pivot = DB::table('mass_user')
        ->where('mass_id', $mass->getKey())
        ->where('user_id', $user->getKey())
        ->first();

    expect($pivot)->not->toBeNull()
        ->and($pivot->reminder_push_8h_sent_at)->not->toBeNull()
        ->and($pivot->reminder_email_sent_at)->not->toBeNull()
        ->and(DB::table('notifications')->where('notifiable_id', $user->getKey())->count())->toBe(1)
        ->and(json_decode((string) DB::table('notifications')->where('notifiable_id', $user->getKey())->value('data'), true)['type'])
        ->toBe('MASS_PENDING');

    Carbon::setTestNow();
});
