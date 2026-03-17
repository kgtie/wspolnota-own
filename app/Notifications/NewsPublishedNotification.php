<?php

namespace App\Notifications;

use App\Models\NewsPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewsPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly NewsPost $newsPost) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'NEWS_CREATED',
            'title' => 'Nowa aktualność w parafii',
            'body' => 'Dodano nową aktualność: '.$this->newsPost->title,
            'data' => [
                'parish_id' => (string) $this->newsPost->parish_id,
                'news_id' => (string) $this->newsPost->getKey(),
            ],
        ];
    }
}
