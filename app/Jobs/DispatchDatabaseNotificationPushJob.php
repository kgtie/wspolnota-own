<?php

namespace App\Jobs;

use App\Models\User;
use App\Support\Push\PushDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\DatabaseNotification;

class DispatchDatabaseNotificationPushJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $notificationId,
        public readonly int|string $userId,
    ) {}

    public function handle(PushDispatchService $dispatcher): void
    {
        $user = User::query()
            ->with(['devices', 'notificationPreference'])
            ->find($this->userId);

        $notification = DatabaseNotification::query()->whereKey($this->notificationId)->first();

        if (! $user instanceof User || ! $notification instanceof DatabaseNotification) {
            return;
        }

        $dispatcher->dispatchDatabaseNotification($user, $notification);
    }
}
