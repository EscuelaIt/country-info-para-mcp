<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    /**
     * Seed trips to ACME branches for every user.
     *
     * Each user gets a mix of planned, completed and cancelled trips
     * to countries where ACME has at least one branch.
     */
    public function run(): void
    {
        $acmeCountries = Country::where('acme_branches', '>', 0)->get();

        User::all()->each(function (User $user) use ($acmeCountries) {
            Trip::factory()
                ->count(3)
                ->for($user)
                ->sequence(fn () => ['country_id' => $acmeCountries->random()->id])
                ->create();

            Trip::factory()
                ->count(2)
                ->completed()
                ->for($user)
                ->sequence(fn () => ['country_id' => $acmeCountries->random()->id])
                ->create();

            Trip::factory()
                ->cancelled()
                ->for($user)
                ->state(fn () => ['country_id' => $acmeCountries->random()->id])
                ->create();
        });

        $this->command->info('Seeded '.Trip::count().' trips for '.User::count().' users.');
    }
}
