<?php

namespace App\Notifications;

use App\Models\AnnouncementSet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPackagePublishedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly AnnouncementSet $announcementSet) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nowy pakiet ogloszen parafialnych')
            ->line('Opublikowano nowy pakiet ogloszen: '.$this->announcementSet->title)
            ->line('Szczegoly znajdziesz w aplikacji mobilnej.');
    }
}
