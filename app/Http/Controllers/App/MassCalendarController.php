<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Parish;

class MassCalendarController extends Controller
{
    public function index(Parish $parish)
    {
        $pageInfo = [
            'meta.title' => 'Kalendarz intencji.',
            'meta.description' => 'Kalendarz intencji mszalnych w Usłudze Wspólnota dla parafii ' . $parish->name,
            'page.title' => 'Kalendarz intencji mszalnych',
        ];
        return view('app.mass_calendar.index', [
            'currentParish'=> $parish,
            'pageInfo' => $pageInfo
        ]);
    }
}
