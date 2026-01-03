<?php

namespace App\Services\Announcements;

use App\Models\AnnouncementSet;
use App\Services\GeminiClient;

class AnnouncementAiSummaryService
{
    public function __construct(
        private readonly GeminiClient $gemini,
    ) {}

    public function buildPrompt(AnnouncementSet $set): string
    {
        $lines = $set->announcements()
            ->orderBy('sort_order')
            ->pluck('content')
            ->map(fn ($c) => trim(strip_tags((string) $c)))
            ->filter()
            ->values();

        $points = $lines->map(fn ($t, $i) => ($i + 1).". ".$t)->implode("\n");

        $title = (string) $set->title;
        $from = optional($set->valid_from)->format('Y-m-d');
        $until = optional($set->valid_until)->format('Y-m-d');

        return <<<PROMPT
Napisz krótkie, zwięzłe streszczenie ogłoszeń parafialnych po polsku.

Wymagania:
- 3 do 7 zdań
- bez wypunktowań
- neutralny, oficjalny styl
- nie dodawaj informacji spoza treści
- nie cytuj dosłownie długich fragmentów

Zestaw: "{$title}"
Okres: {$from} – {$until}

Treść ogłoszeń:
{$points}
PROMPT;
    }

    public function generateSummary(AnnouncementSet $set): string
    {
        return $this->gemini->generateText($this->buildPrompt($set));
    }
}
