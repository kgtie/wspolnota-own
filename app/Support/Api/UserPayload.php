<?php

namespace App\Support\Api;

use App\Models\User;

class UserPayload
{
    public static function make(User $user, bool $withApprovalCode = true): array
    {
        [$firstName, $lastName] = self::extractNameParts($user);

        return [
            'id' => (string) $user->getKey(),
            'login' => (string) $user->name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => (string) $user->email,
            'avatar_url' => $user->getFirstMediaUrl('avatar', 'thumb') ?: null,
            'default_parish_id' => $user->home_parish_id ? (string) $user->home_parish_id : null,
            'is_email_verified' => $user->hasVerifiedEmail(),
            'is_parish_approved' => (bool) $user->is_user_verified,
            'can_access_office' => $user->canAccessOffice(),
            'parish_approval_code' => $withApprovalCode ? $user->verification_code : null,
            'created_at' => optional($user->created_at)?->toISOString(),
            'updated_at' => optional($user->updated_at)?->toISOString(),
        ];
    }

    private static function extractNameParts(User $user): array
    {
        $full = trim((string) $user->full_name);

        if ($full === '') {
            return [(string) $user->name, ''];
        }

        $parts = preg_split('/\s+/', $full) ?: [];
        $firstName = array_shift($parts) ?: '';
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }
}
