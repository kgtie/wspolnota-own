<?php

namespace App\Support\SuperAdmin;

use App\Models\MailingMail;
use App\Models\User;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Support\Collection;

class CommunicationAudienceResolver
{
    public function __construct(private readonly NotificationPreferenceResolver $preferences) {}

    public function resolveEmailRecipients(array $payload): Collection
    {
        $scope = (string) ($payload['recipient_scope'] ?? 'single_users');
        $usersQuery = User::query()
            ->with('notificationPreference')
            ->select(['id', 'email', 'full_name', 'name', 'status', 'role', 'is_user_verified', 'email_verified_at', 'home_parish_id']);

        if (! (bool) ($payload['include_inactive_users'] ?? false)) {
            $usersQuery->where('status', 'active');
        }

        if ((bool) ($payload['only_verified_users'] ?? false)) {
            $usersQuery->where('is_user_verified', true);
        }

        if ((bool) ($payload['only_email_verified_users'] ?? false)) {
            $usersQuery->whereNotNull('email_verified_at');
        }

        if ((bool) ($payload['only_users_with_push_devices'] ?? false)) {
            $usersQuery->whereHas('devices', fn ($query) => $query->pushable());
        }

        $rows = match ($scope) {
            'single_users' => $this->singleUsersRecipients($usersQuery, (array) ($payload['selected_user_ids'] ?? [])),
            'parishioners_all' => $this->usersByRoleRecipients($usersQuery, 0),
            'admins_all' => $this->usersByRoleRecipients($usersQuery, 1),
            'admins_and_superadmins' => $this->adminsAndSuperadminsRecipients($usersQuery),
            'users_by_parish' => $this->usersByParishRecipients($usersQuery, $payload['target_parish_id'] ?? null),
            'verified_users' => $this->verifiedUsersRecipients($usersQuery),
            'mailing_list' => $this->mailingListRecipients(
                $payload['target_mailing_list_id'] ?? null,
                (bool) ($payload['mailing_only_confirmed'] ?? true),
            ),
            'users_with_push_devices' => $this->usersWithPushDevicesRecipients($usersQuery),
            'email_topic_opt_in' => $this->emailTopicRecipients(
                $usersQuery,
                (string) ($payload['email_preference_topic'] ?? 'announcements'),
            ),
            'all_users' => $this->allUsersRecipients($usersQuery),
            'custom_emails' => $this->customEmailRecipients((string) ($payload['custom_emails'] ?? '')),
            default => collect(),
        };

        if ((bool) ($payload['respect_email_preferences'] ?? false)) {
            $topic = (string) ($payload['email_preference_topic'] ?? 'announcements');

            $rows = $rows->filter(function (array $recipient) use ($topic): bool {
                $userId = $recipient['user_id'] ?? null;

                if (! is_int($userId)) {
                    return true;
                }

                $user = User::query()
                    ->with('notificationPreference')
                    ->find($userId);

                return $user instanceof User
                    ? $this->preferences->wantsEmail($user, $topic)
                    : true;
            });
        }

        return $rows
            ->filter(fn (array $recipient): bool => filled($recipient['email']))
            ->unique(fn (array $recipient): string => mb_strtolower((string) $recipient['email']))
            ->values();
    }

    public function resolvePushRecipients(array $payload): Collection
    {
        $emailRecipients = $this->resolveEmailRecipients([
            ...$payload,
            'only_users_with_push_devices' => true,
            'custom_emails' => '',
        ]);

        $userIds = $emailRecipients
            ->pluck('user_id')
            ->filter(fn ($id): bool => is_int($id))
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->with('devices')
            ->whereIn('id', $userIds)
            ->get();
    }

    private function singleUsersRecipients($usersQuery, array $selectedUserIds): Collection
    {
        if ($selectedUserIds === []) {
            return collect();
        }

        return $usersQuery
            ->whereIn('id', $selectedUserIds)
            ->whereNotNull('email')
            ->get()
            ->map(fn (User $user): array => $this->mapUserRecipient($user));
    }

    private function usersByRoleRecipients($usersQuery, int $role): Collection
    {
        return $usersQuery
            ->where('role', $role)
            ->whereNotNull('email')
            ->get()
            ->map(fn (User $user): array => $this->mapUserRecipient($user));
    }

    private function adminsAndSuperadminsRecipients($usersQuery): Collection
    {
        return $usersQuery
            ->where('role', '>=', 1)
            ->whereNotNull('email')
            ->get()
            ->map(fn (User $user): array => $this->mapUserRecipient($user));
    }

    private function usersByParishRecipients($usersQuery, mixed $parishId): Collection
    {
        if (! is_numeric($parishId)) {
            return collect();
        }

        return $usersQuery
            ->where('home_parish_id', (int) $parishId)
            ->whereNotNull('email')
            ->get()
            ->map(fn (User $user): array => $this->mapUserRecipient($user));
    }

    private function verifiedUsersRecipients($usersQuery): Collection
    {
        return $usersQuery
            ->where('is_user_verified', true)
            ->whereNotNull('email')
            ->get()
            ->map(fn (User $user): array => $this->mapUserRecipient($user));
    }

    private function mailingListRecipients(mixed $listId, bool $confirmedOnly): Collection
    {
        if (! is_numeric($listId)) {
            return collect();
        }

        return MailingMail::query()
            ->where('mailing_list_id', (int) $listId)
            ->when($confirmedOnly, fn ($query) => $query->whereNotNull('confirmed_at'))
            ->get(['email'])
            ->map(fn (MailingMail $mail): array => [
                'user_id' => null,
                'parish_id' => null,
                'email' => (string) $mail->email,
                'label' => (string) $mail->email,
            ]);
    }

    private function usersWithPushDevicesRecipients($usersQuery): Collection
    {
        return $usersQuery
            ->whereHas('devices', fn ($query) => $query->pushable())
            ->whereNotNull('email')
            ->get()
            ->map(fn (User $user): array => $this->mapUserRecipient($user));
    }

    private function emailTopicRecipients($usersQuery, string $topic): Collection
    {
        return $usersQuery
            ->whereNotNull('email')
            ->get()
            ->filter(fn (User $user): bool => $this->preferences->wantsEmail($user, $topic))
            ->values()
            ->map(fn (User $user): array => $this->mapUserRecipient($user));
    }

    private function allUsersRecipients($usersQuery): Collection
    {
        return $usersQuery
            ->whereNotNull('email')
            ->get()
            ->map(fn (User $user): array => $this->mapUserRecipient($user));
    }

    private function customEmailRecipients(string $raw): Collection
    {
        return collect(preg_split('/[\s,;]+/', $raw) ?: [])
            ->map(fn ($email): string => mb_strtolower(trim((string) $email)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->map(fn (string $email): array => [
                'user_id' => null,
                'parish_id' => null,
                'email' => $email,
                'label' => $email,
            ]);
    }

    private function mapUserRecipient(User $user): array
    {
        return [
            'user_id' => (int) $user->getKey(),
            'parish_id' => $user->home_parish_id ? (int) $user->home_parish_id : null,
            'email' => (string) $user->email,
            'label' => $user->full_name ?: $user->name ?: $user->email,
        ];
    }
}
