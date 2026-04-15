<?php

use App\Mail\SchedulerDailyReportMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Spatie\Activitylog\Models\Activity;

it('sends a scheduler report email for the selected day', function () {
    Mail::fake();

    config()->set('services.wspolnota.scheduler_report_recipient', 'raporty@example.com');

    Carbon::setTestNow('2026-03-11 09:15:00');

    activity('announcements-ai')
        ->event('announcements_ai_summary_job_started')
        ->withProperties(['limit' => 80])
        ->log('Start');

    activity('announcements-ai')
        ->event('announcements_ai_summary_job_finished')
        ->withProperties([
            'analyzed' => 3,
            'generated' => 2,
            'skipped' => 1,
            'failed' => 0,
        ])
        ->log('Finish');

    activity('announcements-notifications')
        ->event('announcements_notification_job_started')
        ->withProperties(['limit' => 150])
        ->log('Start');

    activity('announcements-notifications')
        ->event('announcements_notification_job_finished')
        ->withProperties([
            'analyzed' => 2,
            'sent_sets' => 1,
            'sent_recipients' => 42,
            'skipped' => 1,
            'failed' => 0,
        ])
        ->log('Finish');

    activity('news-posts')
        ->event('scheduled_news_publish_job_started')
        ->withProperties(['limit' => 150])
        ->log('Start');

    activity('news-posts')
        ->event('scheduled_news_publish_job_finished')
        ->withProperties([
            'analyzed' => 4,
            'published' => 4,
        ])
        ->log('Finish');

    Artisan::call('scheduler:send-report', ['--date' => '2026-03-11']);

    Mail::assertQueued(SchedulerDailyReportMessage::class, function (SchedulerDailyReportMessage $mail) {
        return $mail->hasTo('raporty@example.com')
            && $mail->report['has_failures'] === false
            && $mail->report['jobs'][0]['metrics']['Wygenerowane streszczenia'] === 2
            && $mail->report['jobs'][1]['metrics']['Laczna liczba odbiorcow'] === 42
            && $mail->report['jobs'][2]['metrics']['Opublikowane wpisy'] === 4;
    });

    expect(Activity::query()->where('event', 'scheduler_daily_report_sent')->exists())->toBeTrue();

    Carbon::setTestNow();
});
