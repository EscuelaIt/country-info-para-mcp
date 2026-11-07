<?php

use App\Models\Trip;
use App\Models\User;
use App\TripStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lets the owner complete a trip that has started', function () {
    $trip = Trip::factory()->create(['starts_on' => today(), 'ends_on' => today()->addDays(3)]);
    Sanctum::actingAs($trip->user);

    $this->patchJson(route('api.trips.status.update', $trip), ['status' => 'completed'])
        ->assertOk()
        ->assertJsonPath('data.id', $trip->id)
        ->assertJsonPath('data.status', 'completed');

    expect($trip->fresh()->status)->toBe(TripStatus::Completed);
});

it('lets the owner cancel a trip that has not started', function () {
    $trip = Trip::factory()->create(['starts_on' => today()->addDay(), 'ends_on' => today()->addDays(3)]);
    Sanctum::actingAs($trip->user);

    $this->patchJson(route('api.trips.status.update', $trip), ['status' => 'cancelled'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($trip->fresh()->status)->toBe(TripStatus::Cancelled);
});

it('refuses a status change that breaks a business rule', function () {
    $trip = Trip::factory()->create(['starts_on' => today()->addDay(), 'ends_on' => today()->addDays(3)]);
    Sanctum::actingAs($trip->user);

    $this->patchJson(route('api.trips.status.update', $trip), ['status' => 'completed'])
        ->assertForbidden()
        ->assertJsonPath('message', 'The trip cannot be completed before it starts.');

    expect($trip->fresh()->status)->toBe(TripStatus::Planned);
});

it('rejects requests without a bearer token', function () {
    $trip = Trip::factory()->create();

    $this->patchJson(route('api.trips.status.update', $trip), ['status' => 'completed'])
        ->assertUnauthorized();

    expect($trip->fresh()->status)->toBe(TripStatus::Planned);
});

it('hides trips that belong to another user', function () {
    $trip = Trip::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson(route('api.trips.status.update', $trip), ['status' => 'cancelled'])
        ->assertNotFound();

    expect($trip->fresh()->status)->toBe(TripStatus::Planned);
});

it('rejects a missing or unknown status', function (array $payload, string $message) {
    $trip = Trip::factory()->create();
    Sanctum::actingAs($trip->user);

    $this->patchJson(route('api.trips.status.update', $trip), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status' => $message]);

    expect($trip->fresh()->status)->toBe(TripStatus::Planned);
})->with([
    'missing status' => [[], 'The status field is required.'],
    'unknown status' => [['status' => 'finished'], 'The selected status is invalid.'],
]);
