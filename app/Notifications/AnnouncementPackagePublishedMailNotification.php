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
            subject: 'Nowy pakiet ogloszen parafialnych',
            htmlBodyView: 'mail.html.notifications.action-message',
            textBodyView: 'mail.text.notifications.action-message',
            bodyData: [
                'eyebrow' => 'Ogloszenia parafialne',
                'title' => 'Opublikowano nowy pakiet ogloszen.',
                'intro' => 'Nowy pakiet ogloszen jest juz dostepny: '.$this->announcementSet->title,
                'details' => [
                    'Parafia' => $parish?->name ?? 'Nie przypisano',
                    'Obowiazuje od' => $this->announcementSet->effective_from?->format('d.m.Y') ?? 'dzisiaj',
                ],
                'actionLabel' => 'Otworz Wspolnote',
                'actionUrl' => $serviceUrl,
                'outro' => 'Szczegoly znajdziesz we Wspolnocie oraz na stronie parafii.',
            ],
            parish: $parish,
            context: [
                'category_label' => 'Ogloszenia parafialne',
                'preheader' => 'Opublikowano nowy pakiet ogloszen parafialnych.',
                'mobile_note_variant' => 'parish',
            ],
        );
    }
}
