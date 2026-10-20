<?php

use App\Mcp\Servers\CountryInfoServer;
use App\Mcp\Tools\CountryListTool;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the output schema describes every country field', function () {
    $outputSchema = (new CountryListTool)->toArray()['outputSchema'];

    $countryProperties = $outputSchema['properties']['countries']['items']['properties'];

    expect(array_keys($countryProperties))->toBe([
        'name', 'official_name', 'cca2', 'cca3', 'flag_emoji', 'capital', 'continent',
        'latitude', 'longitude', 'area_km2', 'population', 'gini', 'languages', 'currencies',
    ]);

    foreach ($countryProperties as $property) {
        expect($property['description'] ?? '')->not->toBeEmpty();
    }

    foreach ($countryProperties['currencies']['items']['properties'] as $property) {
        expect($property['description'] ?? '')->not->toBeEmpty();
    }
});

test('nullable fields in the output schema use anyOf branches with a single type', function () {
    $countryProperties = (new CountryListTool)->toArray()['outputSchema']['properties']['countries']['items']['properties'];

    $properties = [
        ...$countryProperties,
        'currency_symbol' => $countryProperties['currencies']['items']['properties']['symbol'],
    ];

    foreach ($properties as $property) {
        expect($property['type'] ?? null)->not->toBeArray();
    }

    expect($countryProperties['capital'])->toMatchArray([
        'anyOf' => [['type' => 'string'], ['type' => 'null']],
    ]);
});

test('the output schema describes the countries count', function () {
    $outputSchema = (new CountryListTool)->toArray()['outputSchema'];

    expect($outputSchema['properties']['count']['type'])->toBe('integer')
        ->and($outputSchema['properties']['count']['description'] ?? '')->not->toBeEmpty();
});

test('it returns the countries as structured content', function () {
    $continent = Continent::factory()->create(['code' => 'EU', 'name' => 'Europe']);
    $currency = Currency::factory()->create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€']);

    $country = Country::factory()->for($continent)->create([
        'name' => 'Spain',
        'official_name' => 'Kingdom of Spain',
        'cca2' => 'ES',
        'cca3' => 'ESP',
        'gini' => 34.6,
        'languages' => ['spa' => 'Spanish'],
    ]);
    $country->currencies()->attach($currency);

    CountryInfoServer::tool(CountryListTool::class)
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('count', 1)
            ->has('countries', 1)
            ->where('countries.0.name', 'Spain')
            ->where('countries.0.cca3', 'ESP')
            ->where('countries.0.continent', 'Europe')
            ->where('countries.0.gini', 34.6)
            ->where('countries.0.languages', ['spa' => 'Spanish'])
            ->where('countries.0.currencies.0.code', 'EUR')
            ->etc()
        );
});

test('it returns the number of countries in the response', function () {
    $continent = Continent::factory()->create();

    Country::factory()->for($continent)->count(3)->sequence(
        ['cca2' => 'ES', 'cca3' => 'ESP'],
        ['cca2' => 'FR', 'cca3' => 'FRA'],
        ['cca2' => 'DE', 'cca3' => 'DEU'],
    )->create();

    CountryInfoServer::tool(CountryListTool::class)
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('count', 3)
            ->has('countries', 3)
        );
});
