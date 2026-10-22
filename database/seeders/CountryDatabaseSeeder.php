<?php

namespace Database\Seeders;

use App\Models\Continent;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CountryDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Carga de datos (priorizar JSON cache, luego API, luego fallback PHP)
        $this->command->info('Cargando datos de países...');

        $rawCountries = null;

        // Primero: intentar cargar desde cache JSON local
        $this->command->info('Buscando cache local...');
        $countriesFile = __DIR__.'/countries_api_v5.json';
        if (file_exists($countriesFile)) {
            $json = json_decode(file_get_contents($countriesFile), true);
            if (isset($json['data']['objects']) && is_array($json['data']['objects'])) {
                $rawCountries = $json['data']['objects'];
                $this->command->info('✓ Cache local encontrado ('.count($rawCountries).' países)');
                $this->command->line('  💡 Ejecuta <fg=cyan>php artisan app:fetch-countries-data</> para actualizar desde la API');
            }
        }

        // Segundo: si no hay cache, intentar descargar desde la API
        if (empty($rawCountries)) {
            $apiKey = config('services.rest_countries.api_key') ?? env('REST_COUNTRIES_API_KEY');

            if ($apiKey) {
                $this->command->info('Cache no encontrado, descargando desde API...');
                try {
                    $rawCountries = $this->fetchAllCountriesFromApi($apiKey);

                    if (! empty($rawCountries)) {
                        $this->command->info('✓ Datos descargados desde la API REST Countries ('.count($rawCountries).' países)');

                        // Cachear para futuras ejecuciones
                        $data = ['data' => ['objects' => $rawCountries]];
                        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        file_put_contents($countriesFile, $json);
                        $this->command->info('✓ Cache guardado para futuras ejecuciones');
                    }
                } catch (\Exception $e) {
                    $this->command->warn('⚠ No se pudo conectar a la API: '.$e->getMessage());
                }
            }
        }

        // Tercero: fallback a datos PHP mínimos
        if (empty($rawCountries)) {
            $this->command->info('Usando datos mínimos de fallback...');
            $countriesFile = __DIR__.'/countries_data.php';
            if (file_exists($countriesFile)) {
                $rawCountries = include $countriesFile;
                $this->command->info('✓ Datos cargados desde archivo PHP ('.count($rawCountries).' países)');
            }
        }

        if (empty($rawCountries)) {
            $this->command->error('✗ No hay datos de países disponibles.');

            return;
        }

        $this->command->info('Procesando '.count($rawCountries).' países...');

        // 2. Pre-cargar/crear los Continentes para evitar duplicados
        $continentsMap = collect([
            'Africa' => ['code' => 'AF', 'name' => 'África'],
            'Americas' => ['code' => 'AM', 'name' => 'América'],
            'Antarctica' => ['code' => 'AN', 'name' => 'Antártida'],
            'Asia' => ['code' => 'AS', 'name' => 'Asia'],
            'Europe' => ['code' => 'EU', 'name' => 'Europa'],
            'Oceania' => ['code' => 'OC', 'name' => 'Oceanía'],
        ])->mapWithKeys(function ($data, $region) {
            $continent = Continent::firstOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name']]
            );

            return [$region => $continent->id];
        });

        // Estructura temporal para almacenar las fronteras pendientes de enlazar
        $pendingBorders = [];
        $countProcessed = 0;

        DB::transaction(function () use ($rawCountries, $continentsMap, &$pendingBorders, &$countProcessed) {

            // FASE 1: Países, Monedas y Relación País-Moneda
            foreach ($rawCountries as $raw) {
                try {
                    // Extraer información base
                    $cca2 = $raw['codes']['alpha_2'] ?? null;
                    $cca3 = $raw['codes']['alpha_3'] ?? null;

                    if (! $cca2 || ! $cca3) {
                        continue;
                    }

                    // Nombres
                    $name = $raw['names']['common'] ?? null;
                    $officialName = $raw['names']['official'] ?? null;

                    if (! $name) {
                        continue;
                    }

                    // Región
                    $region = $raw['region'] ?? 'Europe';
                    $continentId = $continentsMap[$region] ?? $continentsMap['Europe'];

                    // Capital (es un array de objetos)
                    $capital = null;
                    if (! empty($raw['capitals']) && is_array($raw['capitals'])) {
                        $firstCapital = $raw['capitals'][0];
                        $capital = is_array($firstCapital) ? ($firstCapital['name'] ?? null) : $firstCapital;
                    }

                    // Coordenadas
                    $latitude = null;
                    $longitude = null;
                    if (! empty($raw['coordinates'])) {
                        $latitude = $raw['coordinates']['lat'] ?? null;
                        $longitude = $raw['coordinates']['lng'] ?? null;
                    }

                    // Área
                    $area = 0;
                    if (! empty($raw['area']) && is_array($raw['area'])) {
                        $area = (int) ($raw['area']['kilometers'] ?? 0);
                    } elseif (! empty($raw['area'])) {
                        $area = (int) $raw['area'];
                    }

                    // Población
                    $population = 0;
                    if (! empty($raw['population'])) {
                        $population = (int) $raw['population'];
                    }

                    // Idiomas - convertir a array asociativo simple
                    $languages = null;
                    if (! empty($raw['languages']) && is_array($raw['languages'])) {
                        $langArray = [];
                        foreach ($raw['languages'] as $lang) {
                            if (is_array($lang)) {
                                $code = $lang['iso639_1'] ?? $lang['iso639_3'] ?? null;
                                if ($code) {
                                    $langArray[$code] = $lang['name'] ?? null;
                                }
                            }
                        }
                        $languages = ! empty($langArray) ? $langArray : null;
                    }

                    // GINI
                    $gini = null;
                    if (! empty($raw['gini']) && is_array($raw['gini'])) {
                        $gini = end($raw['gini']);
                    } elseif (! empty($raw['gini'])) {
                        $gini = (float) $raw['gini'];
                    }

                    // Bandera
                    $flag = $raw['flags']['unicode'] ?? null;

                    // Crear país
                    $savedCountry = Country::create([
                        'continent_id' => $continentId,
                        'name' => $name,
                        'official_name' => $officialName,
                        'cca2' => strtoupper($cca2),
                        'cca3' => strtoupper($cca3),
                        'flag_emoji' => $flag,
                        'capital' => $capital,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'area_km2' => $area,
                        'population' => $population,
                        'gini' => $gini,
                        'languages' => $languages,
                        'summary' => "Ficha de $name, situado en $region.",
                    ]);
                    $countProcessed++;

                    // Procesar Monedas (N:M)
                    if (! empty($raw['currencies']) && is_array($raw['currencies'])) {
                        $currencyIds = [];
                        foreach ($raw['currencies'] as $curr) {
                            if (is_array($curr)) {
                                $code = $curr['iso4217_code'] ?? $curr['code'] ?? null;
                                $currName = $curr['name'] ?? null;
                                if ($code && $currName) {
                                    $currency = Currency::firstOrCreate(
                                        ['code' => strtoupper($code)],
                                        ['name' => $currName]
                                    );
                                    $currencyIds[] = $currency->id;
                                }
                            }
                        }
                        if (! empty($currencyIds)) {
                            $savedCountry->currencies()->sync($currencyIds);
                        }
                    }

                    // Guardar fronteras para la segunda fase
                    if (! empty($raw['borders']) && is_array($raw['borders'])) {
                        $pendingBorders[$cca3] = $raw['borders'];
                    }
                } catch (\Exception $e) {
                    $this->command->warn('Error procesando: '.$e->getMessage());

                    continue;
                }
            }

            $this->command->info("✓ Países creados: $countProcessed");

            // FASE 2: Relación Reflexiva de Fronteras (N:M)
            $this->command->info('Conectando fronteras terrestres...');

            $countryIdsByCode = Country::pluck('id', 'cca3')->toArray();

            $borderInserts = [];
            foreach ($pendingBorders as $cca3 => $neighbors) {
                $countryId = $countryIdsByCode[$cca3] ?? null;
                if (! $countryId) {
                    continue;
                }

                foreach ($neighbors as $neighborCca3) {
                    $neighborId = $countryIdsByCode[$neighborCca3] ?? null;
                    if ($neighborId) {
                        $borderInserts[] = [
                            'country_id' => $countryId,
                            'neighbor_id' => $neighborId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            foreach (array_chunk($borderInserts, 500) as $chunk) {
                DB::table('country_borders')->insertOrIgnore($chunk);
            }

            $this->command->info('✓ Fronteras conectadas: '.count($borderInserts));
        });

        $this->command->info('✓ Base de datos poblada exitosamente.');
    }

    private function fetchAllCountriesFromApi(string $apiKey): array
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

                        $this->command->line("  📍 Descargados países: {$offset} - ".($offset + $batchSize));

                        // Si la respuesta tiene menos países que el límite, hemos llegado al final
                        if ($batchSize < $limit) {
                            $this->command->info('  ✓ Descarga completada');

                            break;
                        }

                        $offset += $limit;
                    } else {
                        $this->command->warn('⚠ Respuesta del API sin estructura esperada');

                        break;
                    }
                } else {
                    $this->command->warn("⚠ API respondió con status {$response->status()}");

                    break;
                }
            } catch (\Exception $e) {
                $this->command->warn('⚠ Error descargando lote: '.$e->getMessage());

                break;
            }

            $attempt++;
        }

        return $allCountries;
    }
}
