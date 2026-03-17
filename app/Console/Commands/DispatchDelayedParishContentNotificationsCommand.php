<?php

namespace App\Console\Commands;

use App\Models\AnnouncementSet;
use App\Models\NewsPost;
use App\Support\Notifications\ParishContentNotificationDispatcher;
use Illuminate\Console\Command;
use Throwable;

class DispatchDelayedParishContentNotificationsCommand extends Command
{
    protected $signature = 'notifications:dispatch-delayed-content {--limit=100 : Maksymalna liczba rekordow na typ}';

    protected $description = 'Wysyla opoznione o 1h notyfikacje i emaile dla newsow oraz ogloszen.';

    public function handle(ParishContentNotificationDispatcher $dispatcher): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $threshold = now()->subHour();

        $news = NewsPost::query()
            ->where('status', 'published')
            ->whereNull('push_notification_sent_at')
            ->whereNull('email_notification_sent_at')
            ->where(function ($query) use ($threshold): void {
                $query
                    ->where('published_at', '<=', $threshold)
                    ->orWhere(function ($inner) use ($threshold): void {
                        $inner
                            ->whereNull('published_at')
                            ->where('created_at', '<=', $threshold);
                    });
            })
            ->orderBy('published_at')
            ->limit($limit)
            ->get();

        $sets = AnnouncementSet::query()
            ->where('status', 'published')
            ->whereNull('push_notification_sent_at')
            ->whereNull('email_notification_sent_at')
            ->where(function ($query) use ($threshold): void {
                $query
                    ->where('published_at', '<=', $threshold)
                    ->orWhere(function ($inner) use ($threshold): void {
                        $inner
                            ->whereNull('published_at')
                            ->where('created_at', '<=', $threshold);
                    });
            })
            ->orderBy('published_at')
            ->limit($limit)
            ->get();

        $newsProcessed = 0;
        $announcementProcessed = 0;
        $recipients = 0;
        $failed = 0;

        foreach ($news as $post) {
            try {
                $recipients += $dispatcher->dispatchNews($post);
                $newsProcessed++;
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
                $this->error("Blad dispatchu news #{$post->getKey()}: {$exception->getMessage()}");
            }
        }

        foreach ($sets as $set) {
            try {
                $recipients += $dispatcher->dispatchAnnouncementSet($set);
                $announcementProcessed++;
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
                $this->error("Blad dispatchu ogloszen #{$set->getKey()}: {$exception->getMessage()}");
            }
        }

        $this->table(
            ['Metryka', 'Wartosc'],
            [
                ['News dispatch', (string) $newsProcessed],
                ['Announcement dispatch', (string) $announcementProcessed],
                ['Laczna liczba odbiorcow', (string) $recipients],
                ['Bledy', (string) $failed],
            ],
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
