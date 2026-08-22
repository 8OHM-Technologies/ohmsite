<?php

namespace Tests\Feature;

use App\Models\TargetVanity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::connection('pgsql_coeus')->hasTable('extracted_records')) {
            Schema::connection('pgsql_coeus')->create('extracted_records', function ($table) {
                $table->uuid('id')->primary();
                $table->string('record_type')->nullable();
                $table->string('source_url')->nullable();
                $table->date('document_date')->nullable();
                $table->json('data')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('scrubbed_at')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::connection('pgsql_coeus')->hasColumn('extracted_records', 'scrubbed_at')) {
            Schema::connection('pgsql_coeus')->table('extracted_records', function ($table) {
                $table->timestamp('scrubbed_at')->nullable();
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

        TargetVanity::create([
            'target_name' => 'PER',
            'vanity_name' => 'Potchefstroom Electronic Law Journal',
            'target_type' => 'journals',
        ]);

        TargetVanity::create([
            'target_name' => 'ZAGovGaz',
            'vanity_name' => 'South African Government Gazette',
            'target_type' => 'gaz',
        ]);

        TargetVanity::create([
            'target_name' => 'ZACC_Rolls',
            'vanity_name' => 'Constitutional Court Hearing Rolls',
            'target_type' => 'other',
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

    private string $targetId;

    protected array $createdExtractedIds = [];

    protected array $createdScrubbedIds = [];

    protected function tearDown(): void
    {
        if (! empty($this->createdScrubbedIds)) {
            DB::connection('pgsql_coeus')->table('scrubbed_records')->whereIn('id', $this->createdScrubbedIds)->delete();
        }
        if (! empty($this->createdExtractedIds)) {
            DB::connection('pgsql_coeus')->table('extracted_records')->whereIn('id', $this->createdExtractedIds)->delete();
        }
        parent::tearDown();
    }

    private function createScrubbedRecord(string $recordType, string $category, array $scrubbedData, array $extractedData = [], ?string $docDate = '2026-02-10'): string
    {
        $extId = (string) Str::uuid();
        $scrubbedId = (string) Str::uuid();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $extId,
            'target_id' => $this->targetId,
            'record_type' => $recordType,
            'source_url' => $extractedData['source_url'] ?? 'https://www.saflii.org/za/cases/ZACC/2026/'.Str::random(10).'.html',
            'document_date' => $docDate,
            'data' => json_encode(array_merge(['category' => $category], $extractedData)),
            'status' => 'detailed',
            'scrubbed_at' => now(),
            'scraped_at' => now(),
            'detailed_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => $scrubbedId,
            'extracted_record_id' => $extId,
            'data' => json_encode($scrubbedData),
            'created_at' => now(),
        ]);

        $this->createdExtractedIds[] = $extId;
        $this->createdScrubbedIds[] = $scrubbedId;

        return $scrubbedId;
    }

    public function test_legal_records_requires_authentication(): void
    {
        $this->get('/legal-records')->assertRedirect(route('login'));
        $this->get('/legal-records/cases')->assertRedirect(route('login'));
        $this->get('/legal-records/journals')->assertRedirect(route('login'));
        $this->get('/legal-records/court-rolls')->assertRedirect(route('login'));
    }

    public function test_authenticated_verified_user_can_access_cases_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/legal-records/cases');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Subscriber/LegalRecords/Cases')
            ->has('filters', 2)
        );
    }

    public function test_index_route_renders_cases_view(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/legal-records');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Subscriber/LegalRecords/Cases')
        );
    }

    public function test_authenticated_verified_user_can_access_journals_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/legal-records/journals');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Subscriber/LegalRecords/Journals')
            ->has('filters', 2)
        );
    }

    public function test_authenticated_verified_user_can_access_court_rolls_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/legal-records/court-rolls');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Subscriber/LegalRecords/CourtRolls')
            ->has('filters', 1)
        );
    }

    public function test_legal_records_data_endpoint_returns_category_filtered_cases(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $uniq = Str::random(8);

        $this->createScrubbedRecord('sabinet_ccma', 'cases', [
            'title' => "Smith v ABC Corp {$uniq}",
            'case_number' => "GAJB1234-26-{$uniq}",
        ]);

        $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => "State v Defendant {$uniq}",
            'extracted_data' => ['applicant_plaintiff' => 'State', 'respondent_defendant' => 'Defendant'],
        ]);

        $this->createScrubbedRecord('saflii_courts', 'journals', [
            'title' => "The Evolution of Labour Law {$uniq}",
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/data?category=cases&search={$uniq}");

        $response->assertStatus(200);
        $response->assertJsonPath('total', 2);
    }

    public function test_legal_records_data_endpoint_returns_category_filtered_journals(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $uniq = Str::random(8);

        $this->createScrubbedRecord('saflii_courts', 'journals', [
            'title' => "The Evolution of Labour Law {$uniq}",
        ]);

        $this->createScrubbedRecord('saflii_courts', 'gaz', [
            'title' => "Government Notice 456 {$uniq}",
        ]);

        $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => "State v Defendant {$uniq}",
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/data?category=journals&search={$uniq}");

        $response->assertStatus(200);
        $response->assertJsonPath('total', 2);
    }

    public function test_legal_records_data_endpoint_returns_category_filtered_court_rolls(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $uniq = Str::random(8);

        $this->createScrubbedRecord('saflii_courts', 'other', [
            'title' => "Motion Court Roll for 15 March 2026 {$uniq}",
        ]);

        $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => "State v Defendant {$uniq}",
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/data?category=court_rolls&search={$uniq}");

        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
    }

    public function test_legal_records_data_endpoint_sorts_by_document_date_desc_by_default(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $uniq = Str::random(8);

        $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => "Older Case {$uniq}",
        ], [], '2020-01-15');

        $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => "Newer Case {$uniq}",
        ], [], '2026-03-01');

        $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => "Mid Date Case {$uniq}",
        ], [], '2023-06-20');

        $response = $this->actingAs($user)->getJson("/legal-records/data?category=cases&search={$uniq}");

        $response->assertStatus(200);
        $records = $response->json('records');
        $this->assertCount(3, $records);
        $this->assertSame("Newer Case {$uniq}", $records[0]['title']);
        $this->assertSame('2026-••-••', $records[0]['document_date']);
        $this->assertSame("Mid Date Case {$uniq}", $records[1]['title']);
        $this->assertSame('2023-••-••', $records[1]['document_date']);
        $this->assertSame("Older Case {$uniq}", $records[2]['title']);
        $this->assertSame('2020-••-••', $records[2]['document_date']);

        // Test with Pro User (Unmasked)
        $proUser = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $proResponse = $this->actingAs($proUser)->getJson("/legal-records/data?category=cases&search={$uniq}");
        $proResponse->assertStatus(200);
        $proRecords = $proResponse->json('records');
        $this->assertSame('2026-03-01', $proRecords[0]['document_date']);
        $this->assertSame('2023-06-20', $proRecords[1]['document_date']);
        $this->assertSame('2020-01-15', $proRecords[2]['document_date']);
    }

    public function test_legal_records_data_endpoint_sorts_journals_and_gazettes_by_document_date_desc(): void
    {
        $proUser = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $uniq = Str::random(8);

        $this->createScrubbedRecord('ZAGovGaz', 'gaz', [
            'title' => "Government Gazette 2021 {$uniq}",
        ], [], '2021-04-10');

        $this->createScrubbedRecord('PER', 'journals', [
            'title' => "Law Journal 2025 {$uniq}",
        ], [], '2025-08-15');

        $this->createScrubbedRecord('ZAGovGaz', 'gaz', [
            'title' => "Government Gazette 2024 {$uniq}",
        ], [], '2024-02-01');

        $response = $this->actingAs($proUser)->getJson("/legal-records/data?category=journals&search={$uniq}");

        $response->assertStatus(200);
        $records = $response->json('records');
        $this->assertCount(3, $records);
        $this->assertSame("Law Journal 2025 {$uniq}", $records[0]['title']);
        $this->assertSame('2025-08-15', $records[0]['document_date']);
        $this->assertSame("Government Gazette 2024 {$uniq}", $records[1]['title']);
        $this->assertSame('2024-02-01', $records[1]['document_date']);
        $this->assertSame("Government Gazette 2021 {$uniq}", $records[2]['title']);
        $this->assertSame('2021-04-10', $records[2]['document_date']);
    }

    public function test_legal_records_data_endpoint_sorts_court_rolls_by_document_date_desc(): void
    {
        $proUser = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $uniq = Str::random(8);

        $this->createScrubbedRecord('saflii_courts', 'other', [
            'title' => "Motion Roll Old {$uniq}",
        ], [], '2022-05-10');

        $this->createScrubbedRecord('saflii_courts', 'other', [
            'title' => "Motion Roll Recent {$uniq}",
        ], [], '2026-01-20');

        $this->createScrubbedRecord('saflii_courts', 'other', [
            'title' => "Motion Roll Mid {$uniq}",
        ], [], '2024-11-12');

        $response = $this->actingAs($proUser)->getJson("/legal-records/data?category=court_rolls&search={$uniq}");

        $response->assertStatus(200);
        $records = $response->json('records');
        $this->assertCount(3, $records);
        $this->assertSame("Motion Roll Recent {$uniq}", $records[0]['title']);
        $this->assertSame('2026-01-20', $records[0]['document_date']);
        $this->assertSame("Motion Roll Mid {$uniq}", $records[1]['title']);
        $this->assertSame('2024-11-12', $records[1]['document_date']);
        $this->assertSame("Motion Roll Old {$uniq}", $records[2]['title']);
        $this->assertSame('2022-05-10', $records[2]['document_date']);
    }

    public function test_legal_records_sorts_correctly_when_document_date_is_in_extracted_json_payload(): void
    {
        $proUser = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $uniq = Str::random(8);

        // All 3 records have the SAME base extracted_records.document_date (e.g. scraper default '2000-01-01')
        // but have different extracted_data.judgment_date in JSON
        $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => "Case July {$uniq}",
            'extracted_data' => [
                'judgment_date' => '2026-07-27',
            ],
        ], [], '2000-01-01');

        $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => "Case March {$uniq}",
            'extracted_data' => [
                'judgment_date' => '2026-03-03',
            ],
        ], [], '2000-01-01');

        $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => "Case May {$uniq}",
            'extracted_data' => [
                'judgment_date' => '2026-05-28',
            ],
        ], [], '2000-01-01');

        $response = $this->actingAs($proUser)->getJson("/legal-records/data?category=cases&search={$uniq}");

        $response->assertStatus(200);
        $records = $response->json('records');
        $this->assertCount(3, $records);
        $this->assertSame("Case July {$uniq}", $records[0]['title']);
        $this->assertSame('2026-07-27', $records[0]['document_date']);
        $this->assertSame("Case May {$uniq}", $records[1]['title']);
        $this->assertSame('2026-05-28', $records[1]['document_date']);
        $this->assertSame("Case March {$uniq}", $records[2]['title']);
        $this->assertSame('2026-03-03', $records[2]['document_date']);
    }

    public function test_standard_user_receives_blurred_locked_record_fields(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $id = $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => 'Constitutional Rights Matter',
            'case_number' => 'CCT 100/26',
            'extracted_data' => [
                'ratio_decidendi' => 'The fundamental right to fair trial cannot be arbitrarily suspended.',
                'judges' => ['Chief Justice Zondo', 'Deputy Chief Justice Maya'],
                'precedents_cited' => [
                    ['case_name_citation' => 'Makwanyane [1995] ZACC 3', 'treatment' => 'Applied/Followed'],
                ],
                'order' => 'The application is upheld with costs.',
                'summary' => 'Constitutional challenge concerning administrative justice timelines.',
            ],
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/record/{$id}");

        $response->assertStatus(200);
        $response->assertJsonPath('is_pro', false);
        $response->assertJsonPath('source_url', null);
        $response->assertJsonPath('data.is_locked', true);
        $response->assertJsonPath('data.title', 'Constitutional Rights Matter');
        $response->assertJsonPath('data.summary', 'Constitutional challenge concerning administrative justice timelines.');
        $this->assertStringContainsString('••••', (string) $response->json('data.case_number'));
        $this->assertStringContainsString('Pro', (string) $response->json('data.ratio_decidendi'));
        $response->assertJsonPath('data.precedents_cited', []);
    }

    public function test_subscribed_user_receives_complete_unredacted_dossier(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $id = $this->createScrubbedRecord('saflii_courts', 'cases', [
            'title' => 'Constitutional Rights Matter',
            'case_number' => 'CCT 100/26',
            'extracted_data' => [
                'ratio_decidendi' => 'The fundamental right to fair trial cannot be arbitrarily suspended.',
                'judges' => ['Chief Justice Zondo'],
                'precedents_cited' => [
                    ['case_name_citation' => 'Makwanyane [1995] ZACC 3', 'treatment' => 'Applied/Followed'],
                ],
                'order' => 'The application is upheld with costs.',
                'summary' => 'Constitutional challenge concerning administrative justice timelines.',
            ],
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/record/{$id}");

        $response->assertStatus(200);
        $response->assertJsonPath('is_pro', true);
        $response->assertJsonPath('data.is_locked', false);
        $response->assertJsonPath('data.case_number', 'CCT 100/26');
        $response->assertJsonPath('data.ratio_decidendi', 'The fundamental right to fair trial cannot be arbitrarily suspended.');
        $response->assertJsonPath('data.judges.0', 'Chief Justice Zondo');
        $response->assertJsonPath('data.precedents_cited.0.case_name_citation', 'Makwanyane [1995] ZACC 3');
    }

    public function test_journal_record_returns_publication_fields_and_formatted_text(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $id = $this->createScrubbedRecord('saflii_courts', 'journals', [
            'title' => 'The Evolution of Modern Labour Law Jurisprudence',
            'citation' => '2026 PER 45',
            'author' => 'Prof. S. van der Merwe',
            'full_text' => "Paragraph 1: Introduction to constitutional employment rights.\n\nParagraph 2: Detailed comparative analysis.",
            'summary' => 'Comprehensive legal review of modern labor dynamics.',
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/record/{$id}");

        $response->assertStatus(200);
        $response->assertJsonPath('is_pro', true);
        $response->assertJsonPath('data.category', 'journals');
        $response->assertJsonPath('data.title', 'The Evolution of Modern Labour Law Jurisprudence');
        $response->assertJsonPath('data.citation', '2026 PER 45');
        $response->assertJsonPath('data.author', 'Prof. S. van der Merwe');
        $this->assertStringContainsString('Paragraph 1', (string) $response->json('data.full_text'));
        $response->assertJsonPath('data.summary', 'Comprehensive legal review of modern labor dynamics.');
    }

    public function test_court_roll_record_returns_schedule_entries_and_category(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $id = $this->createScrubbedRecord('saflii_courts', 'other', [
            'title' => 'Motion Court Roll for 15 March 2026',
            'roll_entries' => [
                ['item_no' => 1, 'case_number' => '12345/26', 'parties' => 'Alpha v Beta', 'nature' => 'Summary Judgment', 'courtroom' => '4A'],
                ['item_no' => 2, 'case_number' => '12346/26', 'parties' => 'Gamma v Delta', 'nature' => 'Rule 43 Application', 'courtroom' => '4B'],
            ],
            'summary' => 'Daily motion court hearings before Justice Mokgoro.',
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/record/{$id}");

        $response->assertStatus(200);
        $response->assertJsonPath('is_pro', true);
        $response->assertJsonPath('data.category', 'other');
        $response->assertJsonPath('data.title', 'Motion Court Roll for 15 March 2026');
        $response->assertJsonPath('data.roll_entries.0.case_number', '12345/26');
        $response->assertJsonPath('data.roll_entries.1.parties', 'Gamma v Delta');
    }

    public function test_standard_registered_user_is_blocked_from_subscriber_analytics(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/subscriber/analytics/saflii');
        $response->assertRedirect(route('subscriptions.index'));

        $responseCcma = $this->actingAs($user)->get('/subscriber/analytics/ccma');
        $responseCcma->assertRedirect(route('subscriptions.index'));
    }

    public function test_dataset_summary_shared_prop_returns_breakdown_counts(): void
    {
        Cache::forget('dataset_summary');

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $this->createScrubbedRecord('sabinet_ccma', 'cases', [
            'title' => 'Test CCMA Case 1',
        ]);
        $this->createScrubbedRecord('saflii_courts', 'journals', [
            'title' => 'Test Journal 1',
        ]);
        $this->createScrubbedRecord('saflii_courts', 'other', [
            'title' => 'Test Court Roll 1',
        ]);

        $response = $this->actingAs($user)->get('/legal-records/cases');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Subscriber/LegalRecords/Cases')
            ->has('dataset_summary')
            ->where('dataset_summary.total_records', fn ($val) => $val >= 3)
            ->where('dataset_summary.total_cases', fn ($val) => $val >= 1)
            ->where('dataset_summary.total_gazettes', fn ($val) => $val >= 1)
            ->where('dataset_summary.total_court_rolls', fn ($val) => $val >= 1)
        );

        $this->assertTrue(Cache::has('dataset_summary'));
    }

    public function test_dataset_summary_uses_cache_and_can_be_invalidated(): void
    {
        Cache::put('dataset_summary', [
            'total_records' => 999,
            'total_cases' => 111,
            'total_gazettes' => 222,
            'total_court_rolls' => 333,
            'date_range' => '2000 – 2026',
        ], 3600);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/legal-records/cases');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Subscriber/LegalRecords/Cases')
            ->where('dataset_summary.total_records', 999)
            ->where('dataset_summary.total_cases', 111)
            ->where('dataset_summary.total_gazettes', 222)
            ->where('dataset_summary.total_court_rolls', 333)
            ->where('dataset_summary.date_range', '2000 – 2026')
        );

        Cache::forget('dataset_summary');
        $this->assertFalse(Cache::has('dataset_summary'));
    }
}
