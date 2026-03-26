<?php

namespace App\Http\Middleware\Api;

use App\Support\Api\ErrorCode;
use Closure;
use Illuminate\Http\Request;

/**
 * Wymusza zatwierdzenie parafialne dla funkcji dostępnych dopiero po aprobacie proboszcza.
 */
class EnsureApiParishApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->is_user_verified) {
            return $next($request);
        }

        return response()->json([
            'error' => [
                'code' => ErrorCode::PARISH_APPROVAL_REQUIRED,
                'message' => 'Dostęp do tej funkcji wymaga zatwierdzenia parafialnego.',
                'details' => (object) [],
            ],
        ], 403);
    }
}
