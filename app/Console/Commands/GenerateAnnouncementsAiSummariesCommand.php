<?php

namespace App\Console\Commands;

use App\Models\AnnouncementSet;
use App\Support\Announcements\AnnouncementSetSummarizer;
use Illuminate\Console\Command;
use Throwable;

class GenerateAnnouncementsAiSummariesCommand extends Command
{
    protected $signature = 'announcements:ai {--limit=50 : Maksymalna liczba zestawow do analizy}';

    protected $description = 'Generuje streszczenia AI dla opublikowanych zestawow ogloszen bez streszczenia.';

    public function handle(AnnouncementSetSummarizer $summarizer): int
    {
        $limit = max(1, (int) $this->option('limit'));

        activity('announcements-ai')
            ->event('announcements_ai_summary_job_started')
            ->withProperties([
                'limit' => $limit,
            ])
            ->log('Uruchomiono dzienny proces generowania streszczen AI dla ogloszen.');

        $sets = AnnouncementSet::query()
            ->with(['parish'])
            ->where('status', 'published')
            ->whereNull('summary_ai')
            ->orderBy('effective_from')
            ->limit($limit)
            ->get();

        if ($sets->isEmpty()) {
            $this->info('Brak opublikowanych zestawow wymagajacych streszczenia AI.');

            activity('announcements-ai')
                ->event('announcements_ai_summary_job_noop')
                ->withProperties([
                    'limit' => $limit,
                ])
                ->log('Proces streszczen AI zakonczyl sie bez zmian.');

            return self::SUCCESS;
        }

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($sets as $set) {
            if (! $set->parish?->getSetting('announcements_ai_summary', true)) {
                $skipped++;
                $this->line("Pominieto #{$set->id}: AI summary jest wylaczone dla parafii.");

                continue;
            }

            if (! $summarizer->canBeSummarized($set)) {
                $skipped++;
                $this->line("Pominieto #{$set->id}: brak aktywnych ogloszen lub juz posiada streszczenie.");

                continue;
            }

            try {
                $summary = $summarizer->summarize($set);

                $set->update([
                    'summary_ai' => $summary,
                    'summary_generated_at' => now(),
                    'summary_model' => (string) config('gemini.model'),
                ]);

                activity('announcements-ai')
                    ->performedOn($set)
                    ->event('announcement_set_ai_summary_generated_auto')
                    ->withProperties([
                        'parish_id' => $set->parish_id,
                        'announcement_set_id' => $set->getKey(),
                        'summary_length' => mb_strlen($summary),
                        'model' => (string) config('gemini.model'),
                    ])
                    ->log('Automatycznie wygenerowano streszczenie AI dla zestawu ogloszen.');

                $generated++;
                $this->info("Wygenerowano streszczenie dla zestawu #{$set->id}.");
            } catch (Throwable $exception) {
                $failed++;
                report($exception);

                activity('announcements-ai')
                    ->performedOn($set)
                    ->event('announcement_set_ai_summary_generation_failed_auto')
                    ->withProperties([
                        'parish_id' => $set->parish_id,
                        'announcement_set_id' => $set->getKey(),
                        'error' => $exception->getMessage(),
                    ])
                    ->log('Automatyczne generowanie streszczenia AI dla zestawu ogloszen zakonczone bledem.');

                $this->error("Blad podczas streszczania zestawu #{$set->id}: {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(
            ['Metryka', 'Wartosc'],
            [
                ['Przeanalizowane', (string) $sets->count()],
                ['Wygenerowane', (string) $generated],
                ['Pominiete', (string) $skipped],
                ['Bledy', (string) $failed],
            ],
        );

        activity('announcements-ai')
            ->event('announcements_ai_summary_job_finished')
            ->withProperties([
                'limit' => $limit,
                'analyzed' => $sets->count(),
                'generated' => $generated,
                'skipped' => $skipped,
                'failed' => $failed,
            ])
            ->log('Zakonczono dzienny proces generowania streszczen AI dla ogloszen.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
