<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\ContinentDetails;
use App\Mcp\Resources\ContinentsInfo;
use App\Mcp\Tools\CountryListTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Country Info & ACME Business Data Server')]
#[Version('1.0.0')]
#[Instructions('This server provides comprehensive information about countries worldwide combined with internal business data from ACME LTDA. It includes: (1) Public country data: geography, population, capital, continents, coordinates, area, languages, currencies, and borders. (2) ACME LTDA internal data: operational branches, business volume in USD, and customer counts for each country where ACME operates. ACME operates in the top 100 countries by population with realistic distribution patterns. Note: ACME data is proprietary and only available through this server - LLM models cannot access it directly.')]
class CountryInfoServer extends Server
{
    protected array $tools = [
        CountryListTool::class,
    ];

    protected array $resources = [
        ContinentsInfo::class,
        ContinentDetails::class,
    ];

    protected array $prompts = [
        //
    ];
}
