<?php

namespace App\Console\Commands;

use App\Models\AnnouncementSet;
use App\Support\Notifications\ParishContentNotificationDispatcher;
use Illuminate\Console\Command;
use Throwable;

class NotifyCurrentAnnouncementSetsCommand extends Command
{
    protected $signature = 'announcements:notify-current {--limit=100 : Maksymalna liczba zestawow do obslugi}';

    protected $description = 'Wysyla opoznione o 1h notyfikacje i emaile dla opublikowanych zestawow ogloszen.';

    public function handle(ParishContentNotificationDispatcher $dispatcher): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $threshold = now()->subHour();

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

        if ($sets->isEmpty()) {
            $this->info('Brak opublikowanych zestawow oczekujacych na opozniony dispatch.');

            return self::SUCCESS;
        }

        $sentSets = 0;
        $sentRecipients = 0;
        $failed = 0;

        foreach ($sets as $set) {
            try {
                $recipientsCount = $dispatcher->dispatchAnnouncementSet($set);
                $sentSets++;
                $sentRecipients += $recipientsCount;

                $this->info("Zakolejkowano dispatch ogloszen dla zestawu #{$set->id} do {$recipientsCount} parafian.");
            } catch (Throwable $exception) {
                $failed++;
                report($exception);

                $this->error("Blad podczas dispatchu dla zestawu #{$set->id}: {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(
            ['Metryka', 'Wartosc'],
            [
                ['Przeanalizowane zestawy', (string) $sets->count()],
                ['Zestawy z dispatch', (string) $sentSets],
                ['Laczna liczba odbiorcow', (string) $sentRecipients],
                ['Bledy', (string) $failed],
            ],
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
