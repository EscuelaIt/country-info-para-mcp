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

test('it filters the countries by part of their name', function () {
    $continent = Continent::factory()->create();

    Country::factory()->for($continent)->count(3)->sequence(
        ['name' => 'Spain', 'cca2' => 'ES', 'cca3' => 'ESP'],
        ['name' => 'France', 'cca2' => 'FR', 'cca3' => 'FRA'],
        ['name' => 'Germany', 'cca2' => 'DE', 'cca3' => 'DEU'],
    )->create();

    CountryInfoServer::tool(CountryListTool::class, ['name' => 'an'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('count', 2)
            ->where('countries.0.name', 'France')
            ->where('countries.1.name', 'Germany')
            ->etc()
        );
});

test('it returns no countries when none match the name filter', function () {
    Country::factory()->for(Continent::factory())->create(['name' => 'Spain']);

    CountryInfoServer::tool(CountryListTool::class, ['name' => 'xyz'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('count', 0)
            ->where('countries', [])
        );
});

test('the input schema exposes an optional name filter', function () {
    $inputSchema = (new CountryListTool)->toArray()['inputSchema'];

    expect($inputSchema['properties']['name']['type'])->toBe('string')
        ->and($inputSchema['properties']['name']['description'] ?? '')->not->toBeEmpty()
        ->and($inputSchema['required'] ?? [])->not->toContain('name');
});

test('it filters the countries by a population range', function () {
    $continent = Continent::factory()->create();

    Country::factory()->for($continent)->count(3)->sequence(
        ['name' => 'Andorra', 'cca2' => 'AD', 'cca3' => 'AND', 'population' => 80_000],
        ['name' => 'Spain', 'cca2' => 'ES', 'cca3' => 'ESP', 'population' => 48_000_000],
        ['name' => 'Germany', 'cca2' => 'DE', 'cca3' => 'DEU', 'population' => 84_000_000],
    )->create();

    CountryInfoServer::tool(CountryListTool::class, ['min_population' => 80_000, 'max_population' => 48_000_000])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('count', 2)
            ->where('countries.0.name', 'Andorra')
            ->where('countries.1.name', 'Spain')
            ->etc()
        );

    CountryInfoServer::tool(CountryListTool::class, ['min_population' => 50_000_000])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('count', 1)
            ->where('countries.0.name', 'Germany')
            ->etc()
        );

    CountryInfoServer::tool(CountryListTool::class, ['max_population' => 1_000_000])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('count', 1)
            ->where('countries.0.name', 'Andorra')
            ->etc()
        );
});

test('it rejects a maximum population lower than the minimum population', function () {
    CountryInfoServer::tool(CountryListTool::class, ['min_population' => 1_000, 'max_population' => 10])
        ->assertHasErrors();
});

test('the input schema exposes optional population filters', function () {
    $inputSchema = (new CountryListTool)->toArray()['inputSchema'];

    foreach (['min_population', 'max_population'] as $field) {
        expect($inputSchema['properties'][$field]['type'])->toBe('integer')
            ->and($inputSchema['properties'][$field]['description'] ?? '')->not->toBeEmpty()
            ->and($inputSchema['required'] ?? [])->not->toContain($field);
    }
});

