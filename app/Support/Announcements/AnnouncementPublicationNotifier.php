<?php

namespace App\Support\Announcements;

use App\Mail\AnnouncementSetPublishedMessage;
use App\Models\AnnouncementSet;
use App\Models\User;
use App\Support\Notifications\NotificationPreferenceResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class AnnouncementPublicationNotifier
{
    public function __construct(private readonly NotificationPreferenceResolver $preferences) {}

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
            ->with('notificationPreference')
            ->where('role', 0)
            ->where('home_parish_id', $set->parish_id)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->get()
            ->filter(fn (User $user) => $this->preferences->wantsEmail($user, 'announcements'))
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

        foreach ($recipients as $email) {
            Mail::to((string) $email)->queue(
                new AnnouncementSetPublishedMessage(
                    announcementSet: $set,
                    parishName: $parishName,
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
            ->log('Zakolejkowano email do parafian o aktualnych ogloszeniach.');

        return $count;
    }
}
