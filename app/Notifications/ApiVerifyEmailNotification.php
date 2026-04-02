<?php

namespace App\Notifications;

use App\Models\Parish;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ApiVerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersWspolnotaMailMessage;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->actionUrlFor($notifiable);
        $parish = $this->resolveParish($notifiable);

        return $this->wspolnotaMailMessage(
            subject: 'Zweryfikuj adres e-mail',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Bezpieczeństwo konta',
                'title' => 'Zweryfikuj adres e-mail.',
                'intro' => 'Kliknij poniższy przycisk, aby potwierdzić adres e-mail używany w aplikacji i API Wspólnoty.',
                'details' => [
                    'Adres e-mail' => (string) data_get($notifiable, 'email'),
                    'Środowisko' => $parish ? 'Parafia + API mobilne' : 'API mobilne',
                ],
                'actionLabel' => 'Zweryfikuj adres e-mail',
                'actionUrl' => $verificationUrl,
                'outro' => 'Jeśli nie zakładałeś konta, zignoruj tę wiadomość.',
                'secondaryText' => 'Link jest podpisany i ważny tylko przez określony czas, aby bezpiecznie aktywować konto.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Weryfikacja e-maila',
                'preheader' => 'Potwierdź adres e-mail dla konta Wspólnota.',
                'mobile_note_variant' => $parish ? 'parish' : 'default',
                'footer_note' => 'To powiadomienie pomaga bezpiecznie aktywować dostęp do Wspólnoty.',
            ],
        );
    }

    public function actionUrlFor(object $notifiable): string
    {
        return $this->verificationUrl($notifiable);
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
