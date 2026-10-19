<?php

use App\Mcp\Servers\CountryInfoServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/country-info', CountryInfoServer::class);
