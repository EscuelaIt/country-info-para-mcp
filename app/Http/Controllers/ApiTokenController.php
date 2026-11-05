<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    /**
     * Issue a new MCP bearer token and flash its plain text value so it can be shown once.
     */
    public function store(Request $request): RedirectResponse
    {
        $newAccessToken = $request->user()->issueMcpToken();

        return to_route('panel')->with('plainTextToken', $newAccessToken->plainTextToken);
    }

    /**
     * Revoke the user's MCP bearer token.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->revokeMcpToken();

        return to_route('panel')->with('status', 'Token deleted.');
    }
}
