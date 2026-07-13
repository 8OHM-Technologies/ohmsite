<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ApiDocsController extends Controller
{
    public function index(): Response
    {
        if (! auth()->user()->hasApiSubscriptionAccess()) {
            abort(403, 'Unauthorized access to Developer API documentation.');
        }

        return Inertia::render('Developer/Docs');
    }
}
