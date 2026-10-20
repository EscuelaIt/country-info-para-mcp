<?php

namespace App\Mcp\Tools;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Retrieve the list of countries, ordered by name, with detailed data: ISO codes, flag, capital, continent, coordinates, area, population, Gini index, languages and currencies. See the output schema for the meaning of each field.')]
class CountryListTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $countries = Country::query()
            ->with(['continent', 'currencies'])
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
            //
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
                ->description('List of countries ordered alphabetically by common name.')
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
                ]))
                ->required(),
        ];
    }
}
