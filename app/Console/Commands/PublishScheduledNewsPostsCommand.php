<?php

namespace App\Console\Commands;

use App\Models\NewsPost;
use Illuminate\Console\Command;
use Throwable;

class PublishScheduledNewsPostsCommand extends Command
{
    protected $signature = 'news:publish-scheduled {--limit=150 : Maksymalna liczba wpisow do publikacji podczas jednego przebiegu}';

    protected $description = 'Publikuje zaplanowane aktualnosci, ktorych czas publikacji juz nadszedl.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $now = now();

        activity('news-posts')
            ->event('scheduled_news_publish_job_started')
            ->withProperties([
                'limit' => $limit,
                'run_at' => $now->toIso8601String(),
            ])
            ->log('Uruchomiono proces publikacji zaplanowanych aktualnosci.');

        try {
            $posts = NewsPost::query()
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_for')
                ->where('scheduled_for', '<=', $now)
                ->orderBy('scheduled_for')
                ->limit($limit)
                ->get();

            if ($posts->isEmpty()) {
                $this->info('Brak wpisow zaplanowanych do publikacji.');

                activity('news-posts')
                    ->event('scheduled_news_publish_job_noop')
                    ->withProperties([
                        'limit' => $limit,
                        'run_at' => $now->toIso8601String(),
                    ])
                    ->log('Proces publikacji zaplanowanych aktualnosci zakonczyl sie bez zmian.');

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

            activity('news-posts')
                ->event('scheduled_news_publish_job_finished')
                ->withProperties([
                    'limit' => $limit,
                    'analyzed' => $posts->count(),
                    'published' => $published,
                    'run_at' => $now->toIso8601String(),
                ])
                ->log('Zakonczono proces publikacji zaplanowanych aktualnosci.');

            $this->info("Opublikowano {$published} zaplanowanych wpisow.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);

            activity('news-posts')
                ->event('scheduled_news_publish_job_failed')
                ->withProperties([
                    'limit' => $limit,
                    'run_at' => $now->toIso8601String(),
                    'error' => $exception->getMessage(),
                ])
                ->log('Proces publikacji zaplanowanych aktualnosci zakonczyl sie bledem.');

            $this->error("Blad podczas publikacji zaplanowanych wpisow: {$exception->getMessage()}");

            return self::FAILURE;
        }
    }
}
