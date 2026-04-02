<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use App\Support\Mail\EmailThemeFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ApiResetPasswordNotification extends ResetPassword implements ShouldQueue
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
            subject: 'Reset hasła',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Bezpieczeństwo konta',
                'title' => 'Ustaw nowe hasło.',
                'intro' => 'Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta.',
                'details' => [
                    'Adres e-mail' => (string) data_get($notifiable, 'email'),
                    'Tryb' => 'Aplikacja mobilna / API',
                ],
                'actionLabel' => 'Ustaw nowe hasło',
                'actionUrl' => $this->actionUrlFor($notifiable),
                'outro' => 'Jeśli to nie Ty, zignoruj tę wiadomość.',
                'secondaryText' => 'Link prowadzi do bezpiecznego ustawienia nowego hasła.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Reset hasła',
                'preheader' => 'Ustaw nowe hasło do konta Wspólnota.',
                'mobile_note_variant' => $parish ? 'parish' : 'default',
                'footer_note' => 'To powiadomienie służy wyłącznie do bezpiecznej zmiany hasła.',
            ],
        );
    }

    public function actionUrlFor($notifiable): string
    {
        return $this->resetUrl($notifiable);
    }

    protected function resetUrl($notifiable): string
    {
        $mobileUrl = (string) config('api_auth.mobile_password_reset_url', '');

        if ($mobileUrl !== '') {
            return $this->appendQuery($mobileUrl, [
                'token' => $this->token,
                'email' => (string) data_get($notifiable, 'email'),
            ]);
        }

        return url(route('password.reset', [
            'token' => $this->token,
            'email' => (string) data_get($notifiable, 'email'),
        ], false));
    }

    private function appendQuery(string $url, array $params): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($params);
    }
}
