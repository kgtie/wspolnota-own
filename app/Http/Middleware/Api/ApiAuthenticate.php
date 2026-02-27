<?php

namespace App\Http\Middleware\Api;

use App\Services\Auth\MobileTokenService;
use App\Support\Api\ErrorCode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $token = $this->tokenService->findAccessToken($bearer);

        if (! $token || ! $token->user) {
            return response()->json([
                'error' => [
                    'code' => ErrorCode::AUTH_TOKEN_INVALID,
                    'message' => 'Nieprawidłowy lub wygasły token dostępu.',
                    'details' => (object) [],
                ],
            ], 401);
        }

        Auth::setUser($token->user);
        $request->attributes->set('api_access_token_raw', $bearer);
        $request->attributes->set('api_access_token_id', $token->getKey());

        return $next($request);
    }
}
