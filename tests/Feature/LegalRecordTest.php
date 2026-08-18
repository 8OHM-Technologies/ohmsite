<?php

namespace Tests\Feature;

use App\Models\CcmaAnalytics;
use App\Models\LegalAnalytics;
use App\Models\TargetVanity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

        CcmaAnalytics::create([
            'title' => 'Smith v ABC Corp',
            'document_type' => 'CCMA Award',
            'award_date' => '2026-01-15',
            'court' => 'CCMA Johannesburg',
            'award_number' => 'GAJB1234-26',
            'employee' => 'John Smith',
            'employer' => 'ABC Corp',
            'court_location' => 'Johannesburg',
            'reason_for_dismissal' => 'Misconduct',
        ]);

        LegalAnalytics::create([
            'target_type' => 'cases',
            'target_name' => 'ZACC',
            'title' => 'State v Defendant',
            'document_type' => 'Judgment',
            'document_date' => '2026-02-10',
            'court' => 'ZACC',
            'case_number' => 'CCT 12/26',
            'data' => ['applicant_plaintiff' => 'State', 'respondent_defendant' => 'Defendant'],
        ]);

        LegalAnalytics::create([
            'target_type' => 'journals',
            'target_name' => 'PER',
            'title' => 'The Evolution of Labour Law',
            'document_type' => 'Journal Article',
            'document_date' => '2026-03-01',
            'data' => ['journal_name' => 'PER', 'volume' => 'Vol 29 (2026)'],
        ]);

        $response = $this->actingAs($user)->getJson('/legal-records/data?category=cases');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 2);
    }

    public function test_legal_records_data_endpoint_returns_category_filtered_journals(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        LegalAnalytics::create([
            'target_type' => 'journals',
            'target_name' => 'PER',
            'title' => 'The Evolution of Labour Law',
            'document_type' => 'Journal Article',
            'document_date' => '2026-03-01',
            'data' => ['journal_name' => 'PER', 'volume' => 'Vol 29 (2026)'],
        ]);

        LegalAnalytics::create([
            'target_type' => 'gaz',
            'target_name' => 'ZAGovGaz',
            'title' => 'Government Notice 456',
            'document_type' => 'Gazette',
            'document_date' => '2026-03-05',
            'data' => ['gazette_number' => '50000'],
        ]);

        LegalAnalytics::create([
            'target_type' => 'cases',
            'target_name' => 'ZACC',
            'title' => 'State v Defendant',
            'document_type' => 'Judgment',
            'document_date' => '2026-02-10',
            'court' => 'ZACC',
            'case_number' => 'CCT 12/26',
            'data' => [],
        ]);

        $response = $this->actingAs($user)->getJson('/legal-records/data?category=journals');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 2);
    }

    public function test_legal_records_data_endpoint_returns_category_filtered_court_rolls(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        LegalAnalytics::create([
            'target_type' => 'other',
            'target_name' => 'ZACC_Rolls',
            'title' => 'Motion Court Roll for 15 March 2026',
            'document_type' => 'Court Roll',
            'document_date' => '2026-03-15',
            'court' => 'ZACC',
            'data' => ['court_location' => 'Johannesburg'],
        ]);

        LegalAnalytics::create([
            'target_type' => 'cases',
            'target_name' => 'ZACC',
            'title' => 'State v Defendant',
            'document_type' => 'Judgment',
            'document_date' => '2026-02-10',
            'court' => 'ZACC',
            'case_number' => 'CCT 12/26',
            'data' => [],
        ]);

        $response = $this->actingAs($user)->getJson('/legal-records/data?category=court_rolls');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
    }

    public function test_legal_records_show_endpoint(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $ccma = CcmaAnalytics::create([
            'title' => 'Smith v ABC Corp',
            'document_type' => 'CCMA Award',
            'award_date' => '2026-01-15',
            'court' => 'CCMA Johannesburg',
            'award_number' => 'GAJB1234-26',
            'employee' => 'John Smith',
            'employer' => 'ABC Corp',
            'court_location' => 'Johannesburg',
            'reason_for_dismissal' => 'Misconduct',
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/record/{$ccma->id}?source_table=ccma");

        $response->assertStatus(200);
        $response->assertJsonPath('data.award_number', 'GAJB••••');
        $response->assertJsonPath('is_pro', false);
    }

    public function test_standard_user_receives_blurred_locked_record_fields(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $legal = LegalAnalytics::create([
            'target_type' => 'cases',
            'target_name' => 'ZACC',
            'title' => 'Constitutional Rights Matter',
            'document_type' => 'Judgment',
            'document_date' => '2026-02-10',
            'court' => 'ZACC',
            'case_number' => 'CCT 100/26',
            'source_url' => 'https://www.saflii.org/za/cases/ZACC/2026/1.html',
            'data' => [
                'applicant_plaintiff' => 'Civil Rights Org',
                'respondent_defendant' => 'Minister of Justice',
                'extracted_data' => [
                    'ratio_decidendi' => 'The fundamental right to fair trial cannot be arbitrarily suspended.',
                    'judges' => ['Chief Justice Zondo', 'Deputy Chief Justice Maya'],
                    'precedents_cited' => [
                        ['case_name_citation' => 'Makwanyane [1995] ZACC 3', 'treatment' => 'Applied/Followed'],
                    ],
                    'order' => 'The application is upheld with costs.',
                    'summary' => 'Constitutional challenge concerning administrative justice timelines.',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/record/{$legal->id}?source_table=legal");

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

        $legal = LegalAnalytics::create([
            'target_type' => 'cases',
            'target_name' => 'ZACC',
            'title' => 'Constitutional Rights Matter',
            'document_type' => 'Judgment',
            'document_date' => '2026-02-10',
            'court' => 'ZACC',
            'case_number' => 'CCT 100/26',
            'source_url' => 'https://www.saflii.org/za/cases/ZACC/2026/1.html',
            'data' => [
                'extracted_data' => [
                    'ratio_decidendi' => 'The fundamental right to fair trial cannot be arbitrarily suspended.',
                    'judges' => ['Chief Justice Zondo'],
                    'precedents_cited' => [
                        ['case_name_citation' => 'Makwanyane [1995] ZACC 3', 'treatment' => 'Applied/Followed'],
                    ],
                    'order' => 'The application is upheld with costs.',
                    'summary' => 'Constitutional challenge concerning administrative justice timelines.',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson("/legal-records/record/{$legal->id}?source_table=legal");

        $response->assertStatus(200);
        $response->assertJsonPath('is_pro', true);
        $response->assertJsonPath('source_url', 'https://www.saflii.org/za/cases/ZACC/2026/1.html');
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
