<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ApiVerifyEmailNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Zweryfikuj adres e-mail')
            ->line('Kliknij przycisk poniżej, aby zweryfikować adres e-mail używany w aplikacji.')
            ->action('Zweryfikuj adres e-mail', $verificationUrl)
            ->line('Jeśli nie zakładałeś konta, zignoruj tę wiadomość.');
    }

    private function verificationUrl(object $notifiable): string
    {
        if (! $notifiable instanceof MustVerifyEmail) {
            return config('app.url');
        }

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verify-email',
            now()->addMinutes((int) config('api_auth.email_verification_ttl_minutes', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        $mobileUrl = (string) config('api_auth.mobile_email_verification_url', '');

        if ($mobileUrl === '') {
            return $signedUrl;
        }

        return $this->appendQuery($mobileUrl, [
            'verification_url' => $signedUrl,
            'email' => $notifiable->getEmailForVerification(),
        ]);
    }

    private function appendQuery(string $url, array $params): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($params);
    }
}
