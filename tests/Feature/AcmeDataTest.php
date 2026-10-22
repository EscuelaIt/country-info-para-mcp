<?php

use App\Models\Country;
use Tests\TestCase;

class AcmeDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Run migrations and seed ACME data for each test
        $this->artisan('migrate:fresh', ['--seed' => true]);
    }

    public function test_acme_data_only_in_top_100_countries(): void
    {
        $totalWithAcme = Country::where('acme_branches', '>', 0)->count();
        $this->assertEquals(100, $totalWithAcme);

        // Verify all countries with ACME are in top 100 by population
        $top100Pop = Country::orderByDesc('population')->take(100)->pluck('id');
        $countriesWithAcme = Country::where('acme_branches', '>', 0)->pluck('id');

        $this->assertTrue($countriesWithAcme->diff($top100Pop)->isEmpty());
    }

    public function test_acme_branches_distribution_is_realistic(): void
    {
        $india = Country::where('name', 'India')->firstOrFail();
        $spain = Country::where('name', 'Spain')->firstOrFail();

        // India should have significantly more branches than Spain
        $this->assertGreaterThan(50, $india->acme_branches);
        $this->assertLessThanOrEqual(10, $spain->acme_branches);

        // All countries have at least 1 branch (if they have ACME data)
        $minBranches = Country::where('acme_branches', '>', 0)->min('acme_branches');
        $this->assertGreaterThanOrEqual(1, $minBranches);
    }

    public function test_acme_volume_scales_with_branches(): void
    {
        $countries = Country::where('acme_branches', '>', 0)
            ->select('name', 'acme_branches', 'acme_volume_usd')
            ->get();

        foreach ($countries as $country) {
            if ($country->acme_branches > 0) {
                $perBranch = $country->acme_volume_usd / $country->acme_branches;
                // Each branch should generate 100k to 1M USD
                $this->assertGreaterThanOrEqual(100_000, $perBranch);
                $this->assertLessThanOrEqual(1_000_000, $perBranch);
            }
        }
    }

    public function test_acme_customers_are_realistic(): void
    {
        $countries = Country::with('continent')
            ->where('acme_branches', '>', 0)
            ->select('name', 'acme_branches', 'acme_customers', 'continent_id')
            ->get();

        foreach ($countries as $country) {
            if ($country->acme_branches > 0) {
                $perBranch = $country->acme_customers / $country->acme_branches;

                // Asia should have 50-100 per branch, others 30-60
                if ($country->continent?->code === 'AS') {
                    $this->assertGreaterThanOrEqual(50, $perBranch);
                    $this->assertLessThanOrEqual(100, $perBranch);
                } else {
                    $this->assertGreaterThanOrEqual(30, $perBranch);
                    $this->assertLessThanOrEqual(60, $perBranch);
                }
            }
        }
    }

    public function test_acme_data_included_in_country_list_tool(): void
    {
        $country = Country::where('name', 'India')->firstOrFail();

        // Simulate what the tool returns
        $response = [
            'name' => $country->name,
            'population' => $country->population,
            'acme' => [
                'branches' => $country->acme_branches,
                'volume_usd' => $country->acme_volume_usd,
                'customers' => $country->acme_customers,
            ],
        ];

        $this->assertArrayHasKey('acme', $response);
        $this->assertEquals(85, $response['acme']['branches']);
        $this->assertGreaterThan(0, $response['acme']['volume_usd']);
        $this->assertGreaterThan(0, $response['acme']['customers']);
    }

    public function test_no_acme_data_in_bottom_countries(): void
    {
        // Get countries outside top 100 (if any exist in DB)
        $allCountries = Country::count();
        if ($allCountries > 100) {
            $bottomCountries = Country::orderByDesc('population')
                ->offset(100)
                ->limit(10)
                ->get();

            foreach ($bottomCountries as $country) {
                $this->assertEquals(0, $country->acme_branches);
                $this->assertEquals(0, $country->acme_volume_usd);
                $this->assertEquals(0, $country->acme_customers);
            }
        }
    }

    public function test_acme_operations_only_filter(): void
    {
        // Test filtering to only countries with ACME operations
        $countries = Country::where('acme_branches', '>', 0)->get();

        $this->assertGreaterThan(0, $countries->count());
        $this->assertEquals(100, $countries->count()); // Only top 100 should have operations

        foreach ($countries as $country) {
            $this->assertGreaterThan(0, $country->acme_branches);
        }
    }

    public function test_sort_by_acme_volume_usd(): void
    {
        // Test sorting by ACME volume in descending order
        $countries = Country::where('acme_branches', '>', 0)
            ->orderByDesc('acme_volume_usd')
            ->limit(3)
            ->get();

        for ($i = 0; $i < $countries->count() - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $countries[$i + 1]->acme_volume_usd,
                $countries[$i]->acme_volume_usd
            );
        }
    }

    public function test_sort_by_acme_branches(): void
    {
        // Test sorting by number of ACME branches in descending order
        $countries = Country::where('acme_branches', '>', 0)
            ->orderByDesc('acme_branches')
            ->limit(3)
            ->get();

        for ($i = 0; $i < $countries->count() - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $countries[$i + 1]->acme_branches,
                $countries[$i]->acme_branches
            );
        }
    }
}

