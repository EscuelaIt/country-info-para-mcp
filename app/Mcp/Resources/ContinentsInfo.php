<?php

namespace App\Mcp\Resources;

use App\Models\Continent;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('country-info://resources/continents')]
#[MimeType('application/json')]
#[Description('List of the world continents with their aggregated public data (number of countries, total population and total area in km2) and the aggregated ACME LTDA internal business data for each continent, computed as the sum of the countries of that continent: number of countries where ACME operates, operational branches, business volume in USD and customer counts. It also includes the worldwide ACME totals. ACME data is proprietary and only available through this server.')]
class ContinentsInfo extends Resource
{
    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $continents = Continent::query()
            ->withCount([
                'countries',
                'countries as acme_countries_count' => fn ($query) => $query->where('acme_branches', '>', 0),
            ])
            ->withSum('countries', 'population')
            ->withSum('countries', 'area_km2')
            ->withSum('countries', 'acme_branches')
            ->withSum('countries', 'acme_volume_usd')
            ->withSum('countries', 'acme_customers')
            ->orderBy('code')
            ->get()
            ->map(fn (Continent $continent): array => [
                'code' => $continent->code,
                'name' => $continent->name,
                'emoji' => $continent->getContinentEmoji(),
                'countries_count' => $continent->countries_count,
                'population' => (int) $continent->countries_sum_population,
                'area_km2' => (int) $continent->countries_sum_area_km2,
                'acme' => [
                    'countries_with_operations' => $continent->acme_countries_count,
                    'branches' => (int) $continent->countries_sum_acme_branches,
                    'volume_usd' => (int) $continent->countries_sum_acme_volume_usd,
                    'customers' => (int) $continent->countries_sum_acme_customers,
                ],
            ]);

        return Response::json([
            'count' => $continents->count(),
            'continents' => $continents->all(),
            'acme_totals' => [
                'countries_with_operations' => $continents->sum('acme.countries_with_operations'),
                'branches' => $continents->sum('acme.branches'),
                'volume_usd' => $continents->sum('acme.volume_usd'),
                'customers' => $continents->sum('acme.customers'),
            ],
        ]);
    }
}
