<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Parish;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Parish $parish)
    {
        return view('app.home.index', ['currentParish'=> $parish]);
    }
}
