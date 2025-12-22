<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;

class UserApprovalModal extends Component
{
    use WithFileUploads;

    public ?User $user = null;
    public bool $showModal = false;

    // --- POLA FORMULARZA EDYCJI ---
    public $name;
    public $full_name;
    public $email;
    public $newAvatar; // Plik tymczasowy przy uploadzie
    public $is_user_verified = false;

    // --- POLA LOGICZNE ---
    public string $verificationCode = '';

    #[On('edit-user')]
    public function loadUser(int $userId)
    {
        $this->user = User::findOrFail($userId);
        
        // Wypełniamy formularz danymi
        $this->name = $this->user->name;
        $this->full_name = $this->user->full_name;
        $this->email = $this->user->email;
        $this->is_user_verified = (bool) $this->user->is_user_verified;
        $this->verificationCode = $this->user->verification_code ?? '---';
        
        $this->reset(['newAvatar']); // Czyścimy pole uploadu
        
        $this->showModal = true;
        $this->dispatch('open-bootstrap-modal');
    }

    public function saveChanges()
    {
        if (!$this->user) return;

        // Walidacja
        $this->validate([
            'name' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->user->id,
            'newAvatar' => 'nullable|image|max:1024', // max 1MB
        ]);

        // Zapis podstawowych danych
        $this->user->name = $this->name;
        $this->user->full_name = $this->full_name;
        $this->user->email = $this->email;
        
        // Jeśli zmieniono status weryfikacji manualnie checkboxem (opcjonalne, bo mamy przyciski)
        // Ale tutaj skupmy się na danych profilowych.

        // Obsługa Avatara
        if ($this->newAvatar) {
            // Usuń stary jeśli istnieje
            if ($this->user->avatar) {
                Storage::disk('public')->delete($this->user->avatar);
            }
            // Zapisz nowy
            $path = $this->newAvatar->store('/', 'profiles'); 
            $this->user->avatar = $path;    
        }

        $this->user->save();

        session()->flash('success', 'Zaktualizowano dane parafianina.');
        $this->dispatch('refresh-users-table');
        // Nie zamykamy modala automatycznie, żeby admin widział sukces, 
        // chyba że chcesz: $this->closeModal();
    }

    public function deleteAvatar()
    {
        if ($this->user && $this->user->avatar) {
            Storage::disk('profiles')->delete($this->user->avatar);
            $this->user->avatar = null;
            $this->user->save();
            session()->flash('success', 'Usunięto zdjęcie profilowe.');
            $this->dispatch('refresh-users-table');
        }
    }

    public function sendPasswordReset()
    {
        if (!$this->user) return;

        // Używamy wbudowanego brokera haseł Laravela
        $status = Password::broker()->sendResetLink(
            ['email' => $this->user->email]
        );

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('success', 'Wysłano e-mail z linkiem do resetowania hasła.');
        } else {
            session()->flash('error', 'Nie udało się wysłać linku. Sprawdź poprawność adresu e-mail.');
        }
    }

    // --- METODY DO ZATWIERDZANIA (POZOSTAWIAMY Z POPRZEDNIEJ WERSJI) ---

    public function generateNewCode()
    {
        if (!$this->user) return;
        $newCode = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        $this->user->verification_code = $newCode;
        $this->user->save();
        $this->verificationCode = $newCode;
        session()->flash('success', 'Wygenerowano nowy kod weryfikacyjny.');
    }

    public function approveUser()
    {
        if (!$this->user) return;
        $this->user->is_user_verified = true;
        $this->user->user_verified_at = now();
        $this->user->save();
        $this->is_user_verified = true; // Aktualizacja UI
        session()->flash('success', 'Zatwierdzono parafianina.');
        $this->dispatch('refresh-users-table');
    }

    public function revokeApproval()
    {
        if (!$this->user) return;
        $this->user->is_user_verified = false;
        $this->user->user_verified_at = null;
        $this->user->save();
        $this->is_user_verified = false; // Aktualizacja UI
        session()->flash('success', 'Cofnięto zatwierdzenie.');
        $this->dispatch('refresh-users-table');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->user = null;
        $this->reset(['newAvatar']);
        $this->dispatch('close-bootstrap-modal');
    }

    public function render()
    {
        return view('livewire.admin.users.user-approval-modal');
    }
}