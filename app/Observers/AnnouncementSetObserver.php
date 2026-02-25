<?php

namespace App\Observers;

use App\Models\AnnouncementSet;
use App\Support\Announcements\AnnouncementPublicationNotifier;
use Throwable;

class AnnouncementSetObserver
{
    public function saved(AnnouncementSet $set): void
    {
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
            app(AnnouncementPublicationNotifier::class)->notify($set);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
