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

        $sets = AnnouncementSet::query()
            ->with(['parish'])
            ->where('status', 'published')
            ->whereNull('summary_ai')
            ->orderBy('effective_from')
            ->limit($limit)
            ->get();

        if ($sets->isEmpty()) {
            $this->info('Brak opublikowanych zestawow wymagajacych streszczenia AI.');

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

                $generated++;
                $this->info("Wygenerowano streszczenie dla zestawu #{$set->id}.");
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
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

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
