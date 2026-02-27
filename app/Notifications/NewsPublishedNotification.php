<?php

namespace App\Notifications;

use App\Models\NewsPost;
use App\Models\User;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly NewsPost $newsPost) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User
            && app(NotificationPreferenceResolver::class)->wantsEmail($notifiable, 'news')
            && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nowa aktualność parafialna')
            ->line('Opublikowano nową aktualność: '.$this->newsPost->title)
            ->line('Sprawdź ją w aplikacji mobilnej.');
    }
}
