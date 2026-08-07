<?php

namespace App\Http\Controllers;

use App\Services\SubscriberAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class SubscriberController extends Controller
{
    public function __construct(
        protected SubscriberAnalyticsService $analytics
    ) {}

    public function index(): Response
    {
        return Inertia::render(
            'Subscriber/Analytics/Index',
            $this->analytics->getDashboardPayload()
        );
    }
}
