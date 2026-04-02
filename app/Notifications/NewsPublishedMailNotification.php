<?php

namespace App\Notifications;

use App\Models\NewsPost;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use App\Support\Mail\EmailThemeFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsPublishedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersWspolnotaMailMessage;

    public function __construct(public readonly NewsPost $newsPost) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $parish = $this->newsPost->parish()->first();
        $serviceUrl = app(EmailThemeFactory::class)->resolveServiceUrl();

        return $this->wspolnotaMailMessage(
            subject: 'Nowa aktualność parafialna',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Aktualności parafialne',
                'title' => 'Pojawiła się nowa aktualność.',
                'intro' => 'Opublikowano nową aktualność: '.$this->newsPost->title,
                'details' => [
                    'Parafia' => $parish?->name ?? 'Nie przypisano',
                ],
                'actionLabel' => 'Otwórz Wspólnotę',
                'actionUrl' => $serviceUrl,
                'outro' => 'Szczegóły znajdziesz we Wspólnocie oraz na stronie parafii.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Aktualności parafialne',
                'preheader' => 'Opublikowano nową aktualność parafialną.',
                'mobile_note_variant' => 'parish',
            ],
        );
    }
}
