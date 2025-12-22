<?php

namespace App\Livewire\Admin\Masses;

use App\Models\Mass;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class MassAttendanceModal extends Component
{
    public ?Mass $mass = null;
    public $attendees = [];

    #[On('show-attendance')]
    public function load(int $massId)
    {
        $this->mass = Mass::with('attendees')
            ->where('id', $massId)
            ->where('parish_id', Auth::user()->current_parish_id)
            ->firstOrFail();

        $this->attendees = $this->mass->attendees;
        
        $this->dispatch('open-attendance-modal');
    }

    public function render()
    {
        return view('livewire.admin.masses.mass-attendance-modal');
    }
}