<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class DemoAnalyticsService
{
    /**
     * Get the full payload for the admin dashboard.
     */
    public function getDashboardPayload(): array
    {
        $dataset = \App\Models\Dataset::where('slug', 'ccma')->first();
        $cases = $dataset ? ($dataset->demo_data ?? []) : [];

        return [
            'cases' => $cases,
        ];
    }
}
