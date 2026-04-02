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
            subject: 'Nowa wiadomość w kancelarii online',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Kancelaria online',
                'title' => 'Otrzymałeś nową wiadomość.',
                'intro' => 'W Twojej kancelarii online pojawiła się nowa wiadomość i czeka na odpowiedź.',
                'details' => [
                    'Parafia' => $parish?->name ?? 'Nie przypisano',
                    'Nadawca' => $sender?->full_name ?: $sender?->name ?: 'Nieznany użytkownik',
                ],
                'actionLabel' => 'Przejdź do Wspólnoty',
                'actionUrl' => route('dashboard'),
                'outro' => 'Otwórz aplikację lub panel, aby odpowiedzieć w odpowiednim czasie.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Kancelaria online',
                'preheader' => 'Nowa wiadomość w kancelarii online.',
                'mobile_note_variant' => 'parish',
            ],
        );
    }
}
