<?php

namespace Database\Factories;

use App\Models\Analytics;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Analytics>
 */
class AnalyticsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hearingStart = fake()->dateTimeBetween('2026-01-01', '2026-06-30');
        $hearingEnd = fake()->dateTimeBetween($hearingStart, (clone $hearingStart)->modify('+5 days'));
        $awardDate = fake()->dateTimeBetween($hearingEnd, (clone $hearingEnd)->modify('+14 days'));

        $provinces = [
            'Gauteng [Johannesburg]',
            'Gauteng [Pretoria]',
            'KwaZulu-Natal [Durban]',
            'Western Cape [Cape Town]',
            'North West [Rustenburg]',
            'Mpumalanga [Nelspruit]',
            'Eastern Cape [Port Elizabeth]',
            'Free State [Bloemfontein]',
            'Limpopo [Polokwane]',
        ];

        $employers = [
            'Woolworths (Pty) Ltd',
            'Pick n Pay Stores Limited',
            'Bidvest Protea Coin (Pty) Ltd',
            'Transnet SOC Ltd',
            'Anglo American Platinum',
            'MTN',
            'Standard Bank',
            'Shoprite Holdings Ltd',
            'Mediclinic Private Hospitals',
            'Vodacom (Pty) Ltd',
        ];

        $reasons = [
            'MISCONDUCT',
            'INCAPACITY',
            'UNFAIR LABOUR PRACTICE',
            'OPERATIONAL REQUIREMENTS',
            'CONSTRUCTIVE DISMISSAL',
            'MATTERS OF MUTUAL INTEREST',
            'UNFAIR DISMISSAL',
        ];

        $employer = fake()->randomElement($employers);
        $employee = '[REDACTED]';
        $courtLocation = fake()->randomElement($provinces);
        $reason = fake()->randomElement($reasons);
        $awardNumber = strtoupper(fake()->lexify('??')).fake()->numberBetween(1, 500);

        return [
            'title' => "{$employee} v {$employer}, {$awardNumber}",
            'document_type' => 'CCMA Bargaining Council Awards',
            'award_date' => $awardDate,
            'court' => 'CCMA',
            'award_number' => $awardNumber,
            'hearing_start' => $hearingStart,
            'hearing_end' => $hearingEnd,
            'date_modified' => fake()->dateTimeBetween($awardDate, (clone $awardDate)->modify('+30 days')),
            'detail_url' => fake()->url(),
            'detail_title' => "{$employee} v {$employer}, {$awardNumber}",
            'employee' => $employee,
            'employer' => $employer,
            'forum' => 'CCMA',
            'court_location' => $courtLocation,
            'reason_for_dismissal' => $reason,
            'preview_image_url' => null,
            'details_scraped_at' => fake()->dateTimeBetween($awardDate, (clone $awardDate)->modify('+15 days')),
        ];
    }
}
