<?php

namespace App\Notifications;

use App\Models\Mass;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MassPendingReminderMailNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersWspolnotaMailMessage;

    public function __construct(public readonly Mass $mass) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = optional($this->mass->celebration_at)?->format('d.m.Y');
        $time = optional($this->mass->celebration_at)?->format('H:i');
        $parish = $this->mass->parish()->first();

        return $this->wspolnotaMailMessage(
            subject: 'Przypomnienie o dzisiejszej mszy',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Przypomnienie o mszy',
                'title' => 'Dzisiaj masz zapisana msze.',
                'intro' => 'Przypominamy o dzisiejszej mszy, na ktora zapisales swoje uczestnictwo.',
                'details' => array_filter([
                    'Data' => $date ?: 'brak',
                    'Godzina' => $time ?: 'brak',
                    'Intencja' => filled($this->mass->intention_title) ? $this->mass->intention_title : null,
                ]),
                'actionLabel' => 'Przejdz do Wspolnoty',
                'actionUrl' => route('dashboard'),
                'outro' => 'Szczegoly znajdziesz w aplikacji i na telefonie.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Przypomnienie o mszy',
                'preheader' => 'Masz zapisana msze na dzisiaj.',
                'mobile_note_variant' => 'parish',
            ],
        );
    }
}
