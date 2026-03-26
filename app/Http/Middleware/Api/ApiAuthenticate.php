<?php

namespace App\Http\Middleware\Api;

use App\Services\Auth\MobileTokenService;
use App\Support\Api\ErrorCode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Autoryzuje każde żądanie API v1 przez hash access tokenu zapisany w bazie.
 */
class ApiAuthenticate
{
    public function __construct(private readonly MobileTokenService $tokenService) {}

    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json([
                'error' => [
                    'code' => ErrorCode::AUTH_UNAUTHENTICATED,
                    'message' => 'Brak tokenu dostępowego.',
                    'details' => (object) [],
                ],
            ], 401);
        }

        $resolution = $this->tokenService->resolveAccessToken($bearer);
        $token = $resolution['token'];

        if ($resolution['status'] !== 'active' || ! $token || ! $token->user) {
            if ($resolution['status'] === 'inactive_user' && $token?->user) {
                $this->tokenService->revokeAllForUser($token->user);
            }

            return response()->json([
                'error' => [
                    'code' => match ($resolution['status']) {
                        'expired' => ErrorCode::AUTH_TOKEN_EXPIRED,
                        'inactive_user' => ErrorCode::AUTH_ACCOUNT_LOCKED,
                        default => ErrorCode::AUTH_TOKEN_INVALID,
                    },
                    'message' => match ($resolution['status']) {
                        'expired' => 'Token dostępu wygasł.',
                        'inactive_user' => 'Konto jest zablokowane lub nieaktywne.',
                        default => 'Nieprawidłowy token dostępu.',
                    },
                    'details' => (object) [],
                ],
            ], $resolution['status'] === 'inactive_user' ? 423 : 401);
        }

        Auth::setUser($token->user);
        $request->attributes->set('api_access_token_raw', $bearer);
        $request->attributes->set('api_access_token_id', $token->getKey());

        return $next($request);
    }
}
