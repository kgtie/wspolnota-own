<?php
namespace App\Livewire\App\Settings;

use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class Account extends Component
{
    use WithFileUploads;

    public ?User $user = Auth::user();
    public $name;
    public $full_name;
    public $email;
    public $newAvatar; // Plik tymczasowy przy uploadzie

    public function mount()
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->full_name = $user->full_name;
        $this->email = $user->email;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'newAvatar' => 'nullable|image|max:1024', // max 1MB
        ];
    }

    public function updated($property)
    {
        $this->validateOnly($property);

        $user = Auth::user();

        
        if ($property === 'avatar' && $this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $user->avatar = $path;
        } else {
            $user->$property = $this->$property;
        }

        $user->save() or die('i chuj');
    }

    public function render()
    {
        return view('livewire.app.settings.account');
    }
}
