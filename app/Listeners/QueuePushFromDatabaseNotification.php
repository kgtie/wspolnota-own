<?php

namespace App\Listeners;

use App\Jobs\DispatchDatabaseNotificationPushJob;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;

class QueuePushFromDatabaseNotification
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        if (! $event->notifiable instanceof User) {
            return;
        }

        if (! $event->response instanceof DatabaseNotification) {
            return;
        }

        DispatchDatabaseNotificationPushJob::dispatch(
            notificationId: (string) $event->response->getKey(),
            userId: $event->notifiable->getKey(),
        );
    }
}
