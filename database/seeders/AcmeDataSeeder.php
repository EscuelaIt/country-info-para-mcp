<?php

namespace Database\Seeders;

use App\Models\Continent;
use App\Models\Country;
use Illuminate\Database\Seeder;

class AcmeDataSeeder extends Seeder
{
    /**
     * Seed ACME LTDA business data for countries.
     *
     * Logic:
     * - ACME operates only in the top 100 countries by population
     * - Number of branches based on:
     *   - Countries with < population of 100th largest: 0 branches (no operations)
     *   - Base: 1 branch for countries in top 100
     *   - Europe: +3-10 branches per additional 50M inhabitants
     *   - Other regions: +1-3 branches per additional 50M inhabitants
     * - Volume: $0.1M - $1M per branch (random)
     * - Customers:
     *   - Asia: 50-100 per branch
     *   - Other regions: 30-60 per branch
     */
    public function run(): void
    {
        $this->command->info('Seeding ACME LTDA business data...');

        // Get all countries sorted by population descending
        $countries = Country::with('continent')
            ->orderByDesc('population')
            ->get();

        // Determine the 100th largest population threshold
        $top100Countries = $countries->take(100);
        $population100thLargest = $top100Countries->last()?->population ?? 0;

        $this->command->info("Top 100 countries threshold: {$population100thLargest} population");

        $europaContinentId = Continent::where('code', 'EU')->first()?->id;
        $asiaContinentId = Continent::where('code', 'AS')->first()?->id;

        $processedCount = 0;
        $totalOperations = 0;

        foreach ($countries as $country) {
            // Skip countries below the top 100 threshold
            if ($country->population < $population100thLargest) {
                continue;
            }

            $branches = $this->calculateBranches($country, $europaContinentId, $population100thLargest);
            $volume = $this->calculateVolume($branches);
            $customers = $this->calculateCustomers($branches, $country, $asiaContinentId);

            $country->update([
                'acme_branches' => $branches,
                'acme_volume_usd' => $volume,
                'acme_customers' => $customers,
            ]);

            $processedCount++;
            if ($branches > 0) {
                $totalOperations++;
                $this->command->line("  ✓ {$country->name}: {$branches} branches | \${$volume} USD | {$customers} customers");
            }
        }

        $this->command->info("✓ Processed {$processedCount} countries");
        $this->command->info("✓ ACME operates in {$totalOperations} countries");
    }

    /**
     * Calculate number of branches based on population and continent.
     */
    private function calculateBranches(Country $country, ?int $europaContinentId, int $threshold): int
    {
        // Base: 1 branch for being in top 100
        $baseBranches = 1;

        // Calculate additional inhabitants beyond threshold
        $extraPopulation = $country->population - $threshold;
        if ($extraPopulation <= 0) {
            return $baseBranches;
        }

        // Each 50M inhabitants = additional branches
        $fiftyMillionBlocks = (int) floor($extraPopulation / 50_000_000);

        if ($country->continent_id === $europaContinentId) {
            // Europe: 3-10 branches per 50M
            $additionalBranches = $fiftyMillionBlocks * random_int(3, 10);
        } else {
            // Other regions: 1-3 branches per 50M
            $additionalBranches = $fiftyMillionBlocks * random_int(1, 3);
        }

        return $baseBranches + $additionalBranches;
    }

    /**
     * Calculate business volume: $0.1M - $1M per branch.
     */
    private function calculateVolume(int $branches): int
    {
        if ($branches === 0) {
            return 0;
        }

        // Per branch: random between 100k and 1M USD
        $perBranchUsd = random_int(100_000, 1_000_000);

        return $perBranchUsd * $branches;
    }

    /**
     * Calculate number of customers based on region and branches.
     *
     * Asia: 50-100 per branch
     * Others: 30-60 per branch
     */
    private function calculateCustomers(int $branches, Country $country, ?int $asiaContinentId): int
    {
        if ($branches === 0) {
            return 0;
        }

        $perBranchRange = $country->continent_id === $asiaContinentId
            ? random_int(50, 100)
            : random_int(30, 60);

        return $perBranchRange * $branches;
    }
}
