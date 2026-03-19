<?php

namespace App\Notifications;

use App\Models\OfficeMessage;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfficeMessageReceivedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersWspolnotaMailMessage;

    public function __construct(public readonly OfficeMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $conversation = $this->message->conversation;
        $parish = $conversation?->parish()->first();
        $sender = $this->message->sender;

        return $this->wspolnotaMailMessage(
            subject: 'Nowa wiadomosc w kancelarii online',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Kancelaria online',
                'title' => 'Otrzymales nowa wiadomosc.',
                'intro' => 'W Twojej kancelarii online pojawila sie nowa wiadomosc i czeka na odpowiedz.',
                'details' => [
                    'Parafia' => $parish?->name ?? 'Nie przypisano',
                    'Nadawca' => $sender?->full_name ?: $sender?->name ?: 'Nieznany uzytkownik',
                ],
                'actionLabel' => 'Przejdz do Wspolnoty',
                'actionUrl' => route('dashboard'),
                'outro' => 'Otworz aplikacje lub panel, aby odpowiedziec w odpowiednim czasie.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Kancelaria online',
                'preheader' => 'Nowa wiadomosc w kancelarii online.',
                'mobile_note_variant' => 'parish',
            ],
        );
    }
}
