<?php

use App\Models\Trip;
use App\Models\User;
use App\TripStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('allows the owner to change the status when the business rules are met', function (array $attributes, TripStatus $newStatus) {
    $trip = Trip::factory()->create($attributes);

    expect(Gate::forUser($trip->user)->inspect('changeStatus', [$trip, $newStatus])->allowed())->toBeTrue();
})->with([
    'complete a started trip' => [['starts_on' => today(), 'ends_on' => today()->addDays(3)], TripStatus::Completed],
    'cancel a trip that has not started' => [['starts_on' => today()->addDay(), 'ends_on' => today()->addDays(3)], TripStatus::Cancelled],
]);

it('denies status changes that break the business rules', function (array $attributes, TripStatus $newStatus, string $message) {
    $trip = Trip::factory()->create($attributes);

    $response = Gate::forUser($trip->user)->inspect('changeStatus', [$trip, $newStatus]);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe($message);
})->with([
    'same status' => [[], TripStatus::Planned, 'The trip is already planned.'],
    'completed trip is final' => [['status' => TripStatus::Completed, 'starts_on' => today()->subWeek(), 'ends_on' => today()->subDays(3)], TripStatus::Planned, 'The trip is completed and its status can no longer be changed.'],
    'cancelled trip is final' => [['status' => TripStatus::Cancelled], TripStatus::Planned, 'The trip is cancelled and its status can no longer be changed.'],
    'complete before start' => [['starts_on' => today()->addDay(), 'ends_on' => today()->addDays(3)], TripStatus::Completed, 'The trip cannot be completed before it starts.'],
    'cancel after start' => [['starts_on' => today(), 'ends_on' => today()->addDays(3)], TripStatus::Cancelled, 'The trip cannot be cancelled once it has started.'],
]);

it('hides the trip from users who do not own it', function () {
    $trip = Trip::factory()->create(['starts_on' => today()->addDay(), 'ends_on' => today()->addDays(3)]);

    $response = Gate::forUser(User::factory()->create())->inspect('changeStatus', [$trip, TripStatus::Cancelled]);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});
