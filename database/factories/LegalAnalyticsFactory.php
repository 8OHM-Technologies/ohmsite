<?php

namespace Database\Factories;

use App\Models\LegalAnalytics;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalAnalytics>
 */
class LegalAnalyticsFactory extends Factory
{
    protected $model = LegalAnalytics::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'extracted_record_id' => fake()->uuid(),
            'target_type' => 'cases',
            'target_name' => 'saflii_courts',
            'title' => fake()->sentence(),
            'document_type' => 'saflii_courts',
            'document_date' => fake()->date(),
            'court' => fake()->company(),
            'case_number' => fake()->bothify('CCT ##/##'),
            'source_url' => fake()->url(),
            'data' => [
                'applicant_plaintiff' => fake()->name(),
                'respondent_defendant' => fake()->name(),
                'subjects' => [fake()->word(), fake()->word()],
                'result' => fake()->sentence(),
            ],
        ];
    }
}
