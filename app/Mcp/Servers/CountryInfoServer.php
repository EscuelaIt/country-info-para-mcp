<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CountryListTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Country Info Server')]
#[Version('0.0.1')]
#[Instructions('This server provides comprehensive information about countries around the world, including diverse data about each country such as geography, population, economy, culture, and other relevant information.')]
class CountryInfoServer extends Server
{
    protected array $tools = [
        CountryListTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
