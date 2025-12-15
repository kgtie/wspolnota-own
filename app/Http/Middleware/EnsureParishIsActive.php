<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParishIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $parish = $request->route('parish');

        if ($parish && ! $parish->is_active) {
            return response()
                ->view('app.inactive', [
                    'parish' => $parish,
                ], 403);
        }

        return $next($request);
    }
}
