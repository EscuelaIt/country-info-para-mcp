<?php

use App\Mcp\Servers\CountryInfoServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/country-info', CountryInfoServer::class);

// Bearer 1|702RsIfdvXwZAXHbE29g5AHmfFSJGek6Ds6GrOsNfe0b8a85
/*

npx @modelcontextprotocol/inspector \
  --transport http \
  --server-url http://localhost:8000/mcp/country-info \
  --header "Authorization: Bearer 1|JZhNE0lB44Cng7QvRbBFwAwPKAAbf06Q7FixQN6B0054720f"

*/
