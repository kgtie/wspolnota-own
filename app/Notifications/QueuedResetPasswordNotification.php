<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use App\Support\Mail\EmailThemeFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QueuedResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;
    use RendersWspolnotaMailMessage;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $parish = app(EmailThemeFactory::class)->resolveParishFromUser($notifiable instanceof User ? $notifiable : null);
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => (string) data_get($notifiable, 'email'),
        ], false));

        return $this->wspolnotaMailMessage(
            subject: 'Reset hasla',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Bezpieczenstwo konta',
                'title' => 'Ustaw nowe haslo.',
                'intro' => 'Otrzymalismy prosbe o zresetowanie hasla do Twojego konta.',
                'details' => [
                    'Adres email' => (string) data_get($notifiable, 'email'),
                    'Tryb' => 'WWW / panele administracyjne',
                ],
                'actionLabel' => 'Ustaw nowe haslo',
                'actionUrl' => $resetUrl,
                'outro' => 'Jesli to nie Ty, zignoruj te wiadomosc.',
                'secondaryText' => 'Link przekieruje do bezpiecznej strony ustawienia nowego hasla.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Reset hasla',
                'preheader' => 'Ustaw nowe haslo do konta Wspolnota.',
                'mobile_note_variant' => $parish ? 'parish' : 'default',
                'footer_note' => 'To powiadomienie sluzy wyłącznie do bezpiecznej zmiany hasla.',
            ],
        );
    }
}
