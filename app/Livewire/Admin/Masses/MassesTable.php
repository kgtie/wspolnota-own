<?php

namespace App\Livewire\Admin\Masses;

use App\Models\Mass;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class MassesTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterDate = ''; // 'upcoming' (domyślnie), 'past', 'all'
    public string $printDateFrom;
    public string $printDateTo;

    protected string $paginationTheme = 'bootstrap'; // Zabezpieczenie paginacji, aby style się nie rozjezdzalu

    public function mount()
    {
        $this->filterDate = 'upcoming';
        $this->printDateFrom = now()->startOfWeek()->format('Y-m-d');
        $this->printDateTo = now()->endOfWeek()->format('Y-m-d');
    }

    #[On('mass-saved')]
    public function refresh() {}

    public function deleteMass($id)
    {
        $mass = Mass::where('id', $id)->where('parish_id', Auth::user()->current_parish_id)->first();
        if ($mass) {
            $mass->delete();
            session()->flash('success', 'Msza święta została usunięta.');
        }
    }

    public function openEdit($id)
    {
        $this->dispatch('edit-mass', massId: $id);
    }
    
    public function openAttendance($id)
    {
        $this->dispatch('show-attendance', massId: $id);
    }

    public function render()
    {
        $query = Mass::query()
            ->where('parish_id', Auth::user()->current_parish_id);

        // Wyszukiwanie po intencji
        if ($this->search) {
            $query->where('intention', 'like', '%' . $this->search . '%');
        }

        // Filtrowanie czasu
        if ($this->filterDate === 'upcoming') {
            $query->where('start_time', '>=', now());
            $query->orderBy('start_time', 'asc'); // Najbliższe najpierw
        } elseif ($this->filterDate === 'past') {
            $query->where('start_time', '<', now());
            $query->orderBy('start_time', 'desc'); // Najnowsze z przeszłych najpierw
        } else {
            $query->orderBy('start_time', 'desc');
        }

        return view('livewire.admin.masses.masses-table', [
            'masses' => $query->paginate(20)
        ]);
    }
}