<?php

namespace App\Support\Notifications;

use App\Models\Mass;
use App\Models\User;
use App\Notifications\MassPendingReminderMailNotification;
use App\Notifications\MassPendingReminderNotification;

class MassReminderDispatcher
{
    public function __construct(private readonly NotificationPreferenceResolver $preferences) {}

    public function dispatchPushReminder(Mass $mass, User $user, string $reminderKey): bool
    {
        if (! $this->preferences->wantsPush($user, 'mass_reminders')) {
            return false;
        }

        $user->notify(new MassPendingReminderNotification($mass, $reminderKey));

        return true;
    }

    public function dispatchMorningEmailReminder(Mass $mass, User $user): bool
    {
        if (! filled($user->email) || ! $this->preferences->wantsEmail($user, 'mass_reminders')) {
            return false;
        }

        $user->notify(new MassPendingReminderMailNotification($mass));

        return true;
    }
}
