<?php

namespace App\Support\Announcements;

use App\Mail\AnnouncementSetPublishedMessage;
use App\Models\AnnouncementSet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class AnnouncementPublicationNotifier
{
    public function shouldNotify(AnnouncementSet $set, \DateTimeInterface|string|null $date = null): bool
    {
        $currentDate = $date instanceof \DateTimeInterface
            ? $date
            : ($date ? Carbon::parse((string) $date) : now());

        return $set->status === 'published'
            && $set->notifications_sent_at === null
            && $set->isCurrent($currentDate)
            && $set->parish?->getSetting('announcements_push_on_publish', true);
    }

    public function notify(AnnouncementSet $set, string $source = 'manual'): int
    {
        $set->loadMissing('parish');

        $recipients = User::query()
            ->where('role', 0)
            ->where('home_parish_id', $set->parish_id)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            $set->forceFill([
                'notifications_sent_at' => now(),
                'notifications_recipients_count' => 0,
            ])->saveQuietly();

            activity('announcements-notifications')
                ->performedOn($set)
                ->event('announcement_set_notification_marked_without_recipients')
                ->withProperties([
                    'parish_id' => $set->parish_id,
                    'announcement_set_id' => $set->getKey(),
                    'source' => $source,
                    'recipients_count' => 0,
                ])
                ->log('Zestaw ogloszen oznaczono jako obsluzony bez wysylki (brak odbiorcow email).');

            return 0;
        }

        $parishName = $set->parish?->name ?? 'Parafia';
        $announcementsUrl = route('app.announcements', ['parish' => $set->parish?->slug ?? $set->parish_id]);

        foreach ($recipients as $email) {
            Mail::to((string) $email)->send(
                new AnnouncementSetPublishedMessage(
                    announcementSet: $set,
                    parishName: $parishName,
                    announcementsUrl: $announcementsUrl,
                ),
            );
        }

        $count = $recipients->count();

        $set->forceFill([
            'notifications_sent_at' => now(),
            'notifications_recipients_count' => $count,
        ])->saveQuietly();

        activity('announcements-notifications')
            ->performedOn($set)
            ->event('announcement_set_notification_sent')
            ->withProperties([
                'parish_id' => $set->parish_id,
                'announcement_set_id' => $set->getKey(),
                'source' => $source,
                'recipients_count' => $count,
            ])
            ->log('Wyslano email do parafian o aktualnych ogloszeniach.');

        return $count;
    }
}
