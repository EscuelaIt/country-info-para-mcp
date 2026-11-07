<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\View\View;

class TripController extends Controller
{
    /**
     * Show the trips of all users.
     */
    public function index(): View
    {
        $trips = Trip::with(['user', 'country'])
            ->orderBy('user_id')
            ->orderBy('starts_on')
            ->get();

        return view('trips.index', [
            'tripsByUser' => $trips->groupBy('user_id'),
        ]);
    }
}
