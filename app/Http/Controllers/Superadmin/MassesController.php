<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\SaveMassRequest;
use App\Models\Mass;
use App\Models\Parish;
use Illuminate\Http\Request;

class MassesController extends Controller
{
    /**
     * Lista z zaawansowanym filtrowaniem i sortowaniem.
     */
    public function index(Request $request)
    {
        $query = Mass::query()
            ->select('masses.*') // Ważne przy joinach
            ->with(['parish', 'attendees']); // Eager loading

        // 1. Wyszukiwanie (Intencja, Celebrans, Miasto Parafii, Nazwa Parafii)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('intention', 'like', "%{$search}%")
                  ->orWhere('celebrant', 'like', "%{$search}%")
                  ->orWhereHas('parish', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('city', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filtr Parafii
        if ($parishId = $request->input('parish_id')) {
            $query->where('parish_id', $parishId);
        }

        // 3. Filtr Daty (Od - Do)
        if ($dateFrom = $request->input('date_from')) {
            $query->where('start_time', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo = $request->input('date_to')) {
            $query->where('start_time', '<=', $dateTo . ' 23:59:59');
        }

        // 4. Sortowanie
        $sortCol = $request->input('sort', 'start_time');
        $sortDir = $request->input('direction', 'desc');

        if ($sortCol === 'parish_name') {
            // Sortowanie po relacji wymaga Joina
            $query->join('parishes', 'masses.parish_id', '=', 'parishes.id')
                  ->orderBy('parishes.name', $sortDir);
        } else {
            // Sortowanie standardowe
            $query->orderBy($sortCol, $sortDir);
        }

        $masses = $query->paginate(20)->appends($request->query());
        $parishes = Parish::orderBy('name')->get(); // Do dropdowna filtrów

        return view('superadmin.masses.index', compact('masses', 'parishes'));
    }

    public function create()
    {
        $parishes = Parish::orderBy('name')->get();
        return view('superadmin.masses.create', compact('parishes'));
    }

    public function store(SaveMassRequest $request)
    {
        $data = $request->validated();
        
        Mass::create([
            'parish_id' => $data['parish_id'],
            'start_time' => $request->getStartTimestamp(),
            'intention' => $data['intention'],
            'location' => $data['location'],
            'type' => $data['type'],
            'rite' => $data['rite'],
            'celebrant' => $data['celebrant'],
            'stipend' => $data['stipend'],
        ]);

        return redirect()->route('superadmin.masses.index')
            ->with('success', 'Msza święta została dodana do globalnego kalendarza.');
    }

    public function edit(Mass $mass)
    {
        $parishes = Parish::orderBy('name')->get();
        return view('superadmin.masses.edit', compact('mass', 'parishes'));
    }

    public function update(SaveMassRequest $request, Mass $mass)
    {
        $data = $request->validated();

        $mass->update([
            'parish_id' => $data['parish_id'],
            'start_time' => $request->getStartTimestamp(),
            'intention' => $data['intention'],
            'location' => $data['location'],
            'type' => $data['type'],
            'rite' => $data['rite'],
            'celebrant' => $data['celebrant'],
            'stipend' => $data['stipend'],
        ]);

        return redirect()->route('superadmin.masses.index')
            ->with('success', 'Dane mszy świętej zostały zaktualizowane.');
    }

    public function destroy(Mass $mass)
    {
        $mass->delete();

        return back()->with('success', 'Msza święta została usunięta.');
    }
}