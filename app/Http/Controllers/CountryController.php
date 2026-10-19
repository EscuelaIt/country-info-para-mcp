<?php

namespace App\Http\Controllers;

use App\Models\Country;

class CountryController extends Controller
{
    public function show(Country $country)
    {
        $country->load(['continent', 'currencies', 'neighbors']);

        return view('countries.show', compact('country'));
    }
}
