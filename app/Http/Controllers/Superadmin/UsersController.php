<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\SaveUserRequest;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{
    /**
     * Lista użytkowników z wyszukiwaniem i sortowaniem.
     */
    public function index(Request $request)
    {
        $query = User::with('homeParish');

        // Wyszukiwanie "powerfull"
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('homeParish', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('city', 'like', "%{$search}%");
                  });
            });
        }

        // Filtrowanie po roli
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Sortowanie
        $sortCol = $request->input('sort', 'created_at');
        $sortDir = $request->input('direction', 'desc');
        $allowedSorts = ['id', 'name', 'full_name', 'email', 'created_at', 'role'];

        if (in_array($sortCol, $allowedSorts)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate(20)->appends($request->query());

        return view('superadmin.users.index', compact('users'));
    }

    /**
     * Formularz dodawania.
     */
    public function create()
    {
        $parishes = Parish::orderBy('name')->get();
        return view('superadmin.users.create', compact('parishes'));
    }

    /**
     * Zapis nowego użytkownika.
     */
    public function store(SaveUserRequest $request)
    {
        $data = $request->validated();
        
        $user = new User();
        $user->name = $data['name'];
        $user->full_name = $data['full_name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = $data['role'];
        $user->home_parish_id = $data['home_parish_id'];

        // Obsługa statusów (jeśli zaznaczono checkbox, ustawiamy datę/flagę)
        if (!empty($data['is_email_verified'])) {
            $user->email_verified_at = now();
        }
        
        if (!empty($data['is_parish_verified'])) {
            $user->is_user_verified = true;
            $user->user_verified_at = now();
            // Generujemy kod weryfikacyjny, jeśli nie ma, na wypadek gdyby status został cofnięty
            $user->verification_code = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        } else {
            $user->verification_code = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        }

        // Avatar
        if ($request->hasFile('avatar_file')) {
            $user->avatar = $request->file('avatar_file')->store('/', 'profiles');
        }

        $user->save();

        return redirect()->route('superadmin.users.index')
            ->with('success', 'Użytkownik został utworzony.');
    }

    /**
     * Formularz edycji.
     */
    public function edit(User $user)
    {
        $parishes = Parish::orderBy('name')->get();
        return view('superadmin.users.edit', compact('user', 'parishes'));
    }

    /**
     * Aktualizacja użytkownika.
     */
    public function update(SaveUserRequest $request, User $user)
    {
        $data = $request->validated();

        $user->name = $data['name'];
        $user->full_name = $data['full_name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        $user->home_parish_id = $data['home_parish_id'];

        // Zmiana hasła tylko jeśli podano
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        // Władcza weryfikacja Email
        if (!empty($data['is_email_verified']) && is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
        } elseif (empty($data['is_email_verified'])) {
            $user->email_verified_at = null;
        }

        // Władcza weryfikacja Parafii
        if (!empty($data['is_parish_verified'])) {
            if (!$user->is_user_verified) {
                $user->is_user_verified = true;
                $user->user_verified_at = now();
            }
        } else {
            $user->is_user_verified = false;
            $user->user_verified_at = null;
        }

        // Obsługa Avatara
        if (!empty($data['avatar_remove']) && $user->avatar) {
            Storage::disk('profiles')->delete($user->avatar);
            $user->avatar = null;
        }
        if ($request->hasFile('avatar_file')) {
            if ($user->avatar) {
                Storage::disk('profiles')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar_file')->store('/', 'profiles');
        }

        $user->save();

        return redirect()->route('superadmin.users.index')
            ->with('success', 'Dane użytkownika zostały zaktualizowane.');
    }

    /**
     * Soft Delete - przeniesienie do kosza.
     */
    public function destroy(User $user)
    {
        // Blokada przed usunięciem samego siebie
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Nie możesz usunąć własnego konta.');
        }

        $user->delete(); // Soft delete (dzięki Traitowi w modelu)

        return back()->with('success', 'Użytkownik został przeniesiony do kosza.');
    }

    /**
     * Widok kosza.
     */
    public function trash()
    {
        $users = User::onlyTrashed()->with('homeParish')->paginate(20);
        return view('superadmin.users.trash', compact('users'));
    }

    /**
     * Przywracanie z kosza.
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('success', 'Użytkownik został przywrócony.');
    }

    /**
     * Hard Delete - trwałe usunięcie.
     */
    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        
        if ($user->avatar) {
            Storage::disk('profiles')->delete($user->avatar);
        }
        
        $user->forceDelete();

        return back()->with('success', 'Użytkownik został trwale usunięty z bazy danych.');
    }
}