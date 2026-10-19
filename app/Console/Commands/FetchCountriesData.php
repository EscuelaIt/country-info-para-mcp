<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:fetch-countries-data')]
#[Description('Descarga datos de países desde REST Countries API v5 y los cachea en JSON local')]
class FetchCountriesData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Descargando datos de países desde REST Countries API v5...');

        $apiKey = config('services.rest_countries.api_key') ?? env('REST_COUNTRIES_API_KEY');

        if (! $apiKey) {
            $this->error('❌ No hay API key configurada en REST_COUNTRIES_API_KEY');

            return self::FAILURE;
        }

        try {
            $this->info('Conectando con la API...');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->timeout(120)->connectTimeout(120)->retry(3, 1000)->get('https://api.restcountries.com/countries/v5?limit=100');

            if (! $response->successful()) {
                $this->error('❌ Error en la respuesta de la API: '.$response->status());

                return self::FAILURE;
            }

            $data = $response->json();

            if (! isset($data['data']['objects'])) {
                $this->error('❌ Estructura de respuesta inesperada');

                return self::FAILURE;
            }

            $countries = $data['data']['objects'];
            $this->info("✓ Descargados {$response->header('x-ratelimit-remaining')} de {$response->header('x-ratelimit-limit')} países");

            // Guardar en archivo JSON
            $cacheFile = database_path('seeders/countries_api_v5.json');
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($cacheFile, $json);

            $this->info("✓ Datos cacheados en: {$cacheFile}");
            $this->info('✓ Total de países: '.count($countries));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
