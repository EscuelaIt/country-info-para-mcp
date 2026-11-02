<?php

use App\Mcp\Prompts\CountryStatus;
use App\Mcp\Servers\CountryInfoServer;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $europe = Continent::factory()->create(['code' => 'EU', 'name' => 'Europa']);

    Country::factory()->for($europe)->count(3)->sequence(
        ['name' => 'Spain', 'official_name' => 'Kingdom of Spain', 'cca2' => 'ES', 'cca3' => 'ESP', 'capital' => 'Madrid', 'population' => 48_000_000, 'area_km2' => 505_990, 'languages' => ['spa' => 'Spanish'], 'acme_branches' => 5, 'acme_volume_usd' => 2_000_000, 'acme_customers' => 200],
        ['name' => 'France', 'official_name' => 'French Republic', 'cca2' => 'FR', 'cca3' => 'FRA', 'acme_branches' => 15, 'acme_volume_usd' => 6_000_000, 'acme_customers' => 600],
        ['name' => 'Andorra', 'official_name' => 'Principality of Andorra', 'cca2' => 'AD', 'cca3' => 'AND', 'acme_branches' => 0, 'acme_volume_usd' => 0, 'acme_customers' => 0],
    )->create();

    Country::query()->where('cca2', 'ES')->first()->currencies()->attach(
        Currency::factory()->create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'])
    );
});

test('it composes the prompt with the public and acme data of the country', function () {
    CountryInfoServer::prompt(CountryStatus::class, ['pais' => 'Spain'])
        ->assertOk()
        ->assertSee([
            'Spain (ES)',
            '- Continente: Europa',
            '- Capital: Madrid',
            '- Población: 48.000.000 habitantes',
            '- Superficie: 505.990 km²',
            '- Idiomas: Spanish',
            '- Monedas: EUR',
            '- Estado: Con operaciones',
            '- Sedes físicas: 5',
            '- Clientes: 200 (25% del total mundial de ACME)',
            '- Facturación anual: 2.000.000 USD (25% del total mundial de ACME)',
        ]);
});

test('it includes the instructions to write the answer', function () {
    CountryInfoServer::prompt(CountryStatus::class, ['pais' => 'Spain'])
        ->assertOk()
        ->assertSee([
            'Instrucciones para redactar la respuesta:',
            '1. Evalúa el peso del mercado en función de sus clientes e ingresos.',
            '2. Si las sedes físicas son 0, comenta si sería recomendable iniciar operaciones.',
            '3. Aporta una recomendación estratégica breve para este país.',
        ]);
});

test('it separates the assistant instructions from the user message', function () {
    [$assistantMessage, $userMessage] = (new CountryStatus)->handle(new Request(['pais' => 'Spain']));

    expect($assistantMessage->role())->toBe(Role::Assistant)
        ->and((string) $assistantMessage->content())
        ->toContain('Instrucciones para redactar la respuesta:')
        ->not->toContain('Datos internos de ACME LTDA:');

    expect($userMessage->role())->toBe(Role::User)
        ->and((string) $userMessage->content())
        ->toContain('Redacta una ficha ejecutiva sobre el estado de ACME LTDA en "Spain".')
        ->toContain('Datos internos de ACME LTDA:')
        ->not->toContain('Instrucciones para redactar la respuesta:');
});

test('it finds the country ignoring the letter case', function (string $search) {
    CountryInfoServer::prompt(CountryStatus::class, ['pais' => $search])
        ->assertOk()
        ->assertSee('France (FR)');
})->with(['common name' => 'france', 'official name' => 'french republic', 'two letter code' => 'fr', 'three letter code' => 'fra']);

test('it reports a country without acme operations', function () {
    CountryInfoServer::prompt(CountryStatus::class, ['pais' => 'AD'])
        ->assertOk()
        ->assertSee([
            '- Estado: Sin operaciones',
            '- Sedes físicas: 0',
            '- Clientes: 0 (0% del total mundial de ACME)',
        ]);
});

test('it asks to use the country list tool when the name is not stored', function () {
    CountryInfoServer::prompt(CountryStatus::class, ['pais' => 'España'])
        ->assertOk()
        ->assertSee([
            'herramienta "country-list-tool"',
            'Si la herramienta no devuelve ningún país que corresponda a "España"',
        ])
        ->assertDontSee('Datos internos de ACME LTDA:');
});

test('it requires the country argument', function () {
    CountryInfoServer::prompt(CountryStatus::class, [])
        ->assertHasErrors();
});
