<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Parish;

class AppController extends Controller
{
    public function app_route()
    {
        if(Auth::check()){
            $parish = Parish::where('id', Auth::user()->current_parish_id)->first();
            return redirect("/app/" . $parish->slug);
        }
        return redirect(route("landing.home"))->with('status', 'Przekierowano na stronę główną.');
    }
}
