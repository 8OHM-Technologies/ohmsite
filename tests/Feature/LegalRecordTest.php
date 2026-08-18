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
}
