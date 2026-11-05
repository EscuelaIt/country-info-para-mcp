<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    /**
     * Show the user's panel with their MCP bearer token, if any.
     */
    public function index(Request $request): View
    {
        return view('panel', [
            'mcpToken' => $request->user()->mcpToken(),
            'plainTextToken' => session('plainTextToken'),
        ]);
    }
}
