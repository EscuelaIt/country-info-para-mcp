<?php

use App\Models\Continent;
use App\Models\Country;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the trips of every user grouped by owner', function () {
    $country = Country::factory()->for(Continent::factory())->create(['name' => 'Spain', 'cca2' => 'ES']);
    $ana = User::factory()->create(['name' => 'Ana García']);
    $carlos = User::factory()->create(['name' => 'Carlos Pérez']);

    Trip::factory()->for($ana)->for($country)->create(['purpose' => 'Ana quarterly review']);
    Trip::factory()->for($carlos)->for($country)->cancelled()->create(['purpose' => 'Carlos sales training']);

    $this->get(route('trips.index'))
        ->assertOk()
        ->assertSeeInOrder(['Ana García', 'Ana quarterly review', 'Carlos Pérez', 'Carlos sales training', 'Cancelled'])
        ->assertSee('Spain');
});

it('shows an empty state when there are no trips', function () {
    $this->get(route('trips.index'))
        ->assertOk()
        ->assertSee('No trips registered yet.');
});

it('does not link the trips page from the home page', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee(route('trips.index'));
});
