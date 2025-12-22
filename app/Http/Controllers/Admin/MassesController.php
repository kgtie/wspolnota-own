<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MassesController extends Controller
{
    public function index()
    {
        return view('admin.masses.index', [
            'pageInfo' => [
                'page.title' => 'Msze Święte i Intencje',
                'meta.description' => 'Kalendarz liturgiczny Twojej parafii.'
            ]
        ]);
    }

    /**
     * Generuje widok do druku (PDF)
     */
    public function print(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $parishId = Auth::user()->current_parish_id;
        $parish = Auth::user()->currentParish; // Zakładam relację w User modelu (lub pobranie modelu Parish)

        $masses = Mass::where('parish_id', $parishId)
            ->whereBetween('start_time', [
                $request->date_from . ' 00:00:00',
                $request->date_to . ' 23:59:59'
            ])
            ->orderBy('start_time')
            ->get()
            ->groupBy(function($date) {
                return $date->start_time->format('Y-m-d'); // Grupujemy dniami
            });

        return view('admin.masses.print', compact('masses', 'parish', 'request'));
    }
}