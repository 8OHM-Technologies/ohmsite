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
        return Inertia::render('Subscriber/Analytics/SafliiCourts', [
            'filters' => $this->analytics->getFilters(),
        ]);
    }

    public function ccma(): Response
    {
        return Inertia::render('Subscriber/Analytics/CcmaAwards', [
            'filters' => $this->analytics->getFilters(),
        ]);
    }

    public function saflii(): Response
    {
        return Inertia::render('Subscriber/Analytics/SafliiCourts', [
            'filters' => $this->analytics->getFilters(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $type = (string) $request->input('type', '');
        $targetName = trim((string) $request->input('target_name', ''));

        if ($type === 'saflii_courts' || $targetName === 'saflii_courts' || $targetName === 'courts') {
            $filters = [
                'court'      => (string) $request->input('court', 'All'),
                'judge'      => (string) $request->input('judge', 'All'),
                'year'       => (string) $request->input('year', 'All'),
                'reportable' => (string) $request->input('reportable', 'All'),
                'search'     => (string) $request->input('search', ''),
            ];

            return response()->json($this->analytics->getSafliiCourtsPayload($filters));
        }

        if ($targetName === 'sabinet_ccma' || $type === 'ccma' || (!$targetName && !$type)) {
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
