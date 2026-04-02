<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use App\Support\Mail\EmailThemeFactory;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QueuedVerifyEmailNotification extends VerifyEmail implements ShouldQueue
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

        return $this->wspolnotaMailMessage(
            subject: 'Zweryfikuj adres e-mail',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Aktywacja konta',
                'title' => 'Zweryfikuj swój adres e-mail.',
                'intro' => 'Kliknij przycisk, aby potwierdzić adres e-mail i aktywować pełny dostęp do Wspólnoty.',
                'details' => [
                    'Adres e-mail' => (string) data_get($notifiable, 'email'),
                ],
                'actionLabel' => 'Zweryfikuj adres e-mail',
                'actionUrl' => $this->verificationUrl($notifiable),
                'outro' => 'Jeśli nie rejestrowałeś konta, zignoruj tę wiadomość.',
                'secondaryText' => 'Po weryfikacji łatwiej dokończysz sprawy w aplikacji i panelach Wspólnoty.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Weryfikacja e-maila',
                'preheader' => 'Potwierdź adres e-mail dla konta Wspólnota.',
                'mobile_note_variant' => $parish ? 'parish' : 'default',
                'footer_note' => 'To powiadomienie pomaga bezpiecznie aktywować konto Wspólnoty.',
            ],
        );
    }
}
