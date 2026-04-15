<?php

namespace App\Listeners;

use App\Events\OfficeMessageReceived;
use App\Models\User;
use App\Notifications\OfficeMessageReceivedMailNotification;
use App\Notifications\OfficeMessageReceivedNotification;
use App\Support\Notifications\NotificationPreferenceResolver;

class DispatchOfficeMessageReceivedNotifications
{
    public function __construct(private readonly NotificationPreferenceResolver $preferences) {}

    public function handle(OfficeMessageReceived $event): void
    {
        $message = $event->message;
        $conversation = $message->conversation;

        if (! $conversation) {
            return;
        }

        $recipientId = (int) $message->sender_user_id === (int) $conversation->parishioner_user_id
            ? (int) $conversation->priest_user_id
            : (int) $conversation->parishioner_user_id;

        if ($recipientId === (int) $message->sender_user_id) {
            return;
        }

        $recipient = $recipientId > 0
            ? User::query()->where('status', 'active')->find($recipientId)
            : null;

        if (! $recipient) {
            return;
        }

        $recipient->notify(new OfficeMessageReceivedNotification($message));

        if (filled($recipient->email) && $this->preferences->wantsEmail($recipient, 'office_messages')) {
            $recipient->notify(new OfficeMessageReceivedMailNotification($message));
        }
    }
}
