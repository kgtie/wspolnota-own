<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class UsersTable extends Component
{
    use WithPagination;

    // --- WYSZUKIWANIE I FILTRY ---
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterEmailStatus = ''; // '' = wszyscy, 'verified', 'unverified'

    #[Url(history: true)]
    public string $filterParishStatus = ''; // '' = wszyscy, 'approved', 'pending'

    // --- SORTOWANIE ---
    public string $sortCol = 'created_at';
    public string $sortDir = 'desc';

    protected string $paginationTheme = 'bootstrap'; // Zabezpieczenie paginacji, aby się style nie rozjezdzały

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterEmailStatus() { $this->resetPage(); }
    public function updatedFilterParishStatus() { $this->resetPage(); }

    public function sortBy(string $column)
    {
        if ($this->sortCol === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortCol = $column;
            $this->sortDir = 'asc';
        }
    }

    public function openApprovalModal(int $userId)
    {
        $this->dispatch('edit-user', userId: $userId);
    }

    // Nasłuchujemy na odświeżenie tabeli po edycji w modalu
    #[\Livewire\Attributes\On('refresh-users-table')] 
    public function refresh() {}

    public function render()
    {
        $currentParishId = Auth::user()->current_parish_id;

        $query = User::query()
            ->where('home_parish_id', $currentParishId);

        // 1. Wyszukiwanie tekstowe (rozszerzone o login, imię, email)
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('full_name', 'like', '%' . $this->search . '%');
            });
        }

        // 2. Filtrowanie po statusach
        if ($this->filterEmailStatus === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($this->filterEmailStatus === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        if ($this->filterParishStatus === 'approved') {
            $query->where('is_user_verified', true);
        } elseif ($this->filterParishStatus === 'pending') {
            $query->where('is_user_verified', false);
        }

        // 3. Sortowanie
        // Obsługa sortowania po kolumnach, które istnieją w bazie.
        // Dla bezpieczeństwa można dodać whitelistę kolumn.
        $allowedSorts = ['name', 'full_name', 'email', 'created_at', 'email_verified_at', 'is_user_verified'];
        
        if (in_array($this->sortCol, $allowedSorts)) {
            $query->orderBy($this->sortCol, $this->sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return view('livewire.admin.users.users-table', [
            'users' => $query->paginate(50)
        ]);
    }
}