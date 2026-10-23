<?php

namespace App\Mcp\Resources;

use App\Models\Continent;
use App\Models\Country;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[MimeType('application/json')]
#[Description('Extended data of a single continent, identified by its two-letter code in the URI (AF = Africa, AM = America, AN = Antarctica, AS = Asia, EU = Europe, OC = Oceania; case-insensitive). Returns the continent public data (number of countries, total population and total area in km2), the aggregated ACME LTDA internal business data of its countries (branches, business volume in USD and customers), the share of the worldwide ACME totals that this continent represents (percentage of business volume, branches and customers), the list of countries of the continent where ACME operates (with their ACME data, sorted by business volume) and the list of countries where ACME does not operate yet (sorted by population, as expansion opportunities). ACME data is proprietary and only available through this server.')]
class ContinentDetails extends Resource implements HasUriTemplate
{
    /**
     * Get the URI template for this resource.
     */
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('country-info://resources/continents/{code}');
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $code = mb_strtoupper(trim((string) $request->get('code')));

        $continent = Continent::query()
            ->where('code', $code)
            ->with(['countries' => fn ($query) => $query->orderBy('name')])
            ->first();

        if ($continent === null) {
            $validCodes = Continent::query()->orderBy('code')->pluck('code')->implode(', ');

            return Response::error("Continent with code \"{$code}\" not found. Valid continent codes are: {$validCodes}.");
        }

        $globalTotals = Country::query()
            ->selectRaw('COALESCE(SUM(acme_branches), 0) as branches')
            ->selectRaw('COALESCE(SUM(acme_volume_usd), 0) as volume_usd')
            ->selectRaw('COALESCE(SUM(acme_customers), 0) as customers')
            ->first();

        [$operatedCountries, $notOperatedCountries] = $continent->countries
            ->partition(fn (Country $country): bool => $country->acme_branches > 0);

        $branches = $operatedCountries->sum('acme_branches');
        $volumeUsd = $operatedCountries->sum('acme_volume_usd');
        $customers = $operatedCountries->sum('acme_customers');

        return Response::json([
            'code' => $continent->code,
            'name' => $continent->name,
            'emoji' => $continent->getContinentEmoji(),
            'countries_count' => $continent->countries->count(),
            'population' => (int) $continent->countries->sum('population'),
            'area_km2' => (int) $continent->countries->sum('area_km2'),
            'acme' => [
                'countries_with_operations' => $operatedCountries->count(),
                'countries_without_operations' => $notOperatedCountries->count(),
                'branches' => (int) $branches,
                'volume_usd' => (int) $volumeUsd,
                'customers' => (int) $customers,
                'share_of_global' => [
                    'volume_usd_percentage' => $this->percentage($volumeUsd, $globalTotals->volume_usd),
                    'branches_percentage' => $this->percentage($branches, $globalTotals->branches),
                    'customers_percentage' => $this->percentage($customers, $globalTotals->customers),
                ],
            ],
            'countries_with_acme_operations' => $operatedCountries
                ->sortByDesc('acme_volume_usd')
                ->map(fn (Country $country): array => [
                    ...$this->countrySummary($country),
                    'acme' => [
                        'branches' => $country->acme_branches,
                        'volume_usd' => $country->acme_volume_usd,
                        'customers' => $country->acme_customers,
                    ],
                ])
                ->values()
                ->all(),
            'countries_without_acme_operations' => $notOperatedCountries
                ->sortByDesc('population')
                ->map(fn (Country $country): array => $this->countrySummary($country))
                ->values()
                ->all(),
        ]);
    }

    /**
     * Get the basic public data of a country.
     *
     * @return array{name: string, cca2: string, cca3: string, flag_emoji: ?string, capital: ?string, population: int}
     */
    protected function countrySummary(Country $country): array
    {
        return [
            'name' => $country->name,
            'cca2' => $country->cca2,
            'cca3' => $country->cca3,
            'flag_emoji' => $country->flag_emoji,
            'capital' => $country->capital,
            'population' => $country->population,
        ];
    }

    /**
     * Calculate the percentage that a value represents of a total, rounded to two decimals.
     */
    protected function percentage(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round($value / $total * 100, 2);
    }
}
