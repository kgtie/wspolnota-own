<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    // --- TWOJA LOGIKA PRZEKIEROWAŃ ---
    $user = $request->user();

    // 1. SuperAdmin
    if ($user->isSuperAdmin()) {
        return redirect()->intended(route('superadmin.dashboard', absolute: false));
    }

    // 2. Admin
    if ($user->isAdmin()) {
        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    // 3. Zwykły User -> /app/{slug}
    // Pobieramy slug aktualnej parafii (zapisanej przy rejestracji)
    // Używamy currentParish, jeśli jest zdefiniowana
    if ($user->currentParish) {
        $slug = $user->currentParish->slug;
        // UWAGA: Musisz zdefiniować trasę dla userów, np. Route::get('/app/{slug}', ...)
        return redirect()->intended('/app/' . $slug);
    }

    // Fallback, gdyby user nie miał parafii (błąd danych)
    return redirect()->intended(route('dashboard', absolute: false));
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
