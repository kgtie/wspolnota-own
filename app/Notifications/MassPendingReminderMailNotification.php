<?php

namespace App\Notifications;

use App\Models\Mass;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MassPendingReminderMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Mass $mass) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = optional($this->mass->celebration_at)?->format('d.m.Y');
        $time = optional($this->mass->celebration_at)?->format('H:i');

        return (new MailMessage)
            ->subject('Przypomnienie o dzisiejszej mszy')
            ->line('Przypominamy o dzisiejszej mszy, na ktora zapisales swoje uczestnictwo.')
            ->line('Data: '.($date ?: 'brak'))
            ->line('Godzina: '.($time ?: 'brak'))
            ->when(filled($this->mass->intention_title), fn (MailMessage $mail) => $mail->line('Intencja: '.$this->mass->intention_title))
            ->line('Szczegoly znajdziesz w aplikacji mobilnej.');
    }
}
