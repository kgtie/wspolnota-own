<?php

namespace App\Observers;

use App\Events\AnnouncementPackagePublished;
use App\Models\AnnouncementSet;
use App\Support\Announcements\AnnouncementPublicationNotifier;
use Throwable;

class AnnouncementSetObserver
{
    public function saved(AnnouncementSet $set): void
    {
        if ($this->becamePublished($set)) {
            event(new AnnouncementPackagePublished($set->fresh()));
        }

        if (! $set->wasRecentlyCreated && ! $set->wasChanged(['status', 'effective_from', 'effective_to', 'parish_id'])) {
            return;
        }

        if ($set->status !== 'published' || $set->notifications_sent_at !== null || ! $set->isCurrent()) {
            return;
        }

        if (! $set->parish?->getSetting('announcements_push_on_publish', true)) {
            return;
        }

        try {
            activity('announcements-notifications')
                ->performedOn($set)
                ->event('announcement_set_notification_observer_triggered')
                ->withProperties([
                    'parish_id' => $set->parish_id,
                    'announcement_set_id' => $set->getKey(),
                    'reason' => 'saved_event_current_published_set',
                ])
                ->log('Observer uruchomil natychmiastowa wysylke emaila o aktualnych ogloszeniach.');

            app(AnnouncementPublicationNotifier::class)->notify($set, 'observer');
        } catch (Throwable $exception) {
            report($exception);

            activity('announcements-notifications')
                ->performedOn($set)
                ->event('announcement_set_notification_observer_failed')
                ->withProperties([
                    'parish_id' => $set->parish_id,
                    'announcement_set_id' => $set->getKey(),
                    'error' => $exception->getMessage(),
                ])
                ->log('Observer nie wyslal emaila o aktualnych ogloszeniach z powodu bledu.');
        }
    }

    private function becamePublished(AnnouncementSet $set): bool
    {
        if ($set->status !== 'published') {
            return false;
        }

        if ($set->wasRecentlyCreated) {
            return true;
        }

        return $set->wasChanged('status');
    }
}
