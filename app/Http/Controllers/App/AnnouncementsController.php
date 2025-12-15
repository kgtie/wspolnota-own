<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Parish;

class AnnouncementsController extends Controller
{
    public function index(Parish $parish)
    {
        $pageInfo = [
            'meta.title' => 'Ogłoszenia parafialne',
            'meta.description' => 'Ogłoszenia parafialne w Usłudze Wspólnota dla parafii ' . $parish->name,
            'page.title' => 'Ogłoszenia parafialne',
        ];
        return view('app.announcements.index', [
            'currentParish'=> $parish,
            'pageInfo' => $pageInfo
        ]);
    }
}
