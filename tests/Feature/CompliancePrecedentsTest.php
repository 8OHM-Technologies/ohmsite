<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompliancePrecedentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_unauthenticated_user_cannot_access_precedents(): void
    {
        $response = $this->get('/legal-records/precedents');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_precedents_view(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/legal-records/precedents');
        $response->assertStatus(200);
    }

    public function test_precedents_data_endpoint_returns_json(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Http::fake([
            '*/api/v1/compliance/precedents*' => Http::response([
                'total' => 1,
                'records' => [
                    [
                        'id' => 'test-uuid-1',
                        'record_type' => 'fsca_enforcement_records',
                        'regulator' => 'FSCA',
                        'category' => 'regulatory',
                        'title' => 'Administrative Sanction',
                        'case_number' => 'FSCA-2024-001',
                        'document_date' => '2024-05-15',
                        'respondent' => 'Discovery Life Ltd',
                        'applicant' => 'FSCA',
                        'action_type' => 'Administrative Penalty',
                        'penalty_amount' => 500000,
                        'sanctions' => ['Fine'],
                        'contraventions' => ['Section 167'],
                        'summary' => 'Contravention of Section 167 of the Insurance Act.',
                        'source_url' => 'https://fsca.co.za/test',
                        'key_provisions' => ['Section 167'],
                    ],
                ],
                'regulators' => ['FSCA'],
                'categories' => ['regulatory'],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson('/legal-records/precedents/data?q=Discovery');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('records.0.respondent', 'Discovery Life Ltd');
        $this->assertEquals(500000, $response->json('records.0.penalty_amount'));
    }

    public function test_cross_reference_endpoint_returns_statute_occurrences(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Http::fake([
            '*/api/v1/compliance/cross-reference*' => Http::response([
                'statute_section' => 'Section 167',
                'total_occurrences' => 4,
                'breakdown_by_regulator' => [
                    'FSCA' => 3,
                    'Financial Services Tribunal' => 1,
                ],
                'common_contraventions' => [
                    'Failure to submit statutory audit report',
                ],
                'records' => [],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson('/legal-records/precedents/cross-reference?statute_section=Section%20167');

        $response->assertStatus(200);
        $response->assertJsonPath('statute_section', 'Section 167');
        $response->assertJsonPath('total_occurrences', 4);
        $response->assertJsonPath('breakdown_by_regulator.FSCA', 3);
    }

    public function test_entity_profile_endpoint_returns_sanctions_timeline(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Http::fake([
            '*/api/v1/compliance/entity-profile*' => Http::response([
                'entity_name' => 'Sanlam',
                'total_actions' => 2,
                'total_penalties' => 750000,
                'involved_regulators' => ['FSCA', 'FAIS Ombud'],
                'actions_timeline' => [
                    [
                        'id' => 'action-1',
                        'date' => '2023-08-10',
                        'regulator' => 'FSCA',
                        'title' => 'Enforcement Directive',
                        'penalty' => 500000,
                        'summary' => 'Compliance directive issued.',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson('/legal-records/precedents/entity-profile?entity_name=Sanlam');

        $response->assertStatus(200);
        $response->assertJsonPath('entity_name', 'Sanlam');
        $response->assertJsonPath('total_actions', 2);
        $this->assertEquals(750000, $response->json('total_penalties'));
    }

    public function test_unsubscribed_user_is_redirected_from_compliance_analytics(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/subscriber/analytics/compliance');
        $response->assertRedirect(route('subscriptions.index'));
    }

    public function test_subscribed_user_can_access_compliance_analytics(): void
    {
        Cache::flush();

        $adminUser = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Http::fake([
            '*/api/v1/compliance/summary*' => Http::response([
                'total_penalties_amount' => 1500000,
                'total_enforcement_records' => 5,
                'total_popia_notices' => 10,
                'total_prudential_standards' => 20,
                'total_tribunal_decisions' => 8,
                'total_ombud_determinations' => 12,
                'records_by_regulator' => ['FSCA' => 5],
                'penalties_by_year' => ['2024' => 1500000],
                'top_penalties' => [],
                'recent_actions' => [],
            ], 200),
        ]);

        $response = $this->actingAs($adminUser)->get('/subscriber/analytics/compliance');
        $response->assertStatus(200);

        $dataResponse = $this->actingAs($adminUser)->getJson('/subscriber/analytics/compliance/data');
        $dataResponse->assertStatus(200);
        $this->assertEquals(1500000, $dataResponse->json('total_penalties_amount'));
    }
}
