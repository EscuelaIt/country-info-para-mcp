<?php

namespace Database\Factories;

use App\Models\Continent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Continent>
 */
class ContinentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $continents = [
            ['code' => 'EU', 'name' => 'Europa'],
            ['code' => 'AS', 'name' => 'Asia'],
            ['code' => 'AF', 'name' => 'África'],
            ['code' => 'NA', 'name' => 'América del Norte'],
            ['code' => 'SA', 'name' => 'América del Sur'],
            ['code' => 'OC', 'name' => 'Oceanía'],
            ['code' => 'AN', 'name' => 'Antártida'],
        ];

        return $this->faker->randomElement($continents);
    }
}
