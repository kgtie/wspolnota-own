<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
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