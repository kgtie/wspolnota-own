<?php

namespace App\Notifications;

use App\Models\AnnouncementSet;
use App\Notifications\Concerns\RendersWspolnotaMailMessage;
use App\Support\Mail\EmailThemeFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPackagePublishedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersWspolnotaMailMessage;

    public function __construct(public readonly AnnouncementSet $announcementSet) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $parish = $this->announcementSet->parish()->first();
        $serviceUrl = app(EmailThemeFactory::class)->resolveServiceUrl();

        return $this->wspolnotaMailMessage(
            subject: 'Nowy pakiet ogłoszeń parafialnych',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Ogłoszenia parafialne',
                'title' => 'Opublikowano nowy pakiet ogłoszeń.',
                'intro' => 'Nowy pakiet ogłoszeń jest już dostępny: '.$this->announcementSet->title,
                'details' => [
                    'Parafia' => $parish?->name ?? 'Nie przypisano',
                    'Obowiązuje od' => $this->announcementSet->effective_from?->format('d.m.Y') ?? 'dzisiaj',
                ],
                'actionLabel' => 'Otwórz Wspólnotę',
                'actionUrl' => $serviceUrl,
                'outro' => 'Szczegóły znajdziesz we Wspólnocie oraz na stronie parafii.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Ogłoszenia parafialne',
                'preheader' => 'Opublikowano nowy pakiet ogłoszeń parafialnych.',
                'mobile_note_variant' => 'parish',
            ],
        );
    }
}
