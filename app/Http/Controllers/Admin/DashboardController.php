<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Models\Parish;

class DashboardController extends Controller
{
    public function index()
    {
        $parish = Parish::where('id', Auth::user()->current_parish_id)->first();

        $stats = [
            'users' => $parish->users()->count(),
        ];
        $pageInfo = [
            'meta.title' => 'Zarządzanie parafią ' . $parish->name . ' - Usługa Wspólnota',
            'meta.description' => 'Zarządzanie parafią' . $parish->name,
            'page.title' => 'Dashboard ',
        ];
        return view('admin.dashboard.index', 
        [
            'pageInfo' => $pageInfo,
            'stats' => $stats,
            'parish' => $parish,
        ]);
    }
}