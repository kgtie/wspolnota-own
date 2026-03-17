<?php

namespace App\Notifications;

use App\Models\NewsPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsPublishedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly NewsPost $newsPost) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nowa aktualnosc parafialna')
            ->line('Opublikowano nowa aktualnosc: '.$this->newsPost->title)
            ->line('Sprawdz ja w aplikacji mobilnej.');
    }
}
