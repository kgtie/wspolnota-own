<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sprawdzamy, czy użytkownik jest zalogowany i ma rolę >= 1 (Admin lub SuperAdmin)
        // Metodę isAdmin() masz już w modelu User
        if (! $request->user() || ! $request->user()->isAdmin()) {
            abort(403, 'Brak uprawnień do panelu administratora.');
        }

        return $next($request);
    }
}