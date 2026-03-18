<?php

use App\Models\AnnouncementSet;
use App\Models\NewsPost;
use App\Models\Parish;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

it('dispatches delayed news and announcement notifications one hour after publication', function (): void {
    config()->set('queue.default', 'sync');
    Mail::fake();

    $parish = Parish::factory()->create();
    $user = User::factory()->create([
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'email' => 'parishioner@example.com',
    ]);

    $superadminParishioner = User::factory()->create([
        'status' => 'active',
        'role' => 2,
        'home_parish_id' => $parish->getKey(),
        'email' => 'superadmin-parishioner@example.com',
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

    UserNotificationPreference::query()->create([
        'user_id' => $superadminParishioner->getKey(),
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

    $news = NewsPost::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Nowa aktualnosc',
        'content' => 'Tresci aktualnosci',
        'status' => 'published',
        'published_at' => now()->subHour()->subMinute(),
    ]);

    $set = AnnouncementSet::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Nowe ogloszenia',
        'week_label' => 'III Niedziela',
        'effective_from' => now()->toDateString(),
        'effective_to' => now()->addDays(7)->toDateString(),
        'status' => 'published',
        'published_at' => now()->subHour()->subMinute(),
    ]);

    $this->artisan('notifications:dispatch-delayed-content', [
        '--limit' => 10,
    ])->assertSuccessful();

    expect($news->fresh()->push_notification_sent_at)->not->toBeNull()
        ->and($news->fresh()->email_notification_sent_at)->not->toBeNull()
        ->and($set->fresh()->push_notification_sent_at)->not->toBeNull()
        ->and($set->fresh()->email_notification_sent_at)->not->toBeNull();

    expect(DB::table('notifications')->where('notifiable_id', $user->getKey())->count())->toBe(2)
        ->and(DB::table('notifications')->where('notifiable_id', $user->getKey())->pluck('data')->map(fn ($json) => json_decode($json, true)['type'])->all())
        ->toContain('NEWS_CREATED', 'ANNOUNCEMENTS_PACKAGE_PUBLISHED')
        ->and(DB::table('notifications')->where('notifiable_id', $superadminParishioner->getKey())->count())->toBe(2)
        ->and(DB::table('notifications')->where('notifiable_id', $superadminParishioner->getKey())->pluck('data')->map(fn ($json) => json_decode($json, true)['type'])->all())
        ->toContain('NEWS_CREATED', 'ANNOUNCEMENTS_PACKAGE_PUBLISHED');
});
