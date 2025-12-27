<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Parish;

class SettingsController extends Controller
{
    public function account()
    {
        $parish = Parish::where('id', Auth::user()->current_parish_id)->first();
        $pageInfo = [
            'meta.title' => 'Ustawienia konta',
            'meta.description' => 'Ustawienia konta',
            'page.title' => 'Ustawienia konta',
        ];
        return view('app.settings.account', [
            'currentParish'=> $parish,
            'pageInfo' => $pageInfo
        ]);
    }

    public function profile()
    {
        $parish = Parish::where('id', Auth::user()->current_parish_id)->first();
        $pageInfo = [
            'meta.title' => 'Ustawienia profilu',
            'meta.description' => 'Ustawienia profilu',
            'page.title' => 'Ustawienia profilu',
        ];
        return view('app.settings.profile', [
            'currentParish'=> $parish,
            'pageInfo' => $pageInfo
        ]);
    }
}
