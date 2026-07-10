<?php

namespace App\Http\Controllers;

use App\Services\DemoAnalyticsService;
use Inertia\Inertia;

class DemoController extends Controller
{
    protected DemoAnalyticsService $analytics;

    public function __construct(DemoAnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    public function index()
    {
        return Inertia::render('Demo/Analytics/Index', $this->analytics->getDashboardPayload());
    }
}
