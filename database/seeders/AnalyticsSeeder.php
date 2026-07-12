<?php

namespace Database\Seeders;

use App\Models\Analytics;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AnalyticsSeeder extends Seeder
{
    /**
     * Seed the analytics table from the demo JSON data file.
     */
    public function run(): void
    {
        $jsonPath = resource_path('js/Pages/Demo/Analytics/demo_data.json');

        if (! File::exists($jsonPath)) {
            $this->command->warn('demo_data.json not found — skipping analytics seed.');

            return;
        }

        $cases = json_decode(File::get($jsonPath), true) ?? [];

        foreach ($cases as $case) {
            Analytics::create([
                'title' => $case['title'],
                'document_type' => $case['document_type'],
                'award_date' => $case['award_date'],
                'court' => $case['court'],
                'award_number' => $case['award_number'],
                'hearing_start' => $case['hearing_start'] ?? null,
                'hearing_end' => $case['hearing_end'] ?? null,
                'date_modified' => $case['date_modified'] ?? null,
                'detail_url' => $case['detail_url'] ?? null,
                'detail_title' => $case['detail_title'] ?? null,
                'employee' => $case['employee'],
                'employer' => $case['employer'],
                'forum' => $case['forum'] ?? null,
                'court_location' => $case['court_location'],
                'reason_for_dismissal' => $case['reason_for_dismissal'],
                'preview_image_url' => $case['preview_image_url'] ?? null,
                'details_scraped_at' => $case['details_scraped_at'] ?? null,
            ]);
        }

        $this->command->info('Seeded '.count($cases).' analytics records.');
    }
}
