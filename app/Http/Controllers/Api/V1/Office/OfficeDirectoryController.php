<?php

namespace App\Http\Controllers\Api\V1\Office;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Parish;
use App\Models\User;
use App\Support\Api\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Zwraca katalog odbiorców kancelarii i techniczny katalog użytkowników własnej parafii.
 */
class OfficeDirectoryController extends ApiController
{
    public function staff(Request $request, int $parishId): JsonResponse
    {
        $parish = $this->authorizedParishForRequester($request, $parishId);
        $staff = $this->staffCollectionForParish($parish->getKey());
        $defaultRecipientId = $staff->first()?->getKey();

        return $this->success([
            'parish_id' => (string) $parish->getKey(),
            'items' => $staff
                ->map(fn (User $user): array => $this->staffPayload($user, (int) $parish->getKey(), (int) $defaultRecipientId))
                ->values()
                ->all(),
        ]);
    }

    public function users(Request $request, int $parishId): JsonResponse
    {
        $parish = $this->authorizedParishForRequester($request, $parishId);
        $scope = (string) $request->query('scope', 'all');

        $query = User::query()
            ->where('home_parish_id', $parish->getKey())
            ->where('status', 'active')
            ->where('role', 0)
            ->orderByRaw("COALESCE(NULLIF(full_name, ''), NULLIF(name, ''), email)");

        if ($scope === 'email_verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($scope === 'parish_approved') {
            $query->where('is_user_verified', true);
        }

        $users = $query->get();

        return $this->success([
            'parish_id' => (string) $parish->getKey(),
            'scope' => in_array($scope, ['all', 'email_verified', 'parish_approved'], true) ? $scope : 'all',
            'items' => $users->map(fn (User $user): array => $this->directoryPayload($user))->values()->all(),
        ]);
    }

    public static function staffCollectionForParish(int $parishId): Collection
    {
        return User::query()
            ->where('role', '>=', 1)
            ->where('status', 'active')
            ->whereHas('managedParishes', function ($query) use ($parishId): void {
                $query->where('parish_id', $parishId)
                    ->where('parish_user.is_active', true);
            })
            ->with([
                'managedParishes' => function ($query) use ($parishId): void {
                    $query->where('parish_id', $parishId)
                        ->where('parish_user.is_active', true);
                },
            ])
            ->get()
            ->sortByDesc(function (User $user) use ($parishId): array {
                $meta = self::resolveStaffMeta($user, $parishId);

                return [$meta['priority'], (int) $meta['role_rank'], -1 * (int) $user->getKey()];
            })
            ->values();
    }

    public static function resolveStaffMeta(User $user, int $parishId): array
    {
        $assignment = $user->managedParishes
            ->firstWhere('id', $parishId);

        $note = trim((string) $assignment?->pivot?->note);
        $normalized = mb_strtolower($note);
        $roleLabel = 'Administrator';
        $roleKey = 'admin';
        $priority = 100;

        if (str_contains($normalized, 'proboszcz')) {
            $roleLabel = 'Proboszcz';
            $roleKey = 'pastor';
            $priority = 300;
        } elseif (str_contains($normalized, 'moderator')) {
            $roleLabel = 'Moderator';
            $roleKey = 'moderator';
            $priority = 220;
        } elseif (str_contains($normalized, 'pomocniczy')) {
            $roleLabel = 'Administrator pomocniczy';
            $roleKey = 'assistant_admin';
            $priority = 180;
        } elseif ($user->role >= 2) {
            $roleLabel = 'Superadmin';
            $roleKey = 'superadmin';
            $priority = 160;
        } elseif ($note !== '') {
            $roleLabel = $note;
            $roleKey = 'custom';
            $priority = 140;
        }

        return [
            'role_label' => $roleLabel,
            'role_key' => $roleKey,
            'priority' => $priority,
            'assignment_note' => $note !== '' ? $note : null,
            'role_rank' => (int) $user->role,
        ];
    }

    public static function resolveRecipientForParish(int $parishId, ?int $recipientUserId = null): User
    {
        $staff = self::staffCollectionForParish($parishId);

        if ($recipientUserId === null) {
            $recipient = $staff->first();

            if ($recipient instanceof User) {
                return $recipient;
            }

            throw new ApiException(ErrorCode::NOT_FOUND, 'Brak dostępnej obsługi kancelarii dla parafii.', 404);
        }

        $recipient = $staff->firstWhere('id', $recipientUserId);

        if (! $recipient instanceof User) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Wybrany odbiorca nie obsługuje kancelarii tej parafii.', 403);
        }

        return $recipient;
    }

    private function authorizedParishForRequester(Request $request, int $parishId): Parish
    {
        $parish = $this->activeParishOrFail($parishId);
        $user = $request->user();

        if ((int) $user->home_parish_id !== (int) $parish->getKey()) {
            throw new ApiException(ErrorCode::FORBIDDEN, 'Dostęp do katalogu kancelarii jest możliwy wyłącznie dla własnej parafii.', 403);
        }

        return $parish;
    }

    private function staffPayload(User $user, int $parishId, int $defaultRecipientId): array
    {
        $meta = self::resolveStaffMeta($user, $parishId);

        return [
            'id' => (string) $user->getKey(),
            'display_name' => trim((string) ($user->full_name ?: $user->name ?: $user->email)),
            'avatar_url' => $user->avatar_media_url,
            'role_key' => $meta['role_key'],
            'role_label' => $meta['role_label'],
            'priority' => $meta['priority'],
            'assignment_note' => $meta['assignment_note'],
            'is_default_recipient' => (int) $user->getKey() === $defaultRecipientId,
        ];
    }

    private function directoryPayload(User $user): array
    {
        return [
            'id' => (string) $user->getKey(),
            'display_name' => trim((string) ($user->full_name ?: $user->name ?: $user->email)),
            'avatar_url' => $user->avatar_media_url,
            'default_parish_id' => $user->home_parish_id ? (string) $user->home_parish_id : null,
            'is_email_verified' => $user->hasVerifiedEmail(),
            'is_parish_approved' => (bool) $user->is_user_verified,
            'created_at' => optional($user->created_at)?->toISOString(),
        ];
    }
}
