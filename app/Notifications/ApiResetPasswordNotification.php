<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ApiResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset hasła')
            ->line('Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta.')
            ->action('Ustaw nowe hasło', $this->resetUrl($notifiable))
            ->line('Jeśli to nie Ty, zignoruj tę wiadomość.');
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
