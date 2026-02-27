<?php

namespace App\Http\Middleware\Api;

use App\Support\Api\ErrorCode;
use Closure;
use Illuminate\Http\Request;

class EnsureApiEmailVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        return response()->json([
            'error' => [
                'code' => ErrorCode::AUTH_EMAIL_NOT_VERIFIED,
                'message' => 'Aby uzyskać pełny dostęp zweryfikuj adres e-mail.',
                'details' => (object) [],
            ],
        ], 403);
    }
}
