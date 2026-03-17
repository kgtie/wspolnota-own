<?php

namespace App\Observers;

use App\Events\AnnouncementPackagePublished;
use App\Models\AnnouncementSet;

class AnnouncementSetObserver
{
    public function saved(AnnouncementSet $set): void
    {
        if ($this->becamePublished($set)) {
            event(new AnnouncementPackagePublished($set->fresh()));
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
