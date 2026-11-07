<?php

use App\Mcp\Servers\CountryInfoServer;
use App\Mcp\Tools\ChangeTripStatusTool;
use App\Models\Trip;
use App\Models\User;
use App\TripStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('it cancels a planned trip of the authenticated user', function () {
    $trip = Trip::factory()->create();
    Sanctum::actingAs($trip->user);

    CountryInfoServer::tool(ChangeTripStatusTool::class, ['trip_id' => $trip->id, 'status' => 'cancelled'])
        ->assertOk();

    expect($trip->fresh()->status)->toBe(TripStatus::Cancelled);
});

test('it reports trips of other users as not found and leaves them unchanged', function () {
    $trip = Trip::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    CountryInfoServer::tool(ChangeTripStatusTool::class, ['trip_id' => $trip->id, 'status' => 'cancelled'])
        ->assertHasErrors(["No se ha encontrado el viaje {$trip->id} entre tus viajes."]);

    expect($trip->fresh()->status)->toBe(TripStatus::Planned);
});
