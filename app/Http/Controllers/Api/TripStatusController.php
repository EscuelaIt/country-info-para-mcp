<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTripStatusRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\TripStatus;
use Illuminate\Support\Facades\Gate;

class TripStatusController extends Controller
{
    /**
     * Change the status of one of the authenticated user's trips.
     */
    public function __invoke(UpdateTripStatusRequest $request, Trip $trip): TripResource
    {
        $newStatus = $request->enum('status', TripStatus::class);

        Gate::authorize('changeStatus', [$trip, $newStatus]);

        $trip->update(['status' => $newStatus]);

        return new TripResource($trip);
    }
}
