<?php

namespace App\Notifications;

use App\Models\OfficeMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfficeMessageReceivedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly OfficeMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nowa wiadomosc w kancelarii online')
            ->line('Otrzymales nowa wiadomosc w kancelarii online.')
            ->line('Otworz aplikacje, aby odpowiedziec.');
    }
}
