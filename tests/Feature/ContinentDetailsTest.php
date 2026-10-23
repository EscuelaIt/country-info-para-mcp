<?php

use App\Mcp\Resources\ContinentDetails;
use App\Mcp\Servers\CountryInfoServer;
use App\Models\Continent;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $europe = Continent::factory()->create(['code' => 'EU', 'name' => 'Europa']);
    $asia = Continent::factory()->create(['code' => 'AS', 'name' => 'Asia']);

    Country::factory()->for($europe)->count(4)->sequence(
        ['name' => 'Spain', 'cca2' => 'ES', 'cca3' => 'ESP', 'population' => 48_000_000, 'area_km2' => 505_990, 'acme_branches' => 5, 'acme_volume_usd' => 2_000_000, 'acme_customers' => 200],
        ['name' => 'France', 'cca2' => 'FR', 'cca3' => 'FRA', 'population' => 68_000_000, 'area_km2' => 551_695, 'acme_branches' => 15, 'acme_volume_usd' => 3_000_000, 'acme_customers' => 300],
        ['name' => 'Andorra', 'cca2' => 'AD', 'cca3' => 'AND', 'population' => 80_000, 'area_km2' => 468, 'acme_branches' => 0, 'acme_volume_usd' => 0, 'acme_customers' => 0],
        ['name' => 'Portugal', 'cca2' => 'PT', 'cca3' => 'PRT', 'population' => 10_000_000, 'area_km2' => 92_212, 'acme_branches' => 0, 'acme_volume_usd' => 0, 'acme_customers' => 0],
    )->create();

    Country::factory()->for($asia)->create([
        'name' => 'India', 'cca2' => 'IN', 'cca3' => 'IND', 'population' => 1_400_000_000, 'area_km2' => 3_287_263,
        'acme_branches' => 30, 'acme_volume_usd' => 15_000_000, 'acme_customers' => 1_500,
    ]);
});

test('it returns the extended data of a continent with its share of the global acme totals', function () {
    CountryInfoServer::resource(ContinentDetails::class, ['code' => 'EU'])
        ->assertOk()
        ->assertSee([
            '"code":"EU","name":"Europa","emoji":"🏛️","countries_count":4,"population":126080000,"area_km2":1150365',
            '"acme":{"countries_with_operations":2,"countries_without_operations":2,"branches":20,"volume_usd":5000000,"customers":500',
            '"share_of_global":{"volume_usd_percentage":25,"branches_percentage":40,"customers_percentage":25}',
        ]);
});

test('it lists the countries where acme operates sorted by business volume', function () {
    CountryInfoServer::resource(ContinentDetails::class, ['code' => 'EU'])
        ->assertOk()
        ->assertSee('"countries_with_acme_operations":[{"name":"France","cca2":"FR","cca3":"FRA"')
        ->assertSee('"acme":{"branches":15,"volume_usd":3000000,"customers":300}},{"name":"Spain"');
});

test('it lists the countries where acme does not operate yet sorted by population', function () {
    CountryInfoServer::resource(ContinentDetails::class, ['code' => 'EU'])
        ->assertOk()
        ->assertSee('"countries_without_acme_operations":[{"name":"Portugal"')
        ->assertSee('{"name":"Andorra"');
});

test('the continent code is case insensitive', function () {
    CountryInfoServer::resource(ContinentDetails::class, ['code' => 'as'])
        ->assertOk()
        ->assertSee('"code":"AS"')
        ->assertSee('"countries_without_acme_operations":[]');
});

test('it returns an error with the valid codes when the continent does not exist', function () {
    CountryInfoServer::resource(ContinentDetails::class, ['code' => 'XX'])
        ->assertHasErrors(['Continent with code "XX" not found. Valid continent codes are: AS, EU.']);
});

test('it is registered as a resource template', function () {
    expect((new ContinentDetails)->toArray())
        ->toHaveKey('uriTemplate', 'country-info://resources/continents/{code}')
        ->toHaveKey('mimeType', 'application/json');
});
