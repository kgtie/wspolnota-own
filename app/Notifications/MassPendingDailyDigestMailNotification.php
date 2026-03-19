<?php

namespace App\Notifications;

use App\Models\Mass;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class MassPendingDailyDigestMailNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersWspolnotaMailMessage;

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
        $firstMass = $this->masses->first();
        $parish = $firstMass?->parish()->first();
        $items = $this->masses
            ->sortBy('celebration_at')
            ->map(function (Mass $mass): string {
                $date = optional($mass->celebration_at)?->format('d.m.Y');
                $time = optional($mass->celebration_at)?->format('H:i');
                $intention = filled($mass->intention_title) ? ' - '.$mass->intention_title : '';

                return trim(($date ?: 'brak daty').' '.$time).$intention;
            })
            ->values()
            ->all();

        return $this->wspolnotaMailMessage(
            subject: 'Dzisiejsze msze z Twoim uczestnictwem',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Przypomnienie o mszach',
                'title' => 'Dzisiejsze msze z Twoim uczestnictwem.',
                'intro' => 'Przypominamy o wszystkich dzisiejszych mszach, na ktore zapisales swoje uczestnictwo.',
                'bullets' => $items,
                'actionLabel' => 'Przejdz do Wspolnoty',
                'actionUrl' => route('dashboard'),
                'outro' => 'Szczegoly znajdziesz w aplikacji i w przypomnieniach na telefonie.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Przypomnienie o mszach',
                'preheader' => 'Lista dzisiejszych mszy z Twoim uczestnictwem.',
                'mobile_note_variant' => 'parish',
            ],
        );
    }
}
