<?php

namespace App\Support\Scheduler;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class SchedulerDailyReportBuilder
{
    public function build(CarbonInterface $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $activities = Activity::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('log_name', [
                'announcements-ai',
                'announcements-notifications',
                'news-posts',
            ])
            ->orderBy('created_at')
            ->get();

        $jobs = [
            $this->buildAiSummariesSection($activities->where('log_name', 'announcements-ai')),
            $this->buildAnnouncementNotificationsSection($activities->where('log_name', 'announcements-notifications')),
            $this->buildNewsPublishingSection($activities->where('log_name', 'news-posts')),
        ];

        return [
            'date_label' => $start->format('d.m.Y'),
            'window' => [
                'start' => $start,
                'end' => $end,
            ],
            'jobs' => $jobs,
            'has_failures' => collect($jobs)->contains(fn (array $job): bool => $job['has_failures']),
        ];
    }

    private function buildAiSummariesSection(Collection $activities): array
    {
        $started = $activities->where('event', 'announcements_ai_summary_job_started');
        $noop = $activities->where('event', 'announcements_ai_summary_job_noop');
        $finished = $activities->where('event', 'announcements_ai_summary_job_finished');
        $itemFailures = $activities->where('event', 'announcement_set_ai_summary_generation_failed_auto');

        $errors = $this->sumProperty($finished, 'failed');

        return [
            'label' => 'Streszczenia AI ogloszen',
            'command' => 'announcements:ai --limit=80',
            'runs' => $started->count(),
            'noop_runs' => $noop->count(),
            'completed_runs' => $finished->count(),
            'metrics' => [
                'Przeanalizowane zestawy' => $this->sumProperty($finished, 'analyzed'),
                'Wygenerowane streszczenia' => $this->sumProperty($finished, 'generated'),
                'Pominiete pozycje' => $this->sumProperty($finished, 'skipped'),
                'Bledy' => $errors,
            ],
            'has_failures' => $errors > 0 || $itemFailures->isNotEmpty(),
            'latest_error' => $this->latestError($itemFailures),
        ];
    }

    private function buildAnnouncementNotificationsSection(Collection $activities): array
    {
        $started = $activities->where('event', 'announcements_notification_job_started');
        $noop = $activities->where('event', 'announcements_notification_job_noop');
        $finished = $activities->where('event', 'announcements_notification_job_finished');
        $itemFailures = $activities->where('event', 'announcement_set_notification_failed');

        $errors = $this->sumProperty($finished, 'failed');

        return [
            'label' => 'Powiadomienia o aktualnych ogloszeniach',
            'command' => 'announcements:notify-current --limit=150',
            'runs' => $started->count(),
            'noop_runs' => $noop->count(),
            'completed_runs' => $finished->count(),
            'metrics' => [
                'Przeanalizowane zestawy' => $this->sumProperty($finished, 'analyzed'),
                'Wyslane zestawy' => $this->sumProperty($finished, 'sent_sets'),
                'Laczna liczba odbiorcow' => $this->sumProperty($finished, 'sent_recipients'),
                'Pominiete pozycje' => $this->sumProperty($finished, 'skipped'),
                'Bledy' => $errors,
            ],
            'has_failures' => $errors > 0 || $itemFailures->isNotEmpty(),
            'latest_error' => $this->latestError($itemFailures),
        ];
    }

    private function buildNewsPublishingSection(Collection $activities): array
    {
        $started = $activities->where('event', 'scheduled_news_publish_job_started');
        $noop = $activities->where('event', 'scheduled_news_publish_job_noop');
        $finished = $activities->where('event', 'scheduled_news_publish_job_finished');
        $failed = $activities->where('event', 'scheduled_news_publish_job_failed');

        return [
            'label' => 'Publikacja zaplanowanych aktualnosci',
            'command' => 'news:publish-scheduled --limit=150',
            'runs' => $started->count(),
            'noop_runs' => $noop->count(),
            'completed_runs' => $finished->count(),
            'metrics' => [
                'Przeanalizowane wpisy' => $this->sumProperty($finished, 'analyzed'),
                'Opublikowane wpisy' => $this->sumProperty($finished, 'published'),
                'Nieudane przebiegi zadania' => $failed->count(),
            ],
            'has_failures' => $failed->isNotEmpty(),
            'latest_error' => $this->latestError($failed),
        ];
    }

    private function sumProperty(Collection $activities, string $key): int
    {
        return $activities->sum(fn (Activity $activity): int => (int) data_get($activity->properties?->toArray(), $key, 0));
    }

    private function latestError(Collection $activities): ?string
    {
        $latest = $activities->last();

        if (! $latest instanceof Activity) {
            return null;
        }

        return data_get($latest->properties?->toArray(), 'error');
    }
}
