<?php

namespace App\Mcp\Tools;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Retrieve comprehensive country data combined with ACME LTDA internal business information. Returns: public data (ISO codes, flag, capital, continent, coordinates, area, population, Gini index, languages, currencies) plus proprietary ACME data (operational branches, business volume in USD, customer counts). ACME operates in the top 100 countries by population. Results can be sorted by name, population, or area (default: name ascending), and filtered by country name fragment, capital city, or population range. This tool demonstrates MCP value by providing access to internal corporate data that LLM models cannot retrieve directly.')]
class CountryListTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'capital' => ['nullable', 'string', 'max:100'],
            'min_population' => ['nullable', 'integer', 'min:0'],
            'max_population' => [
                'nullable', 'integer', 'min:0',
                Rule::when($request->get('min_population') !== null, ['gte:min_population']),
            ],
            'sort_by' => ['nullable', Rule::in(['name', 'population', 'area_km2'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $countries = Country::query()
            ->with(['continent', 'currencies'])
            ->when(
                filled($validated['name'] ?? null),
                fn ($query) => $query->where('name', 'like', '%'.$validated['name'].'%'),
            )
            ->when(
                filled($validated['capital'] ?? null),
                fn ($query) => $query->whereRaw('LOWER(capital) = ?', [mb_strtolower(trim($validated['capital']))]),
            )
            ->when(
                isset($validated['min_population']),
                fn ($query) => $query->where('population', '>=', $validated['min_population']),
            )
            ->when(
                isset($validated['max_population']),
                fn ($query) => $query->where('population', '<=', $validated['max_population']),
            )
            ->orderBy($validated['sort_by'] ?? 'name', $validated['sort_direction'] ?? 'asc')
            ->orderBy('name')
            ->get()
            ->map(fn (Country $country): array => [
                'name' => $country->name,
                'official_name' => $country->official_name,
                'cca2' => $country->cca2,
                'cca3' => $country->cca3,
                'flag_emoji' => $country->flag_emoji,
                'capital' => $country->capital,
                'continent' => $country->continent?->name,
                'latitude' => $country->latitude !== null ? (float) $country->latitude : null,
                'longitude' => $country->longitude !== null ? (float) $country->longitude : null,
                'area_km2' => $country->area_km2,
                'population' => $country->population,
                'gini' => $country->gini !== null ? (float) $country->gini : null,
                'languages' => $country->languages,
                'currencies' => $country->currencies->map(fn (Currency $currency): array => [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                ])->all(),
                'acme' => [
                    'branches' => $country->acme_branches,
                    'volume_usd' => $country->acme_volume_usd,
                    'customers' => $country->acme_customers,
                ],
            ]);

        return Response::structured([
            'count' => $countries->count(),
            'countries' => $countries->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Optional case-insensitive fragment of the country common name in English to filter by (e.g. "spa" matches "Spain"). Omit it to retrieve all countries.'),
            'capital' => $schema->string()->max(100)
                ->description('Optional capital city to filter by. Only the countries whose capital is this city are returned. Requirements: (1) it must be the full, exact name of the city, partial names do not match (e.g. "Madrid" matches Spain, but "Mad" returns nothing); (2) it must be the English name of the city, translate it if the user gives it in another language (e.g. use "Rome", not "Roma"; "Warsaw", not "Varsovia"); (3) keep its accents and diacritics (e.g. "Bogotá", not "Bogota"). Letter case does not matter. To search by part of a name, use the "name" filter on the country instead.'),
            'min_population' => $schema->integer()->min(0)
                ->description('Optional minimum number of inhabitants (inclusive). Only countries with a population greater than or equal to this value are returned.'),
            'max_population' => $schema->integer()->min(0)
                ->description('Optional maximum number of inhabitants (inclusive). Only countries with a population less than or equal to this value are returned. Must be greater than or equal to "min_population" when both are given.'),
            'sort_by' => $schema->string()->enum(['name', 'population', 'area_km2'])->default('name')
                ->description('Optional field to sort the countries by: "name" (common name), "population" (number of inhabitants) or "area_km2" (surface area). Defaults to "name". Ties are broken by name.'),
            'sort_direction' => $schema->string()->enum(['asc', 'desc'])->default('asc')
                ->description('Optional sort direction: "asc" (ascending) or "desc" (descending). Defaults to "asc". Use "desc" to get e.g. the most populated or largest countries first.'),
        ];
    }

    /**
     * Get the tool's output schema, describing every field of the returned countries.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'count' => $schema->integer()->description('Total number of countries returned in the "countries" list.')->required(),
            'countries' => $schema->array()
                ->description('List of countries in the requested order (alphabetically by common name by default).')
                ->items($schema->object([
                    'name' => $schema->string()->description('Common name of the country in English (e.g. "Spain").')->required(),
                    'official_name' => $schema->anyOf([$schema->string()])->description('Official long-form name of the country (e.g. "Kingdom of Spain").')->nullable(),
                    'cca2' => $schema->string()->description('ISO 3166-1 alpha-2 two-letter country code (e.g. "ES").')->required(),
                    'cca3' => $schema->string()->description('ISO 3166-1 alpha-3 three-letter country code (e.g. "ESP").')->required(),
                    'flag_emoji' => $schema->anyOf([$schema->string()])->description('Unicode emoji of the country flag (e.g. "🇪🇸").')->nullable(),
                    'capital' => $schema->anyOf([$schema->string()])->description('Name of the capital city.')->nullable(),
                    'continent' => $schema->anyOf([$schema->string()])->description('Name of the continent the country belongs to (e.g. "Europe").')->nullable(),
                    'latitude' => $schema->anyOf([$schema->number()])->description('Latitude of the approximate geographic center of the country, in decimal degrees (-90 to 90).')->nullable(),
                    'longitude' => $schema->anyOf([$schema->number()])->description('Longitude of the approximate geographic center of the country, in decimal degrees (-180 to 180).')->nullable(),
                    'area_km2' => $schema->integer()->description('Total surface area of the country in square kilometres.')->required(),
                    'population' => $schema->integer()->description('Total number of inhabitants.')->required(),
                    'gini' => $schema->anyOf([$schema->number()])->description('Gini coefficient of income inequality, from 0 (perfect equality) to 100 (maximum inequality).')->nullable(),
                    'languages' => $schema->anyOf([$schema->object()])->description('Official languages, as a map of ISO 639-3 language code to language name (e.g. {"spa": "Spanish"}).')->nullable(),
                    'currencies' => $schema->array()
                        ->description('Currencies in official use in the country.')
                        ->items($schema->object([
                            'code' => $schema->string()->description('ISO 4217 three-letter currency code (e.g. "EUR").')->required(),
                            'name' => $schema->string()->description('Name of the currency (e.g. "Euro").')->required(),
                            'symbol' => $schema->anyOf([$schema->string()])->description('Currency symbol (e.g. "€").')->nullable(),
                        ]))
                        ->required(),
                    'acme' => $schema->object([
                        'branches' => $schema->integer()->description('Number of ACME LTDA branches operating in the country.')->required(),
                        'volume_usd' => $schema->integer()->description('Annual business volume of ACME LTDA in the country, in USD.')->required(),
                        'customers' => $schema->integer()->description('Number of active ACME LTDA customers in the country.')->required(),
                    ])->description('ACME LTDA business data for this country (only available for countries with ACME operations).')->required(),
                ]))
                ->required(),
        ];
    }
}
