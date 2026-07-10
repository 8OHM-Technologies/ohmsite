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
        $jsonPath = resource_path('js/Pages/Demo/Analytics/demo_data.json');
        $cases = [];

        if (File::exists($jsonPath)) {
            $cases = json_decode(File::get($jsonPath), true) ?? [];
        }

        return [
            'cases' => $cases,
        ];
    }
}
