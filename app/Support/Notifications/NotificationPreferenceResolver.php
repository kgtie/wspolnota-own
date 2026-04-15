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
            'mass_reminders' => (bool) $preferences->mass_reminders_email,
            'office_messages' => (bool) $preferences->office_messages_email,
            'parish_approval_status' => (bool) $preferences->parish_approval_status_email,
            'manual_messages' => (bool) $preferences->manual_messages_email,
            default => false,
        };
    }

    public function wantsPush(User $user, string $topic): bool
    {
        $preferences = $user->notificationPreference;

        if (! $preferences) {
            return $this->defaultPush($topic);
        }

        return match ($topic) {
            'news' => (bool) $preferences->news_push,
            'announcements' => (bool) $preferences->announcements_push,
            'mass_reminders' => (bool) $preferences->mass_reminders_push,
            'office_messages' => (bool) $preferences->office_messages_push,
            'parish_approval_status' => (bool) $preferences->parish_approval_status_push,
            'auth_security' => (bool) $preferences->auth_security_push,
            'manual_messages' => (bool) $preferences->manual_messages_push,
            default => false,
        };
    }

    private function defaultEmail(string $topic): bool
    {
        return match ($topic) {
            'news' => false,
            'announcements' => true,
            'mass_reminders' => true,
            'office_messages' => true,
            'parish_approval_status' => true,
            'manual_messages' => true,
            default => false,
        };
    }

    private function defaultPush(string $topic): bool
    {
        return false;
    }
}
