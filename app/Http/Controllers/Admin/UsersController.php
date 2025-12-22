<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class UsersController extends Controller
{
    /**
     * Wyświetla listę parafian dla aktualnie zarządzanej parafii.
     */
    public function index(): View
    {
        // Logika pobierania danych została przeniesiona do Livewire (UsersTable),
        // aby zapewnić dynamikę (wyszukiwanie, paginacja) bez przeładowania.
        
        return view('admin.users.index', [
            'pageInfo' => [
                'page.title' => 'Lista Parafian',
                'meta.description' => 'Zarządzanie użytkownikami zapisanymi do Twojej parafii.'
            ]
        ]);
    }
}