<?php

namespace App\Services;

use App\Models\Analytics;

class SubscriberAnalyticsService
{
    /**
     * Get the full payload for the subscriber analytics dashboard.
     *
     * @return array{cases: array<int, array<string, mixed>>}
     */
    public function getDashboardPayload(): array
    {
        $cases = Analytics::all()->toArray();

        return [
            'cases' => $cases,
        ];
    }
}
