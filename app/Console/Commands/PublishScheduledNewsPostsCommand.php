<?php

namespace App\Console\Commands;

use App\Models\NewsPost;
use Illuminate\Console\Command;

class PublishScheduledNewsPostsCommand extends Command
{
    protected $signature = 'news:publish-scheduled {--limit=150 : Maksymalna liczba wpisow do publikacji podczas jednego przebiegu}';

    protected $description = 'Publikuje zaplanowane aktualnosci, ktorych czas publikacji juz nadszedl.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $now = now();

        $posts = NewsPost::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', $now)
            ->orderBy('scheduled_for')
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('Brak wpisow zaplanowanych do publikacji.');

            return self::SUCCESS;
        }

        $published = 0;

        foreach ($posts as $post) {
            $post->update([
                'status' => 'published',
                'published_at' => $post->scheduled_for ?? $now,
                'scheduled_for' => null,
            ]);

            $published++;

            activity('news-posts')
                ->performedOn($post)
                ->event('news_post_published_from_schedule')
                ->withProperties([
                    'parish_id' => $post->parish_id,
                    'news_post_id' => $post->getKey(),
                ])
                ->log('Wpis aktualnosci zostal opublikowany automatycznie zgodnie z harmonogramem.');
        }

        $this->info("Opublikowano {$published} zaplanowanych wpisow.");

        return self::SUCCESS;
    }
}
