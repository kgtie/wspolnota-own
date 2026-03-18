<?php

namespace App\Notifications;

use App\Models\Mass;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class MassPendingDailyDigestMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int,Mass>  $masses
     */
    public function __construct(public readonly Collection $masses) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Dzisiejsze msze z Twoim uczestnictwem')
            ->line('Przypominamy o wszystkich dzisiejszych mszach, na ktore zapisales swoje uczestnictwo.');

        foreach ($this->masses->sortBy('celebration_at') as $mass) {
            $date = optional($mass->celebration_at)?->format('d.m.Y');
            $time = optional($mass->celebration_at)?->format('H:i');
            $intention = filled($mass->intention_title) ? ' - '.$mass->intention_title : '';

            $mail->line('- '.trim(($date ?: 'brak daty').' '.$time).$intention);
        }

        return $mail->line('Szczegoly znajdziesz w aplikacji mobilnej.');
    }
}
