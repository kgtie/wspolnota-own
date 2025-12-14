<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Parish;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Pobieramy listę parafii do selecta
        $parishes = Parish::where('is_active', true)->orderBy('name')->get(['id', 'name', 'city']);
        
        return view('auth.register', compact('parishes'));
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'parish_id' => ['required', 'exists:parishes,id'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 0, // Zwykły user
            'current_parish_id' => $request->parish_id, // Ustawiamy kontekst od razu
        ]);

        // Przypisujemy usera do parafii w tabeli pivot
        // Dzięki temu user "należy" do tej parafii
        $user->parishes()->attach($request->parish_id);

        event(new Registered($user));

        Auth::login($user);
        return redirect(route('verification.notice', absolute: false));
    }
}