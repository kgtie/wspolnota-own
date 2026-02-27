<?php

namespace App\Listeners;

use App\Events\AnnouncementPackagePublished;
use App\Models\User;
use App\Notifications\AnnouncementPackagePublishedNotification;

class DispatchAnnouncementPackagePublishedNotifications
{
    public function handle(AnnouncementPackagePublished $event): void
    {
        $set = $event->announcementSet;

        $recipients = User::query()
            ->where('role', 0)
            ->where('status', 'active')
            ->where('home_parish_id', $set->parish_id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $recipients->each(fn (User $user) => $user->notify(new AnnouncementPackagePublishedNotification($set)));
    }
}
