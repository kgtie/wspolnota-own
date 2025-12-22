<?php

namespace App\Livewire\Admin\Masses;

use App\Models\Mass;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class MassFormModal extends Component
{
    public ?Mass $mass = null;
    public bool $isEdit = false;
    
    // Pola formularza
    public $date;
    public $time;
    public $intention;
    public $location = 'Kościół główny';
    public $type = 'z dnia';
    public $rite = 'rzymski';
    public $celebrant;
    public $stipend;

    #[On('create-mass')]
    public function create()
    {
        $this->reset();
        $this->isEdit = false;
        // Domyślne wartości
        $this->date = now()->addDay()->format('Y-m-d'); // Jutro
        $this->time = '18:00';
        $this->location = 'Kościół główny';
        $this->type = 'z dnia';
        $this->rite = 'rzymski';
        
        $this->dispatch('open-mass-modal');
    }

    #[On('edit-mass')]
    public function edit(int $massId)
    {
        $this->reset();
        $this->isEdit = true;
        
        $this->mass = Mass::where('id', $massId)->where('parish_id', Auth::user()->current_parish_id)->firstOrFail();
        
        $this->date = $this->mass->start_time->format('Y-m-d');
        $this->time = $this->mass->start_time->format('H:i');
        $this->intention = $this->mass->intention;
        $this->location = $this->mass->location;
        $this->type = $this->mass->type;
        $this->rite = $this->mass->rite;
        $this->celebrant = $this->mass->celebrant;
        $this->stipend = $this->mass->stipend;

        $this->dispatch('open-mass-modal');
    }

    public function save()
    {
        $this->validate([
            'date' => 'required|date',
            'time' => 'required',
            'intention' => 'required|string|min:3',
            'location' => 'required|string',
            'stipend' => 'nullable|numeric|min:0',
        ]);

        $dateTime = $this->date . ' ' . $this->time . ':00';

        $data = [
            'parish_id' => Auth::user()->current_parish_id,
            'start_time' => $dateTime,
            'intention' => $this->intention,
            'location' => $this->location,
            'type' => $this->type,
            'rite' => $this->rite,
            'celebrant' => $this->celebrant,
            'stipend' => $this->stipend ?: null,
        ];

        if ($this->isEdit && $this->mass) {
            $this->mass->update($data);
            session()->flash('success', 'Msza święta została zaktualizowana.');
        } else {
            Mass::create($data);
            session()->flash('success', 'Dodano nową mszę do kalendarza.');
        }

        $this->dispatch('mass-saved'); // Odświeża tabelę
        $this->dispatch('close-mass-modal');
    }

    public function render()
    {
        return view('livewire.admin.masses.mass-form-modal');
    }
}