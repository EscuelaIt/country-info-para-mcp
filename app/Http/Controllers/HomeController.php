<?php

namespace App\Http\Controllers;

use App\Models\Continent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $continents = Continent::with(['countries' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        $countries = $continents->flatMap->countries;

        $stats = [
            'countries' => $countries->count(),
            'continents' => $continents->count(),
            'population' => $countries->sum('population'),
        ];

        return view('home', compact('continents', 'stats'));
    }
}
