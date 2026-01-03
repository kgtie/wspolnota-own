<?php

namespace App\Jobs;

use App\Models\AnnouncementSet;
use App\Services\Announcements\AnnouncementAiSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAnnouncementSetAiSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $announcementSetId,
        public bool $force = false,
    ) {}

    public function handle(AnnouncementAiSummaryService $service): void
    {
        $set = AnnouncementSet::query()
            ->with(['announcements' => fn ($q) => $q->orderBy('sort_order')])
            ->findOrFail($this->announcementSetId);

        if (! $this->force && filled($set->ai_summary)) {
            return;
        }

        $summary = $service->generateSummary($set);

        $set->ai_summary = $summary;
        $set->ai_summary_generated_at = now();
        $set->save();
    }
}
