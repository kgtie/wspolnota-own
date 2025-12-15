<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Parish;

class OfficeController extends Controller
{
    public function index(Parish $parish)
    {
        $pageInfo = [
            'meta.title' => 'Kancelaria parafialna',
            'meta.description' => 'Kancelaria parafialna w Usłudze Wspólnota dla parafii ' . $parish->name,
            'page.title' => 'Kancelaria parafialna',
        ];
        return view('app.office.index', [
            'currentParish'=> $parish,
            'pageInfo' => $pageInfo
        ]);
    }
}
