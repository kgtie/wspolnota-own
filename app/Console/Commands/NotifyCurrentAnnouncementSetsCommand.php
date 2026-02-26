<?php

namespace App\Console\Commands;

use App\Models\AnnouncementSet;
use App\Support\Announcements\AnnouncementPublicationNotifier;
use Illuminate\Console\Command;
use Throwable;

class NotifyCurrentAnnouncementSetsCommand extends Command
{
    protected $signature = 'announcements:notify-current {--limit=100 : Maksymalna liczba zestawow do obslugi}';

    protected $description = 'Wysyla email do parafian dla opublikowanych zestawow, ktore staly sie aktualne.';

    public function handle(AnnouncementPublicationNotifier $notifier): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $today = now()->toDateString();

        activity('announcements-notifications')
            ->event('announcements_notification_job_started')
            ->withProperties([
                'limit' => $limit,
                'date' => $today,
            ])
            ->log('Uruchomiono proces wysylki emaili o aktualnych ogloszeniach.');

        $sets = AnnouncementSet::query()
            ->with(['parish'])
            ->where('status', 'published')
            ->whereNull('notifications_sent_at')
            ->currentForDate($today)
            ->orderBy('effective_from')
            ->limit($limit)
            ->get();

        if ($sets->isEmpty()) {
            $this->info('Brak opublikowanych zestawow oczekujacych na wysylke emaila.');

            activity('announcements-notifications')
                ->event('announcements_notification_job_noop')
                ->withProperties([
                    'limit' => $limit,
                    'date' => $today,
                ])
                ->log('Proces wysylki emaili o ogloszeniach zakonczyl sie bez zmian.');

            return self::SUCCESS;
        }

        $sentSets = 0;
        $sentRecipients = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($sets as $set) {
            if (! $notifier->shouldNotify($set, $today)) {
                $skipped++;
                $this->line("Pominieto #{$set->id}: zestaw nie kwalifikuje sie do wysylki.");

                continue;
            }

            try {
                $recipientsCount = $notifier->notify($set, 'scheduler');
                $sentSets++;
                $sentRecipients += $recipientsCount;

                $this->info("Wyslano email dla zestawu #{$set->id} do {$recipientsCount} parafian.");
            } catch (Throwable $exception) {
                $failed++;
                report($exception);

                activity('announcements-notifications')
                    ->performedOn($set)
                    ->event('announcement_set_notification_failed')
                    ->withProperties([
                        'parish_id' => $set->parish_id,
                        'announcement_set_id' => $set->getKey(),
                        'date' => $today,
                        'error' => $exception->getMessage(),
                    ])
                    ->log('Wysylka emaila o aktualnych ogloszeniach zakonczona bledem.');

                $this->error("Blad podczas wysylki dla zestawu #{$set->id}: {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(
            ['Metryka', 'Wartosc'],
            [
                ['Przeanalizowane zestawy', (string) $sets->count()],
                ['Zestawy z wysylka', (string) $sentSets],
                ['Laczna liczba odbiorcow', (string) $sentRecipients],
                ['Pominiete', (string) $skipped],
                ['Bledy', (string) $failed],
            ],
        );

        activity('announcements-notifications')
            ->event('announcements_notification_job_finished')
            ->withProperties([
                'limit' => $limit,
                'date' => $today,
                'analyzed' => $sets->count(),
                'sent_sets' => $sentSets,
                'sent_recipients' => $sentRecipients,
                'skipped' => $skipped,
                'failed' => $failed,
            ])
            ->log('Zakonczono proces wysylki emaili o aktualnych ogloszeniach.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
