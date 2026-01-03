<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAnnouncementSetAiSummaryJob;
use App\Models\AnnouncementSet;
use Illuminate\Console\Command;

class GenerateMissingAnnouncementSummaries extends Command
{
    protected $signature = 'announcements:generate-ai-summaries {--limit=50}';
    protected $description = 'Generuje streszczenia AI dla zestawów ogłoszeń bez ai_summary.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $sets = AnnouncementSet::query()
            ->whereNull('ai_summary')
            ->orWhere('ai_summary', '=', '')
            ->orderByDesc('valid_from')
            ->limit($limit)
            ->pluck('id');

        foreach ($sets as $id) {
            GenerateAnnouncementSetAiSummaryJob::dispatch((int) $id);
        }

        $this->info("Zlecono {$sets->count()} zadań.");
        return self::SUCCESS;
    }
}
