<?php

namespace App\Mcp\Prompts;

use App\Mcp\Tools\CountryListTool;
use App\Models\Country;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('status-pais')]
#[Title('Status de un país prompt')]
#[Description('Genera una ficha ejecutiva con el estado, sedes, clientes y facturación de ACME en un país.')]
class CountryStatus extends Prompt
{
    /**
     * Handle the prompt request.
     *
     * @return array<int, Response>
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'pais' => ['required', 'string', 'max:100'],
        ], [
            'pais.*' => 'Debes indicar el nombre o el código del país a consultar, con un máximo de 100 caracteres (ej. España, Chile, FR).',
        ]);

        $countryName = trim($validated['pais']);
        $country = $this->findCountry($countryName);

        $countryData = $country !== null
            ? $this->describeCountry($country)
            : $this->describeCountryLookup($countryName);

        $instructions = <<<'INSTRUCTIONS'
        Actúa como analista de negocio de ACME LTDA.

        Instrucciones para redactar la respuesta:
        1. Evalúa el peso del mercado en función de sus clientes e ingresos.
        2. Si las sedes físicas son 0, comenta si sería recomendable iniciar operaciones.
        3. Aporta una recomendación estratégica breve para este país.
        4. Si hay operaciones, calcula la facturación media por cliente y por sede para justificar tu análisis.
        5. Basa las cifras únicamente en los datos de ACME disponibles. No inventes ninguna y, si falta alguna, indícalo.
        6. Redacta en español, con títulos breves y un máximo de 300 palabras.
        INSTRUCTIONS;

        $userMessage = <<<MESSAGE
        Redacta una ficha ejecutiva sobre el estado de ACME LTDA en "{$countryName}".

        {$countryData}
        MESSAGE;

        return [
            Response::text($instructions)->asAssistant(),
            Response::text($userMessage),
        ];
    }

    /**
     * Find a country by its exact common name, official name or ISO code, ignoring the letter case.
     */
    protected function findCountry(string $search): ?Country
    {
        return Country::query()
            ->with(['continent', 'currencies'])
            ->whereRaw('LOWER(name) = LOWER(?)', [$search])
            ->orWhereRaw('LOWER(official_name) = LOWER(?)', [$search])
            ->orWhereRaw('LOWER(cca2) = LOWER(?)', [$search])
            ->orWhereRaw('LOWER(cca3) = LOWER(?)', [$search])
            ->first();
    }

    /**
     * Describe the public and ACME data of a country to be used as the prompt context.
     */
    protected function describeCountry(Country $country): string
    {
        $volumeShare = $this->percentage($country->acme_volume_usd, Country::query()->sum('acme_volume_usd'));
        $customersShare = $this->percentage($country->acme_customers, Country::query()->sum('acme_customers'));

        $status = $country->acme_branches > 0 ? 'Con operaciones' : 'Sin operaciones';
        $languages = implode(', ', $country->languages ?? []) ?: 'No disponible';
        $currencies = $country->currencies->pluck('code')->implode(', ') ?: 'No disponible';
        $continent = $country->continent?->name ?? 'No disponible';
        $capital = $country->capital ?? 'No disponible';
        $population = number_format($country->population, 0, ',', '.');
        $area = number_format($country->area_km2, 0, ',', '.');
        $volume = number_format($country->acme_volume_usd, 0, ',', '.');
        $customers = number_format($country->acme_customers, 0, ',', '.');

        return <<<DATA
        Datos del país:
        - País: {$country->flag_emoji} {$country->name} ({$country->cca2})
        - Continente: {$continent}
        - Capital: {$capital}
        - Población: {$population} habitantes
        - Superficie: {$area} km²
        - Idiomas: {$languages}
        - Monedas: {$currencies}

        Datos internos de ACME LTDA:
        - Estado: {$status}
        - Sedes físicas: {$country->acme_branches}
        - Clientes: {$customers} ({$customersShare}% del total mundial de ACME)
        - Facturación anual: {$volume} USD ({$volumeShare}% del total mundial de ACME)
        DATA;
    }

    /**
     * Describe how to get the data of a country whose name could not be resolved, as names are stored in English.
     */
    protected function describeCountryLookup(string $countryName): string
    {
        $toolName = (new CountryListTool)->name();

        return <<<DATA
        Los datos de este país no se han podido obtener directamente. Consúltalos con la herramienta "{$toolName}", usando en el filtro "name" el nombre común del país en inglés (ej. "Spain" para "España").
        Si la herramienta no devuelve ningún país que corresponda a "{$countryName}", indica que no existe información sobre él y no redactes la ficha.
        DATA;
    }

    /**
     * Calculate the percentage that a value represents of a total, rounded to two decimals.
     */
    protected function percentage(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round($value / $total * 100, 2);
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'pais',
                description: 'Nombre o código del país a consultar (ej. España, Chile, FR)',
                required: true,
            ),
        ];
    }
}
