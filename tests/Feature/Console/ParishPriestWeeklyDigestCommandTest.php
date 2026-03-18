<?php

use App\Mail\ParishPriestWeeklyDigestMessage;
use App\Models\AnnouncementSet;
use App\Models\Mass;
use App\Models\OfficeConversation;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

it('queues weekly priest digest for active parish admins and sends a copy to superadmin', function (): void {
    Mail::fake();

    config()->set('services.wspolnota.priest_weekly_digest_copy_recipient', 'copy@example.com');

    Carbon::setTestNow('2026-03-21 12:00:00');

    $parish = Parish::factory()->create([
        'name' => 'Parafia Testowa',
    ]);

    $priest = User::factory()->admin()->create([
        'email' => 'proboszcz@example.com',
        'status' => 'active',
    ]);

    $parishioner = User::factory()->create([
        'home_parish_id' => $parish->getKey(),
        'status' => 'active',
    ]);

    $parish->users()->attach($priest->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    foreach (range(0, 9) as $offset) {
        Mass::query()->create([
            'parish_id' => $parish->getKey(),
            'intention_title' => 'Msza '.$offset,
            'celebration_at' => now()->startOfDay()->addDays($offset)->setTime(18, 0),
            'status' => 'scheduled',
            'mass_kind' => 'weekday',
            'mass_type' => 'individual',
        ]);
    }

    AnnouncementSet::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Ogloszenia na przyszly tydzien',
        'week_label' => 'Tydzien testowy',
        'effective_from' => '2026-03-22',
        'effective_to' => '2026-03-28',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    \App\Models\NewsPost::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Nowa aktualnosc',
        'content' => 'Tresc',
        'status' => 'published',
        'published_at' => now()->subDays(3),
    ]);

    OfficeConversation::query()->create([
        'parish_id' => $parish->getKey(),
        'parishioner_user_id' => $parishioner->getKey(),
        'priest_user_id' => $priest->getKey(),
        'status' => OfficeConversation::STATUS_OPEN,
        'last_message_at' => now()->subHour(),
    ]);

    $this->artisan('parishes:send-weekly-priest-digest', [
        '--date' => '2026-03-21',
        '--copy-to-superadmin' => true,
    ])->assertSuccessful();

    Mail::assertQueued(ParishPriestWeeklyDigestMessage::class, function (ParishPriestWeeklyDigestMessage $mail): bool {
        return $mail->hasTo('proboszcz@example.com')
            && $mail->hasCc('copy@example.com')
            && $mail->report['checklist']['mass_calendar']['tone'] === 'success'
            && $mail->report['checklist']['announcements']['tone'] === 'success'
            && $mail->report['stats']['office_open_for_priest'] === 1;
    });

    Carbon::setTestNow();
});
