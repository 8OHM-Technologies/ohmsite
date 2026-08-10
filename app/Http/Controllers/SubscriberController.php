<?php

namespace App\Http\Controllers;

use App\Services\SubscriberAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriberController extends Controller
{
    public function __construct(
        protected SubscriberAnalyticsService $analytics
    ) {}

    public function index(): Response
    {
        return Inertia::render('Subscriber/Analytics/Index', [
            'filters' => $this->analytics->getFilters(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $targetName = trim((string) $request->input('target_name', 'sabinet_ccma'));

        if ($targetName === 'sabinet_ccma') {
            $filters = [
                'province' => (string) $request->input('province', 'All'),
                'category' => (string) $request->input('category', 'All'),
                'month'    => (string) $request->input('month', 'All'),
                'employer' => (string) $request->input('employer', 'All'),
            ];

            return response()->json($this->analytics->getCcmaPayload($filters));
        }

        return response()->json($this->analytics->getLegalPayload($targetName));
    }
}
