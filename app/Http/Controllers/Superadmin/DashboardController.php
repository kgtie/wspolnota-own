<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Mass;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'masses' => Mass::where('start_time', '>', now())->count(),
        ];
        $pageInfo = [
            'meta.title' => 'Zarządzanie usługą',
            'meta.description' => 'Zarządzanie całą usługą Wspólnota',
            'page.title' => 'Dashboard ',
        ];
        return view('superadmin.dashboard.index', 
        [
            'pageInfo' => $pageInfo,
            'stats' => $stats,
        ]);    }
}