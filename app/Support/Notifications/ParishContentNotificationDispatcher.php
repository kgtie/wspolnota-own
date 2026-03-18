<?php

namespace App\Support\Notifications;

use App\Models\AnnouncementSet;
use App\Models\NewsPost;
use App\Models\User;
use App\Notifications\AnnouncementPackagePublishedNotification;
use App\Notifications\AnnouncementPackagePublishedMailNotification;
use App\Notifications\NewsPublishedNotification;
use App\Notifications\NewsPublishedMailNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ParishContentNotificationDispatcher
{
    public function __construct(
        private readonly NotificationPreferenceResolver $preferences,
        private readonly ParishAudienceResolver $audiences,
    ) {}

    public function dispatchNews(NewsPost $news): int
    {
        $recipients = $this->recipientsForParish((int) $news->parish_id);
        $emailRecipients = $recipients
            ->filter(fn (User $user): bool => filled($user->email) && $this->preferences->wantsEmail($user, 'news'))
            ->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new NewsPublishedNotification($news));
        }

        if ($emailRecipients->isNotEmpty()) {
            Notification::send($emailRecipients, new NewsPublishedMailNotification($news));
        }

        $news->forceFill([
            'push_notification_sent_at' => now(),
            'email_notification_sent_at' => now(),
        ])->saveQuietly();

        return $recipients->count();
    }

    public function dispatchAnnouncementSet(AnnouncementSet $set): int
    {
        $recipients = $this->recipientsForParish((int) $set->parish_id);
        $emailRecipients = $recipients
            ->filter(fn (User $user): bool => filled($user->email) && $this->preferences->wantsEmail($user, 'announcements'))
            ->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AnnouncementPackagePublishedNotification($set));
        }

        if ($emailRecipients->isNotEmpty()) {
            Notification::send($emailRecipients, new AnnouncementPackagePublishedMailNotification($set));
        }

        $set->forceFill([
            'push_notification_sent_at' => now(),
            'email_notification_sent_at' => now(),
            'notifications_sent_at' => now(),
            'notifications_recipients_count' => $recipients->count(),
        ])->saveQuietly();

        return $recipients->count();
    }

    /**
     * @return Collection<int,User>
     */
    private function recipientsForParish(int $parishId): Collection
    {
        return $this->audiences->homeParishUsers($parishId);
    }
}
