<?php

namespace App\Support\Notifications;

use App\Models\User;

class NotificationPreferenceResolver
{
    public function wantsEmail(User $user, string $topic): bool
    {
        $preferences = $user->notificationPreference;

        if (! $preferences) {
            return $this->defaultEmail($topic);
        }

        return match ($topic) {
            'news' => (bool) $preferences->news_email,
            'announcements' => (bool) $preferences->announcements_email,
            'office_messages' => (bool) $preferences->office_messages_email,
            'parish_approval_status' => (bool) $preferences->parish_approval_status_email,
            default => false,
        };
    }

    private function defaultEmail(string $topic): bool
    {
        return match ($topic) {
            'news' => false,
            'announcements' => true,
            'office_messages' => true,
            'parish_approval_status' => true,
            default => false,
        };
    }
}
