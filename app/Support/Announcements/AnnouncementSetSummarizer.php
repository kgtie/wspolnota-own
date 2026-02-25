<?php

namespace App\Support\Announcements;

use App\Models\AnnouncementSet;
use App\Support\Gemini\GeminiClient;
use Illuminate\Support\Collection;

class AnnouncementSetSummarizer
{
    public function __construct(
        protected GeminiClient $geminiClient,
    ) {}

    public function canGenerateForSet(AnnouncementSet $set): bool
    {
        return $set->status === 'published'
            && $set->items()->where('is_active', true)->exists();
    }

    public function canBeSummarized(AnnouncementSet $set): bool
    {
        return $this->canGenerateForSet($set)
            && blank($set->summary_ai)
            && $set->summary_generated_at === null;
    }

    public function summarize(AnnouncementSet $set): string
    {
        $set->loadMissing(['parish']);

        $items = $set->items()
            ->where('is_active', true)
            ->orderBy('position')
            ->get(['position', 'title', 'content', 'is_important']);

        $prompt = $this->buildPrompt($set, $items);
        $summary = $this->geminiClient->generate([
            ['text' => $prompt],
        ]);

        return $this->normalizeSummary($summary);
    }

    protected function buildPrompt(AnnouncementSet $set, Collection $items): string
    {
        $lines = [];

        foreach ($items as $item) {
            $label = $item->is_important ? '[WAZNE]' : '[STANDARD]';
            $title = $item->title ? "{$item->title}: " : '';
            $lines[] = sprintf('%s %s%s', $label, $title, $item->content);
        }

        $context = [
            'Parafia: '.($set->parish?->name ?? 'Nieznana'),
            'Tytul zestawu: '.$set->title,
            'Okres: '.($set->effective_from?->format('d.m.Y') ?? 'brak')
                .($set->effective_to ? ' - '.$set->effective_to->format('d.m.Y') : ''),
            'Opis tygodnia: '.($set->week_label ?? 'brak'),
            'Wstep: '.($set->lead ?? 'brak'),
            '',
            'Pojedyncze ogloszenia:',
            ...$lines,
            '',
            'Zadanie:',
            '- Napisz streszczenie po polsku.',
            '- Dokladnie 5 albo 6 prostych zdan.',
            '- Styl prosty, duszpasterski, zrozumialy dla parafian.',
            '- Bez list punktowanych, bez naglowkow, bez emoji.',
            '- Zachowaj najwazniejsze informacje praktyczne (terminy, wydarzenia, prosby).',
            '- Nie wymyslaj nowych faktow.',
        ];

        return implode("\n", $context);
    }

    protected function normalizeSummary(string $text): string
    {
        $singleLine = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);

        return trim($singleLine);
    }
}
