<?php

namespace App\Services;

use App\Models\Dataset;

class DemoAnalyticsService
{
    /**
     * Get the full payload for the demo analytics dashboard.
     *
     * @return array<string, mixed>
     */
    public function getDashboardPayload(): array
    {
        $dataset = Dataset::whereIn('slug', ['high-court', 'labour-court', 'case-law', 'saflii', 'ccma'])
            ->whereNotNull('demo_data')
            ->first() ?? Dataset::whereNotNull('demo_data')->first();

        $cases = $dataset ? ($dataset->demo_data ?? []) : [];

        if (empty($cases)) {
            $demoDataPath = resource_path('js/Pages/Demo/Analytics/demo_data.json');
            if (file_exists($demoDataPath)) {
                $cases = json_decode(file_get_contents($demoDataPath), true) ?: [];
            }
        }

        return [
            'cases' => $cases,
        ];
    }
}

