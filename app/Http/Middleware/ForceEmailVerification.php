<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ForceEmailVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 1. Sprawdzamy, czy użytkownik jest w ogóle zalogowany.
        // Jeśli jest GOŚCIEM -> przepuszczamy go (return $next), niech sobie ogląda publiczne /app
        if (!$user) {
            return $next($request);
        }

        // 2. Jeśli jest zalogowany, ale NIE MA zweryfikowanego maila
        if (!$user->hasVerifiedEmail()) {

            // 3. Musimy pozwolić mu wejść na trasy służące do weryfikacji oraz wylogowania,
            // inaczej wpadnie w pętlę nieskończonych przekierowań.
            if ($request->routeIs('verification.*') || $request->routeIs('logout')) {
                return $next($request);
            }

            // 4. W każdym innym przypadku -> KIERUNEK: NOTICE
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}