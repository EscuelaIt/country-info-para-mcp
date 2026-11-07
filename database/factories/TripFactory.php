<?php

namespace Database\Factories;

use App\Models\Continent;
use App\Models\Country;
use App\Models\Trip;
use App\Models\User;
use App\TripStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = $this->faker->dateTimeBetween('+1 week', '+6 months');

        return [
            'user_id' => User::factory(),
            'country_id' => Country::factory()->for(Continent::factory()),
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->modify('+'.$this->faker->numberBetween(1, 10).' days'),
            'purpose' => $this->faker->randomElement([
                'Quarterly review with the local branch',
                'Onboarding of new branch staff',
                'Meeting with key customers',
                'Sales team training',
                'Branch opening ceremony',
                'Annual budget planning',
                'IT infrastructure audit',
            ]),
            'status' => TripStatus::Planned,
        ];
    }

    /**
     * Indicate that the trip already took place.
     */
    public function completed(): static
    {
        return $this->state(function () {
            $startsOn = $this->faker->dateTimeBetween('-1 year', '-2 weeks');

            return [
                'starts_on' => $startsOn,
                'ends_on' => (clone $startsOn)->modify('+'.$this->faker->numberBetween(1, 10).' days'),
                'status' => TripStatus::Completed,
            ];
        });
    }

    /**
     * Indicate that the trip was cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => TripStatus::Cancelled,
        ]);
    }
}
