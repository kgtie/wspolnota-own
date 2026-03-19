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
            subject: 'Nowa aktualnosc parafialna',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Aktualnosci parafialne',
                'title' => 'Pojawila sie nowa aktualnosc.',
                'intro' => 'Opublikowano nowa aktualnosc: '.$this->newsPost->title,
                'details' => [
                    'Parafia' => $parish?->name ?? 'Nie przypisano',
                ],
                'actionLabel' => 'Otworz Wspolnote',
                'actionUrl' => $serviceUrl,
                'outro' => 'Szczegoly znajdziesz we Wspolnocie oraz na stronie parafii.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Aktualnosci parafialne',
                'preheader' => 'Opublikowano nowa aktualnosc parafialna.',
                'mobile_note_variant' => 'parish',
            ],
        );
    }
}
