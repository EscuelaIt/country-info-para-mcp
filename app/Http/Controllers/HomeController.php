<?php

namespace App\Http\Controllers;

use App\Models\Continent;

class HomeController extends Controller
{
    public function index()
    {
        $continents = Continent::with('countries')->get();

        return view('home', compact('continents'));
    }
}
