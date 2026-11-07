<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountryDatabaseSeeder::class,
            AcmeDataSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Escuela User',
            'email' => 'user@escuela.it',
            'password' => 'password',
        ]);

        User::factory()->create([
            'name' => 'Ana García',
            'email' => 'ana@escuela.it',
            'password' => 'password',
        ]);

        User::factory()->create([
            'name' => 'Carlos Pérez',
            'email' => 'carlos@escuela.it',
            'password' => 'password',
        ]);

        $this->call(TripSeeder::class);
    }
}
