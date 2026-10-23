<?php

use App\Mcp\Resources\ContinentsInfo;
use App\Mcp\Servers\CountryInfoServer;
use App\Models\Continent;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it aggregates the acme data of the countries of each continent', function () {
    $europe = Continent::factory()->create(['code' => 'EU', 'name' => 'Europa']);
    $asia = Continent::factory()->create(['code' => 'AS', 'name' => 'Asia']);

    Country::factory()->for($europe)->count(3)->sequence(
        ['cca2' => 'ES', 'cca3' => 'ESP', 'population' => 48_000_000, 'area_km2' => 505_990, 'acme_branches' => 5, 'acme_volume_usd' => 2_000_000, 'acme_customers' => 200],
        ['cca2' => 'FR', 'cca3' => 'FRA', 'population' => 68_000_000, 'area_km2' => 551_695, 'acme_branches' => 7, 'acme_volume_usd' => 3_500_000, 'acme_customers' => 300],
        ['cca2' => 'AD', 'cca3' => 'AND', 'population' => 80_000, 'area_km2' => 468, 'acme_branches' => 0, 'acme_volume_usd' => 0, 'acme_customers' => 0],
    )->create();

    Country::factory()->for($asia)->create([
        'cca2' => 'IN', 'cca3' => 'IND', 'population' => 1_400_000_000, 'area_km2' => 3_287_263,
        'acme_branches' => 60, 'acme_volume_usd' => 30_000_000, 'acme_customers' => 4_500,
    ]);

    CountryInfoServer::resource(ContinentsInfo::class)
        ->assertOk()
        ->assertSee([
            '"count":2',
            '{"code":"AS","name":"Asia","emoji":"🌏","countries_count":1,"population":1400000000,"area_km2":3287263,"acme":{"countries_with_operations":1,"branches":60,"volume_usd":30000000,"customers":4500}}',
            '{"code":"EU","name":"Europa","emoji":"🏛️","countries_count":3,"population":116080000,"area_km2":1058153,"acme":{"countries_with_operations":2,"branches":12,"volume_usd":5500000,"customers":500}}',
            '"acme_totals":{"countries_with_operations":3,"branches":72,"volume_usd":35500000,"customers":5000}',
        ]);
});

test('continents without countries report zero totals', function () {
    Continent::factory()->create(['code' => 'AN', 'name' => 'Antártida']);

    CountryInfoServer::resource(ContinentsInfo::class)
        ->assertOk()
        ->assertSee('{"code":"AN","name":"Antártida","emoji":"❄️","countries_count":0,"population":0,"area_km2":0,"acme":{"countries_with_operations":0,"branches":0,"volume_usd":0,"customers":0}}');
});
