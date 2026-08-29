<?php

namespace Tests\Feature;

use App\Models\TargetVanity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalRecordReviewTest extends TestCase
{
    use RefreshDatabase;

    private string $targetId;

    protected array $createdExtractedIds = [];

    protected array $createdScrubbedIds = [];

    protected array $createdParsedIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::connection('pgsql_coeus')->hasTable('extracted_records')) {
            Schema::connection('pgsql_coeus')->create('extracted_records', function ($table) {
                $table->uuid('id')->primary();
                $table->uuid('target_id')->nullable();
                $table->string('record_type')->nullable();
                $table->string('source_url')->nullable();
                $table->date('document_date')->nullable();
                $table->json('data')->nullable();
                $table->boolean('requires_human_review')->default(false);
                $table->string('review_reason')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('scraped_at')->nullable();
                $table->timestamp('detailed_at')->nullable();
                $table->timestamp('parsed_at')->nullable();
                $table->timestamp('scrubbed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('pgsql_coeus')->hasTable('parsed_records')) {
            Schema::connection('pgsql_coeus')->create('parsed_records', function ($table) {
                $table->uuid('id')->primary();
                $table->uuid('extracted_record_id')->nullable();
                $table->json('data')->nullable();
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

        TargetVanity::create([
            'target_name' => 'sabinet_ccma',
            'vanity_name' => 'CCMA Awards',
            'target_type' => 'cases',
        ]);

        TargetVanity::create([
            'target_name' => 'ZACC',
            'vanity_name' => 'Constitutional Court of South Africa',
            'target_type' => 'cases',
        ]);

        $existingTarget = DB::connection('pgsql_coeus')->table('targets')->first();
        if (! $existingTarget) {
            $this->targetId = (string) Str::uuid();
            DB::connection('pgsql_coeus')->table('targets')->insert([
                'id' => $this->targetId,
                'name' => 'saflii',
                'created_at' => now(),
            ]);
        } else {
            $this->targetId = $existingTarget->id;
        }
    }

    protected function tearDown(): void
    {
        if (! empty($this->createdScrubbedIds)) {
            DB::connection('pgsql_coeus')->table('scrubbed_records')->whereIn('id', $this->createdScrubbedIds)->delete();
        }
        if (! empty($this->createdParsedIds)) {
            DB::connection('pgsql_coeus')->table('parsed_records')->whereIn('id', $this->createdParsedIds)->delete();
        }
        if (! empty($this->createdExtractedIds)) {
            DB::connection('pgsql_coeus')->table('extracted_records')->whereIn('id', $this->createdExtractedIds)->delete();
        }
        parent::tearDown();
    }

    private function createRecordWithStates(bool $requiresReview = false, ?string $reviewReason = null, array $customScrubbed = [], array $customParsed = []): array
    {
        $extId = (string) Str::uuid();
        $parsedId = (string) Str::uuid();
        $scrubbedId = (string) Str::uuid();

        $rand = Str::random(12);
        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $extId,
            'target_id' => $this->targetId,
            'record_type' => 'saflii_courts',
            'source_url' => "https://www.saflii.org/za/cases/ZACC/2026/test_{$rand}.html",
            'document_date' => '2026-03-01',
            'data' => json_encode(['raw_title' => 'State v Smith', 'category' => 'cases']),
            'requires_human_review' => $requiresReview,
            'review_reason' => $reviewReason,
            'status' => 'detailed',
            'scraped_at' => now(),
            'detailed_at' => now(),
            'parsed_at' => now(),
            'scrubbed_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('parsed_records')->insert([
            'id' => $parsedId,
            'extracted_record_id' => $extId,
            'data' => json_encode(array_merge([
                'title' => 'State v Smith',
                'court' => 'Constitutional Court',
                'case_number' => 'CCT 50/26',
            ], $customParsed)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => $scrubbedId,
            'extracted_record_id' => $extId,
            'data' => json_encode(array_merge([
                'title' => 'State v Smith',
                'category' => 'cases',
                'metadata' => [
                    'case_number' => 'CCT 50/26',
                    'document_date' => '2026-03-01',
                ],
                'extracted_data' => [
                    'court' => 'Constitutional Court',
                    'applicant_plaintiff' => 'The State',
                    'respondent_defendant' => 'John Smith',
                    'judgment_date' => '2026-03-01',
                    'ratio_decidendi' => 'Constitutional principles must be upheld.',
                    'precedents_cited' => [
                        ['case_name' => 'S v Makwanyane', 'treatment' => 'Applied'],
                    ],
                ],
            ], $customScrubbed)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdExtractedIds[] = $extId;
        $this->createdParsedIds[] = $parsedId;
        $this->createdScrubbedIds[] = $scrubbedId;

        return [
            'extracted_id' => $extId,
            'parsed_id' => $parsedId,
            'scrubbed_id' => $scrubbedId,
        ];
    }

    public function test_non_admin_cannot_access_human_review_endpoints(): void
    {
        $subscriber = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'subscriber',
        ]);

        $this->actingAs($subscriber)->get('/admin/legal-records/human-review')->assertRedirect(route('home'));
        $this->actingAs($subscriber)->getJson('/admin/legal-records/human-review/data')->assertRedirect(route('home'));
        $this->actingAs($subscriber)->postJson('/admin/legal-records/dummy-id/human-review')->assertRedirect(route('home'));
        $this->actingAs($subscriber)->postJson('/admin/legal-records/batch-human-review', ['ids' => ['dummy']])->assertRedirect(route('home'));
        $this->actingAs($subscriber)->putJson('/admin/legal-records/dummy-id', ['title' => 'New Title'])->assertRedirect(route('home'));
    }

    public function test_admin_can_access_human_review_page(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/legal-records/human-review');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/LegalRecords/HumanReview')
            ->has('filters')
        );
    }

    public function test_human_review_data_endpoint_only_returns_flagged_records(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $flagged = $this->createRecordWithStates(true, 'Scraper parse anomaly');
        $unflagged = $this->createRecordWithStates(false);

        $response = $this->actingAs($admin)->getJson('/admin/legal-records/human-review/data');

        $response->assertStatus(200);
        $records = $response->json('records');

        $returnedExtractedIds = array_column($records, 'extracted_record_id');
        $this->assertContains($flagged['extracted_id'], $returnedExtractedIds);
        $this->assertNotContains($unflagged['extracted_id'], $returnedExtractedIds);
    }

    public function test_admin_can_toggle_human_review_for_single_record(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $rec = $this->createRecordWithStates(false);

        // Toggle ON using scrubbed ID
        $response = $this->actingAs($admin)->postJson("/admin/legal-records/{$rec['scrubbed_id']}/human-review", [
            'requires_human_review' => true,
            'review_reason' => 'Judges list contains OCR artifact',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('requires_human_review', true);
        $response->assertJsonPath('review_reason', 'Judges list contains OCR artifact');

        $this->assertDatabaseHas('extracted_records', [
            'id' => $rec['extracted_id'],
            'requires_human_review' => true,
            'review_reason' => 'Judges list contains OCR artifact',
        ], 'pgsql_coeus');

        // Toggle OFF (Resolve)
        $toggleOffResponse = $this->actingAs($admin)->postJson("/admin/legal-records/{$rec['extracted_id']}/human-review", [
            'requires_human_review' => false,
        ]);

        $toggleOffResponse->assertStatus(200);
        $toggleOffResponse->assertJsonPath('requires_human_review', false);

        $this->assertDatabaseHas('extracted_records', [
            'id' => $rec['extracted_id'],
            'requires_human_review' => false,
        ], 'pgsql_coeus');
    }

    public function test_admin_can_batch_mark_records_for_human_review(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $rec1 = $this->createRecordWithStates(false);
        $rec2 = $this->createRecordWithStates(false);

        $response = $this->actingAs($admin)->postJson('/admin/legal-records/batch-human-review', [
            'ids' => [$rec1['scrubbed_id'], $rec2['extracted_id']],
            'requires_human_review' => true,
            'review_reason' => 'Batch flagged from cases table',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('affected_count', 2);

        $this->assertDatabaseHas('extracted_records', [
            'id' => $rec1['extracted_id'],
            'requires_human_review' => true,
        ], 'pgsql_coeus');

        $this->assertDatabaseHas('extracted_records', [
            'id' => $rec2['extracted_id'],
            'requires_human_review' => true,
        ], 'pgsql_coeus');
    }

    public function test_admin_can_retrieve_3_state_record_payload(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $rec = $this->createRecordWithStates(true, 'Needs inspection');

        $response = $this->actingAs($admin)->getJson("/admin/legal-records/{$rec['scrubbed_id']}/states");

        $response->assertStatus(200);
        $response->assertJsonPath('extracted_record_id', $rec['extracted_id']);
        $response->assertJsonPath('scrubbed_record_id', $rec['scrubbed_id']);
        $response->assertJsonPath('parsed_record_id', $rec['parsed_id']);
        $response->assertJsonPath('requires_human_review', true);
        $response->assertJsonPath('review_reason', 'Needs inspection');
        $response->assertJsonPath('states.extracted.id', $rec['extracted_id']);
        $response->assertJsonPath('states.parsed.id', $rec['parsed_id']);
        $response->assertJsonPath('states.scrubbed.id', $rec['scrubbed_id']);
    }

    public function test_admin_can_update_record_fields_and_preserve_footnotes(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $rec = $this->createRecordWithStates(true, 'Needs update');

        $response = $this->actingAs($admin)->putJson("/admin/legal-records/{$rec['scrubbed_id']}", [
            'title' => 'Updated Landmark Decision',
            'case_number' => 'CCT 99/26',
            'court' => 'Constitutional Court of South Africa',
            'applicant' => 'New Applicant Ltd',
            'respondent' => 'Minister of Justice',
            'judges' => ['Maya DCJ', 'Zondo CJ'],
            'ratio_decidendi' => 'Updated ratio decidendi text.',
            'summary' => 'Refined executive summary.',
            'requires_human_review' => false,
            'review_reason' => 'Resolved after editing',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify scrubbed_records updated
        $updatedScrubbed = DB::connection('pgsql_coeus')->table('scrubbed_records')->where('id', $rec['scrubbed_id'])->first();
        $srData = json_decode($updatedScrubbed->data, true);

        $this->assertSame('Updated Landmark Decision', $srData['title']);
        $this->assertSame('CCT 99/26', $srData['case_number']);
        $this->assertSame('New Applicant Ltd', $srData['extracted_data']['applicant_plaintiff']);
        $this->assertSame('Updated ratio decidendi text.', $srData['extracted_data']['ratio_decidendi']);
        $this->assertSame('Refined executive summary.', $srData['ai_summary']);

        // Check that footnotes/precedents are preserved intact!
        $this->assertCount(1, $srData['extracted_data']['precedents_cited']);
        $this->assertSame('S v Makwanyane', $srData['extracted_data']['precedents_cited'][0]['case_name']);

        // Verify extracted_records updated
        $this->assertDatabaseHas('extracted_records', [
            'id' => $rec['extracted_id'],
            'requires_human_review' => false,
            'review_reason' => 'Resolved after editing',
        ], 'pgsql_coeus');
    }
}