test('it sorts the countries by the requested field and direction', function (array $arguments, array $expectedNames) {
    $continent = Continent::factory()->create();

    Country::factory()->for($continent)->count(3)->sequence(
        ['name' => 'Andorra', 'cca2' => 'AD', 'cca3' => 'AND', 'population' => 80_000, 'area_km2' => 468],
        ['name' => 'Spain', 'cca2' => 'ES', 'cca3' => 'ESP', 'population' => 48_000_000, 'area_km2' => 505_992],
        ['name' => 'Germany', 'cca2' => 'DE', 'cca3' => 'DEU', 'population' => 84_000_000, 'area_km2' => 357_114],
    )->create();

    CountryInfoServer::tool(CountryListTool::class, $arguments)
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('countries.0.name', $expectedNames[0])
            ->where('countries.1.name', $expectedNames[1])
            ->where('countries.2.name', $expectedNames[2])
            ->etc()
        );
})->with([
    'name by default' => [[], ['Andorra', 'Germany', 'Spain']],
    'name descending' => [['sort_by' => 'name', 'sort_direction' => 'desc'], ['Spain', 'Germany', 'Andorra']],
    'population ascending' => [['sort_by' => 'population'], ['Andorra', 'Spain', 'Germany']],
    'population descending' => [['sort_by' => 'population', 'sort_direction' => 'desc'], ['Germany', 'Spain', 'Andorra']],
    'area ascending' => [['sort_by' => 'area_km2'], ['Andorra', 'Germany', 'Spain']],
    'area descending' => [['sort_by' => 'area_km2', 'sort_direction' => 'desc'], ['Spain', 'Germany', 'Andorra']],
]);

test('it breaks sorting ties by name', function () {
    $continent = Continent::factory()->create();

    Country::factory()->for($continent)->count(2)->sequence(
        ['name' => 'Tuvalu', 'cca2' => 'TV', 'cca3' => 'TUV', 'population' => 10_000],
        ['name' => 'Nauru', 'cca2' => 'NR', 'cca3' => 'NRU', 'population' => 10_000],
    )->create();

    CountryInfoServer::tool(CountryListTool::class, ['sort_by' => 'population', 'sort_direction' => 'desc'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('countries.0.name', 'Nauru')
            ->where('countries.1.name', 'Tuvalu')
            ->etc()
        );
});

test('it rejects invalid sorting options', function (array $arguments) {
    CountryInfoServer::tool(CountryListTool::class, $arguments)
        ->assertHasErrors();
})->with([
    'unknown field' => [['sort_by' => 'gini']],
    'unknown direction' => [['sort_direction' => 'up']],
]);

test('the input schema exposes the sorting options as enums', function () {
    $properties = (new CountryListTool)->toArray()['inputSchema']['properties'];

    expect($properties['sort_by']['enum'])->toBe(['name', 'population', 'area_km2'])
        ->and($properties['sort_direction']['enum'])->toBe(['asc', 'desc'])
        ->and($properties['sort_by']['description'] ?? '')->not->toBeEmpty()
        ->and($properties['sort_direction']['description'] ?? '')->not->toBeEmpty();
});

test('it filters the countries by their capital ignoring case', function () {
    $continent = Continent::factory()->create();

    Country::factory()->for($continent)->count(2)->sequence(
        ['name' => 'Spain', 'cca2' => 'ES', 'cca3' => 'ESP', 'capital' => 'Madrid'],
        ['name' => 'France', 'cca2' => 'FR', 'cca3' => 'FRA', 'capital' => 'Paris'],
    )->create();

    CountryInfoServer::tool(CountryListTool::class, ['capital' => 'madrid'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('count', 1)
            ->where('countries.0.name', 'Spain')
            ->where('countries.0.capital', 'Madrid')
            ->etc()
        );
});

test('it only matches the whole capital name', function () {
    Country::factory()->for(Continent::factory())->create(['name' => 'Spain', 'capital' => 'Madrid']);

    CountryInfoServer::tool(CountryListTool::class, ['capital' => 'Mad'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('count', 0)
            ->where('countries', [])
        );
});

test('the input schema exposes an optional capital filter', function () {
    $inputSchema = (new CountryListTool)->toArray()['inputSchema'];

    expect($inputSchema['properties']['capital']['type'])->toBe('string')
        ->and($inputSchema['properties']['capital']['maxLength'])->toBe(100)
        ->and($inputSchema['properties']['capital']['description'] ?? '')->toContain('exact', 'English')
        ->and($inputSchema['required'] ?? [])->not->toContain('capital');
});
