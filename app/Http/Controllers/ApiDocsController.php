<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiDocsController extends Controller
{
    /**
     * Display the developer documentation and keys list.
     */
    public function index(): Response
    {
        if (! auth()->user()->hasApiAccess()) {
            abort(403, 'Unauthorized access to Developer API documentation.');
        }

        $apiKeys = auth()->user()->apiKeys()->latest()->get();

        return Inertia::render('Developer/Docs', [
            'apiKeys' => $apiKeys,
        ]);
    }

    /**
     * Create a new API key.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->hasApiAccess()) {
            abort(403, 'Unauthorized action.');
        }

        $token = 'ohm_live_'.bin2hex(random_bytes(24));

        auth()->user()->apiKeys()->create([
            'key' => $token,
            'name' => $request->input('name') ?? 'Default Key',
        ]);

        return back()->with('success', 'API Key generated successfully.');
    }

    /**
     * Delete an API key.
     */
    public function destroy(ApiKey $apiKey)
    {
        if ($apiKey->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $apiKey->delete();

        return back()->with('success', 'API Key deleted successfully.');
    }
}
