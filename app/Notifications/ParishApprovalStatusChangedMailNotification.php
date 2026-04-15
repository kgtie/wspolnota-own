<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use App\Support\Mail\EmailThemeFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParishApprovalStatusChangedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersWspolnotaMailMessage;

    public function __construct(public readonly bool $isApproved) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $parish = app(EmailThemeFactory::class)->resolveParishFromUser($notifiable instanceof User ? $notifiable : null);

        return $this->wspolnotaMailMessage(
            subject: 'Zmiana statusu zatwierdzenia parafialnego',
            htmlBodyView: 'mail.html.notifications.simple-message',
            textBodyView: 'mail.text.notifications.simple-message',
            bodyData: [
                'eyebrow' => 'Status konta',
                'title' => $this->isApproved ? 'Konto zostalo zatwierdzone.' : 'Status zatwierdzenia zostal zmieniony.',
                'intro' => $this->isApproved
                    ? 'Twoje konto zostalo zatwierdzone przez parafie.'
                    : 'Status zatwierdzenia parafialnego Twojego konta zostal cofniety.',
                'outro' => 'Szczegoly znajdziesz we Wspolnocie.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Status konta',
                'preheader' => 'Zmienil sie status zatwierdzenia parafialnego Twojego konta.',
                'mobile_note_variant' => $parish ? 'parish' : 'default',
            ],
        );
    }
}
