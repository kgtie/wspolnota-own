<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ContextSwitcher extends Component
{
    public $currentParishId;

    public function mount()
    {
        // Inicjalizujemy stan selecta aktualną wartością z bazy
        $this->currentParishId = Auth::user()->current_parish_id;
    }

    // Livewire 4 pozwala na proste listenery zmian
    public function updatedCurrentParishId($value)
    {
        $user = Auth::user();

        // SECURITY: Sprawdzamy czy user MA PRAWO przełączyć się na tę parafię
        $hasAccess = $user->parishes()->where('id', $value)->exists();

        if ($hasAccess) {
            $user->current_parish_id = $value;
            $user->save();

            // Przeładowanie strony, by załadować dane nowej parafii
            $this->redirect(request()->header('Referer'), navigate: false); 
        } else {
            // Opcjonalnie: rzuć wyjątek lub alert
            $this->currentParishId = $user->fresh()->current_parish_id;
        }
    }

    public function render()
    {
        // Pobieramy tylko te parafie, do których admin ma dostęp
        $myParishes = Auth::user()->parishes;

        return view('livewire.admin.context-switcher', [
            'parishes' => $myParishes
        ]);
    }
}