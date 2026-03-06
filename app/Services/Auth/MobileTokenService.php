<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\ApiAccessToken;
use App\Models\ApiRefreshToken;
use App\Models\User;
use App\Support\Api\ErrorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileTokenService
{
    public function issuePair(User $user, Request $request, ?string $familyId = null, ?ApiRefreshToken $rotateFrom = null): array
    {
        return DB::transaction(function () use ($user, $request, $familyId, $rotateFrom) {
            $rawRefresh = $this->generateToken();
            $rawAccess = $this->generateToken();

            $refresh = ApiRefreshToken::query()->create([
                'user_id' => $user->getKey(),
                'family_id' => $familyId ?? (string) Str::uuid(),
                'token_hash' => hash('sha256', $rawRefresh),
                'device_id' => (string) data_get($request->input('device'), 'device_id', ''),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'expires_at' => now()->addDays(config('api_auth.refresh_ttl_days')),
            ]);

            if ($rotateFrom) {
                $rotateFrom->forceFill([
                    'used_at' => now(),
                    'replaced_by_id' => $refresh->getKey(),
                ])->save();

                ApiAccessToken::query()
                    ->where('refresh_token_id', $rotateFrom->getKey())
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);
            }

            ApiAccessToken::query()->create([
                'user_id' => $user->getKey(),
                'refresh_token_id' => $refresh->getKey(),
                'token_hash' => hash('sha256', $rawAccess),
                'device_id' => (string) data_get($request->input('device'), 'device_id', ''),
                'expires_at' => now()->addSeconds(config('api_auth.access_ttl_seconds')),
            ]);

            return [
                'token_type' => 'Bearer',
                'access_token' => $rawAccess,
                'access_expires_in' => config('api_auth.access_ttl_seconds'),
                'refresh_token' => $rawRefresh,
                'refresh_expires_in' => config('api_auth.refresh_ttl_days') * 24 * 3600,
                'refresh_family_id' => $refresh->family_id,
            ];
        });
    }

    public function rotateByRefreshToken(string $refreshToken, Request $request): array
    {
        $hash = hash('sha256', $refreshToken);

        $token = ApiRefreshToken::query()
            ->with('user')
            ->where('token_hash', $hash)
            ->first();

        if (! $token) {
            throw new ApiException(ErrorCode::AUTH_REFRESH_INVALID, 'Nieprawidłowy refresh token.', 401);
        }

        if ($token->revoked_at) {
            throw new ApiException(ErrorCode::AUTH_REFRESH_REVOKED, 'Refresh token został unieważniony.', 401);
        }

        if ($token->expires_at->isPast()) {
            $token->forceFill(['revoked_at' => now()])->save();
            throw new ApiException(ErrorCode::AUTH_REFRESH_EXPIRED, 'Refresh token wygasł.', 401);
        }

        if ($token->used_at) {
            if ($token->user) {
                $this->revokeAllForUser($token->user);
            } else {
                $this->revokeRefreshFamily((string) $token->family_id);
            }

            throw new ApiException(ErrorCode::AUTH_REFRESH_REUSED, 'Wykryto ponowne użycie refresh tokenu.', 401);
        }

        $user = $token->user;

        if (! $user) {
            throw new ApiException(ErrorCode::AUTH_REFRESH_INVALID, 'Nieprawidłowy refresh token.', 401);
        }

        return [
            'user' => $user,
            'tokens' => $this->issuePair($user, $request, $token->family_id, $token),
        ];
    }

    public function findAccessToken(string $rawToken): ?ApiAccessToken
    {
        $resolution = $this->resolveAccessToken($rawToken);

        return $resolution['status'] === 'active' ? $resolution['token'] : null;
    }

    public function resolveAccessToken(string $rawToken): array
    {
        $token = ApiAccessToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $rawToken))
            ->first();

        if (! $token) {
            return ['status' => 'invalid', 'token' => null];
        }

        if ($token->revoked_at) {
            return ['status' => 'revoked', 'token' => $token];
        }

        if ($token->expires_at->isPast()) {
            return ['status' => 'expired', 'token' => $token];
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return ['status' => 'active', 'token' => $token];
    }

    public function revokeAccessTokenByRaw(string $rawToken): void
    {
        ApiAccessToken::query()
            ->where('token_hash', hash('sha256', $rawToken))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeRefreshTokenForUser(string $rawToken, User $user): void
    {
        ApiRefreshToken::query()
            ->where('user_id', $user->getKey())
            ->where('token_hash', hash('sha256', $rawToken))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllForUser(User $user): void
    {
        ApiAccessToken::query()
            ->where('user_id', $user->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        ApiRefreshToken::query()
            ->where('user_id', $user->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeRefreshFamily(string $familyId): void
    {
        ApiRefreshToken::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        ApiAccessToken::query()
            ->whereIn('refresh_token_id', ApiRefreshToken::query()->select('id')->where('family_id', $familyId))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeSessionByAccessTokenId(int $accessTokenId): void
    {
        $token = ApiAccessToken::query()->find($accessTokenId);

        if (! $token) {
            return;
        }

        $token->forceFill(['revoked_at' => now()])->save();

        if ($token->refresh_token_id) {
            ApiRefreshToken::query()
                ->whereKey($token->refresh_token_id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }
}
