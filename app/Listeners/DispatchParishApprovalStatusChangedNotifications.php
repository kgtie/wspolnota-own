<?php

namespace App\Listeners;

use App\Events\ParishApprovalStatusChanged;
use App\Notifications\ParishApprovalStatusChangedNotification;

class DispatchParishApprovalStatusChangedNotifications
{
    public function handle(ParishApprovalStatusChanged $event): void
    {
        $event->user->notify(new ParishApprovalStatusChangedNotification($event->isApproved));
    }
}
