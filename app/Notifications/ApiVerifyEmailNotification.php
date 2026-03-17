<?php

namespace App\Notifications;

use App\Models\Parish;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ApiVerifyEmailNotification extends Notification implements ShouldQueue
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

        $parish = $this->resolveParish($notifiable);

        if ($parish) {
            return URL::temporarySignedRoute(
                'parish.verification.verify',
                now()->addMinutes((int) config('api_auth.email_verification_ttl_minutes', 60)),
                [
                    'subdomain' => $parish->slug,
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );
        }

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verify-email',
            now()->addMinutes((int) config('api_auth.email_verification_ttl_minutes', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        return $signedUrl;
    }

    private function resolveParish(object $notifiable): ?Parish
    {
        $parishId = data_get($notifiable, 'home_parish_id');

        if (! is_numeric($parishId)) {
            return null;
        }

        return Parish::query()
            ->select(['id', 'slug'])
            ->find((int) $parishId);
    }
}
