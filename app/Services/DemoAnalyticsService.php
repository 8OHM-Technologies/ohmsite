<?php

namespace App\Services;

use App\Models\Dataset;

class DemoAnalyticsService
{
    /**
     * Get the full payload for the admin dashboard.
     */
    public function getDashboardPayload(): array
    {
        $dataset = Dataset::where('slug', 'ccma')->first();
        $cases = $dataset ? ($dataset->demo_data ?? []) : [];

        return [
            'cases' => $cases,
        ];
    }
}
