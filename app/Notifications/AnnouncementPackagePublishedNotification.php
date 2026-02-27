<?php

namespace App\Notifications;

use App\Models\AnnouncementSet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementPackagePublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly AnnouncementSet $announcementSet) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ANNOUNCEMENTS_PACKAGE_PUBLISHED',
            'title' => 'Nowy pakiet ogłoszeń',
            'body' => 'Opublikowano nowy pakiet ogłoszeń: '.$this->announcementSet->title,
            'data' => [
                'parish_id' => (string) $this->announcementSet->parish_id,
                'announcement_set_id' => (string) $this->announcementSet->getKey(),
            ],
        ];
    }
}
