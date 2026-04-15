<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Twarde zabezpieczenie panelu superadministratora.
 *
 * Filament sprawdza uprawnienia panelu na etapie dostepu, ale middleware jest
 * ostatnia linia obrony dla kazdego requestu do /superadmin. Pilnujemy tutaj
 * jednoczesnie roli oraz aktywnego statusu konta.
 */
class EnsureUserIsSuperAdmin
{
    /**
     * Blokuje dostep dla niezalogowanych, nie-superadminow i kont nieaktywnych.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isSuperAdmin() || $user->status !== 'active') {
            abort(403, 'Brak uprawnień superadministratora.');
        }

        return $next($request);
    }
}
