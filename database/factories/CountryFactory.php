<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = [
            ['name' => 'Spain', 'official_name' => 'Kingdom of Spain', 'cca2' => 'ES', 'cca3' => 'ESP', 'flag_emoji' => '🇪🇸', 'capital' => 'Madrid', 'latitude' => '40.463667', 'longitude' => '-3.74922', 'area_km2' => 505990, 'population' => 47615034, 'gini' => 34.6, 'languages' => ['spa' => 'Spanish', 'cat' => 'Catalan', 'eus' => 'Basque']],
            ['name' => 'France', 'official_name' => 'French Republic', 'cca2' => 'FR', 'cca3' => 'FRA', 'flag_emoji' => '🇫🇷', 'capital' => 'Paris', 'latitude' => '46.227638', 'longitude' => '2.213749', 'area_km2' => 643801, 'population' => 67750000, 'gini' => 32.4, 'languages' => ['fra' => 'French']],
            ['name' => 'Germany', 'official_name' => 'Federal Republic of Germany', 'cca2' => 'DE', 'cca3' => 'DEU', 'flag_emoji' => '🇩🇪', 'capital' => 'Berlin', 'latitude' => '51.165691', 'longitude' => '10.451526', 'area_km2' => 357022, 'population' => 83369843, 'gini' => 31.7, 'languages' => ['deu' => 'German']],
            ['name' => 'Italy', 'official_name' => 'Italian Republic', 'cca2' => 'IT', 'cca3' => 'ITA', 'flag_emoji' => '🇮🇹', 'capital' => 'Rome', 'latitude' => '41.871940', 'longitude' => '12.56738', 'area_km2' => 301340, 'population' => 56366000, 'gini' => 36.0, 'languages' => ['ita' => 'Italian']],
            ['name' => 'United States', 'official_name' => 'United States of America', 'cca2' => 'US', 'cca3' => 'USA', 'flag_emoji' => '🇺🇸', 'capital' => 'Washington, D.C.', 'latitude' => '37.09024', 'longitude' => '-95.71289', 'area_km2' => 9833520, 'population' => 331002651, 'gini' => 41.0, 'languages' => ['eng' => 'English']],
            ['name' => 'Japan', 'official_name' => 'Japan', 'cca2' => 'JP', 'cca3' => 'JPN', 'flag_emoji' => '🇯🇵', 'capital' => 'Tokyo', 'latitude' => '36.204824', 'longitude' => '138.252924', 'area_km2' => 377975, 'population' => 125124000, 'gini' => 32.9, 'languages' => ['jpn' => 'Japanese']],
            ['name' => 'China', 'official_name' => 'People\'s Republic of China', 'cca2' => 'CN', 'cca3' => 'CHN', 'flag_emoji' => '🇨🇳', 'capital' => 'Beijing', 'latitude' => '35.86166', 'longitude' => '104.19540', 'area_km2' => 9596961, 'population' => 1412000000, 'gini' => 38.5, 'languages' => ['zho' => 'Chinese']],
            ['name' => 'India', 'official_name' => 'Republic of India', 'cca2' => 'IN', 'cca3' => 'IND', 'flag_emoji' => '🇮🇳', 'capital' => 'New Delhi', 'latitude' => '20.593684', 'longitude' => '78.96288', 'area_km2' => 3287263, 'population' => 1417173173, 'gini' => 35.7, 'languages' => ['hin' => 'Hindi', 'eng' => 'English']],
        ];

        return $this->faker->randomElement($countries);
    }
}
