<?php

namespace App\Support\SuperAdmin;

use App\Models\MailingMail;
use App\Models\User;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommunicationAudienceResolver
{
    public function __construct(private readonly NotificationPreferenceResolver $preferences) {}

    public function resolveEmailRecipients(array $payload): Collection
    {
        $scope = (string) ($payload['recipient_scope'] ?? 'single_users');
        $rows = match ($scope) {
            'mailing_list' => $this->mailingListRecipients(
                $payload['target_mailing_list_id'] ?? null,
                (bool) ($payload['mailing_only_confirmed'] ?? true),
            ),
            'custom_emails' => $this->customEmailRecipients((string) ($payload['custom_emails'] ?? '')),
            default => $this->resolveUserRecipients($payload)
                ->filter(fn (User $user): bool => filled($user->email) && $this->preferences->wantsEmail($user, $this->resolvePreferenceTopic($payload)))
                ->map(fn (User $user): array => $this->mapUserRecipient($user)),
        };

        return $rows
            ->filter(fn (array $recipient): bool => filled($recipient['email']))
            ->unique(fn (array $recipient): string => mb_strtolower((string) $recipient['email']))
            ->values();
    }

    public function resolvePushRecipients(array $payload): Collection
    {
        return $this->resolveUserRecipients($payload, withDevices: true)
            ->filter(fn (User $user): bool => $this->preferences->wantsPush($user, $this->resolvePreferenceTopic($payload)))
            ->filter(fn (User $user): bool => $this->userHasPushableDevice($user))
            ->values();
    }

    private function resolveUserRecipients(array $payload, bool $withDevices = false): Collection
    {
        $scope = (string) ($payload['recipient_scope'] ?? 'single_users');
        $usersQuery = $this->baseUsersQuery($payload, $withDevices);

        return match ($scope) {
            'single_users' => $this->singleUsers($usersQuery, (array) ($payload['selected_user_ids'] ?? [])),
            'parishioners_all' => $this->usersByRole($usersQuery, 0),
            'admins_all' => $this->usersByRole($usersQuery, 1),
            'admins_and_superadmins' => $this->adminsAndSuperadmins($usersQuery),
            'users_by_parish' => $this->usersByParish($usersQuery, $payload['target_parish_id'] ?? null),
            'verified_users' => $this->verifiedUsers($usersQuery),
            'users_with_push_devices' => $this->usersWithPushDevices($usersQuery),
            'email_topic_opt_in', 'all_users' => $this->allUsers($usersQuery),
            default => collect(),
        };
    }

    private function baseUsersQuery(array $payload, bool $withDevices = false): Builder
    {
        $query = User::query()
            ->with($withDevices ? ['notificationPreference', 'devices'] : ['notificationPreference'])
            ->select(['id', 'email', 'full_name', 'name', 'status', 'role', 'is_user_verified', 'email_verified_at', 'home_parish_id']);

        if (! (bool) ($payload['include_inactive_users'] ?? false)) {
            $query->where('status', 'active');
        }

        if ((bool) ($payload['only_verified_users'] ?? false)) {
            $query->where('is_user_verified', true);
        }

        if ((bool) ($payload['only_email_verified_users'] ?? false)) {
            $query->whereNotNull('email_verified_at');
        }

        if ((bool) ($payload['only_users_with_push_devices'] ?? false)) {
            $query->whereHas('devices', fn ($deviceQuery) => $deviceQuery->pushable());
        }

        return $query;
    }

    private function singleUsers(Builder $usersQuery, array $selectedUserIds): Collection
    {
        if ($selectedUserIds === []) {
            return collect();
        }

        return $usersQuery
            ->whereIn('id', $selectedUserIds)
            ->get();
    }

    private function usersByRole(Builder $usersQuery, int $role): Collection
    {
        return $usersQuery
            ->where('role', $role)
            ->get();
    }

    private function adminsAndSuperadmins(Builder $usersQuery): Collection
    {
        return $usersQuery
            ->where('role', '>=', 1)
            ->get();
    }

    private function usersByParish(Builder $usersQuery, mixed $parishId): Collection
    {
        if (! is_numeric($parishId)) {
            return collect();
        }

        return $usersQuery
            ->where('home_parish_id', (int) $parishId)
            ->get();
    }

    private function verifiedUsers(Builder $usersQuery): Collection
    {
        return $usersQuery
            ->where('is_user_verified', true)
            ->get();
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

    private function usersWithPushDevices(Builder $usersQuery): Collection
    {
        return $usersQuery
            ->whereHas('devices', fn ($query) => $query->pushable())
            ->get();
    }

    private function allUsers(Builder $usersQuery): Collection
    {
        return $usersQuery->get();
    }

    private function resolvePreferenceTopic(array $payload): string
    {
        return (string) ($payload['notification_preference_topic'] ?? $payload['email_preference_topic'] ?? 'manual_messages');
    }

    private function userHasPushableDevice(User $user): bool
    {
        if (! $user->relationLoaded('devices')) {
            return $user->devices()->pushable()->exists();
        }

        return $user->devices->contains(
            fn ($device): bool => $device->disabled_at === null
                && in_array($device->permission_status, ['authorized', 'provisional'], true)
                && filled($device->push_token),
        );
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
