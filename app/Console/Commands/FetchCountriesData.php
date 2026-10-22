<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:fetch-countries-data')]
#[Description('Descarga todos los países desde REST Countries API v5 con paginación y los cachea en JSON local')]
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
            $countries = $this->fetchAllCountries($apiKey);

            if (empty($countries)) {
                $this->error('❌ No se obtuvieron países de la API');

                return self::FAILURE;
            }

            // Guardar en archivo JSON
            $data = ['data' => ['objects' => $countries]];
            $cacheFile = database_path('seeders/countries_api_v5.json');
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($cacheFile, $json);

            $this->info("✓ Datos cacheados en: {$cacheFile}");
            $this->info('✓ Total de países descargados: '.count($countries));
            $this->newLine();
            $this->info('💡 Ahora puedes ejecutar: <fg=cyan>php artisan db:seed --class=CountryDatabaseSeeder</>');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function fetchAllCountries(string $apiKey): array
    {
        $allCountries = [];
        $limit = 100;
        $offset = 0;
        $maxAttempts = 100;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $url = "https://api.restcountries.com/countries/v5?limit={$limit}&offset={$offset}";

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                ])->timeout(120)->connectTimeout(120)->retry(3, 1000)->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['data']['objects']) && is_array($data['data']['objects'])) {
                        $batchSize = count($data['data']['objects']);
                        $allCountries = array_merge($allCountries, $data['data']['objects']);

                        $this->line("  📍 Descargados países: {$offset} - ".($offset + $batchSize));

                        // Si la respuesta tiene menos países que el límite, hemos llegado al final
                        if ($batchSize < $limit) {
                            $this->info('  ✓ Descarga completada');

                            break;
                        }

                        $offset += $limit;
                    } else {
                        $this->warn('⚠ Respuesta del API sin estructura esperada');

                        break;
                    }
                } else {
                    $this->warn("⚠ API respondió con status {$response->status()}");

                    break;
                }
            } catch (\Exception $e) {
                $this->warn('⚠ Error descargando lote: '.$e->getMessage());

                break;
            }

            $attempt++;
        }

        return $allCountries;
    }
}
