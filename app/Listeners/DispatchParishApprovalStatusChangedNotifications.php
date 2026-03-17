<?php

namespace App\Listeners;

use App\Events\ParishApprovalStatusChanged;
use App\Notifications\ParishApprovalStatusChangedMailNotification;
use App\Notifications\ParishApprovalStatusChangedNotification;
use App\Support\Notifications\NotificationPreferenceResolver;

class DispatchParishApprovalStatusChangedNotifications
{
    public function __construct(private readonly NotificationPreferenceResolver $preferences) {}

    public function handle(ParishApprovalStatusChanged $event): void
    {
        $event->user->notify(new ParishApprovalStatusChangedNotification($event->isApproved));

        if (filled($event->user->email) && $this->preferences->wantsEmail($event->user, 'parish_approval_status')) {
            $event->user->notify(new ParishApprovalStatusChangedMailNotification($event->isApproved));
        }
    }
}
