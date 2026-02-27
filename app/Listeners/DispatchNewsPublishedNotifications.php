<?php

namespace App\Listeners;

use App\Events\NewsPublished;
use App\Models\User;
use App\Notifications\NewsPublishedNotification;

class DispatchNewsPublishedNotifications
{
    public function handle(NewsPublished $event): void
    {
        $news = $event->newsPost;

        $recipients = User::query()
            ->where('role', 0)
            ->where('status', 'active')
            ->where('home_parish_id', $news->parish_id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $recipients->each(fn (User $user) => $user->notify(new NewsPublishedNotification($news)));
    }
}
