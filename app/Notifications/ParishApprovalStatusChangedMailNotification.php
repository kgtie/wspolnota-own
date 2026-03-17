<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParishApprovalStatusChangedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly bool $isApproved) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Zmiana statusu zatwierdzenia parafialnego')
            ->line($this->isApproved
                ? 'Twoje konto zostalo zatwierdzone przez parafie.'
                : 'Status zatwierdzenia parafialnego Twojego konta zostal cofniety.')
            ->line('Szczegoly znajdziesz w aplikacji.');
    }
}
