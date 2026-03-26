<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Pilnuje, aby pomocnicze endpointy pod /admin byly dostepne wyłącznie
     * dla aktywnych kont administracyjnych, spójnie z regułami Filament panelu.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || $user->status !== 'active') {
            abort(403, 'Brak uprawnień do panelu administratora.');
        }

        return $next($request);
    }
}
