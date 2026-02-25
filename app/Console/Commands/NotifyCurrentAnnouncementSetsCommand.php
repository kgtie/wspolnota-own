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
                $recipientsCount = $notifier->notify($set);
                $sentSets++;
                $sentRecipients += $recipientsCount;

                $this->info("Wyslano email dla zestawu #{$set->id} do {$recipientsCount} parafian.");
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
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

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
