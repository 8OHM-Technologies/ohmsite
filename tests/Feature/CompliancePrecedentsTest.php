<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompliancePrecedentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        if (! Schema::connection('pgsql_coeus')->hasTable('extracted_records')) {
            Schema::connection('pgsql_coeus')->create('extracted_records', function ($table) {
                $table->uuid('id')->primary();
                $table->string('record_type')->nullable();
                $table->string('source_url')->nullable();
                $table->date('document_date')->nullable();
                $table->json('data')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('scrubbed_at')->nullable();
                $table->boolean('requires_human_review')->default(false);
                $table->text('review_reason')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('pgsql_coeus')->hasTable('scrubbed_records')) {
            Schema::connection('pgsql_coeus')->create('scrubbed_records', function ($table) {
                $table->uuid('id')->primary();
                $table->uuid('extracted_record_id')->nullable();
                $table->json('data')->nullable();
                $table->timestamps();
            });
        }

        $existingTarget = DB::connection('pgsql_coeus')->table('targets')->first();
        if (! $existingTarget) {
            $this->targetId = (string) Str::uuid();
            DB::connection('pgsql_coeus')->table('targets')->insert([
                'id' => $this->targetId,
                'name' => 'fsca',
                'created_at' => now(),
            ]);
        } else {
            $this->targetId = $existingTarget->id;
        }
    }

    private string $targetId;

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

    public function test_compliance_record_detail_endpoint_returns_specialized_regulatory_dossier(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $extractedId = (string) Str::uuid();
        $scrubbedId = (string) Str::uuid();

        $sourceUrl = 'https://fsca.co.za/actions/'.(string) Str::uuid();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $extractedId,
            'target_id' => $this->targetId,
            'record_type' => 'fsca_enforcement_records',
            'document_date' => '2024-06-01',
            'source_url' => $sourceUrl,
            'data' => json_encode([
                'title' => 'Administrative Sanction - Alpha Capital',
                'applicant' => 'FSCA',
                'respondent' => 'Alpha Capital',
                'action_type' => 'Administrative Penalty',
                'penalty_amount' => 750000,
                'sanctions' => ['Administrative Penalty of R750 000'],
                'contraventions' => ['Section 167 of FSR Act'],
            ]),
            'status' => 'detailed',
            'scraped_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => $scrubbedId,
            'extracted_record_id' => $extractedId,
            'data' => json_encode([
                'title' => 'Administrative Sanction - Alpha Capital',
                'regulator' => 'FSCA',
                'applicant' => 'FSCA',
                'respondent' => 'Alpha Capital',
                'action_type' => 'Administrative Penalty',
                'penalty_amount' => 750000,
                'sanctions' => ['Administrative Penalty of R750 000'],
                'contraventions' => ['Section 167 of FSR Act'],
                'key_provisions' => ['Section 167'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/record/{$scrubbedId}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.category', 'regulatory');
        $response->assertJsonPath('data.regulator', 'FSCA');
        $response->assertJsonPath('data.respondent', 'Alpha Capital');
        $this->assertEquals(750000, $response->json('data.penalty_amount'));
        $this->assertEquals(['Section 167 of FSR Act'], $response->json('data.contraventions'));
    }

    public function test_unscrubbed_tribunal_record_detail_endpoint_resolves_from_extracted_records(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $extractedId = (string) Str::uuid();
        $sourceUrl = 'https://fsca.co.za/fst/decision-'.(string) Str::uuid();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $extractedId,
            'target_id' => $this->targetId,
            'record_type' => 'fst_decisions',
            'document_date' => '2024-07-15',
            'source_url' => $sourceUrl,
            'data' => json_encode([
                'title' => 'Beta Brokerage v FSCA',
                'applicant' => 'Beta Brokerage',
                'respondent' => 'FSCA',
                'division' => 'Financial Services Tribunal',
                'action_type' => 'Tribunal Reconsideration',
                'sanction_outcome' => 'Decision Set Aside',
            ]),
            'status' => 'detailed',
            'scraped_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/record/{$extractedId}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.category', 'tribunal');
        $response->assertJsonPath('data.regulator', 'Financial Services Tribunal');
        $response->assertJsonPath('data.applicant', 'Beta Brokerage');
    }
}
