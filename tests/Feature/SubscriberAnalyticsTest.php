<?php

namespace Tests\Feature;

use App\Models\CcmaAnalytics;
use App\Models\LegalAnalytics;
use App\Models\Order;
use App\Models\Product;
use App\Models\TargetVanity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
    }

    protected function subscribeUser(User $user): void
    {
        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        $product = Product::factory()->create([
            'name' => 'Analytics Dashboard',
            'slug' => 'pro-analytics',
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address' => '123 Street',
            'city' => 'Johannesburg',
            'country' => 'South Africa',
            'phone' => '123456789',
            'total_amount' => $product->price,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);
    }

    public function test_subscriber_analytics_requires_authentication(): void
    {
        $response = $this->get('/subscriber');

        $response->assertRedirect(route('login'));
    }

    public function test_unsubscribed_user_is_redirected_to_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/subscriber');

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('error', 'An active subscription is required to access this section.');
    }

    public function test_subscribed_user_can_access_subscriber_analytics(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        $response = $this->actingAs($user)->get('/subscriber');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_subscriber_analytics_without_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/subscriber');

        $response->assertStatus(200);
    }

    public function test_subscriber_analytics_renders_with_filters_prop(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        TargetVanity::create([
            'target_name' => 'sabinet_ccma',
            'vanity_name' => 'CCMA Labour Awards',
            'target_type' => 'cases',
        ]);

        $response = $this->actingAs($user)->get('/subscriber');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Subscriber/Analytics/SafliiCourts')
            ->has('filters')
            ->where('filters.0.target_name', 'sabinet_ccma')
        );
    }

    public function test_subscriber_ccma_route_renders_ccma_component(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        $response = $this->actingAs($user)->get('/subscriber/analytics/ccma');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Subscriber/Analytics/CcmaAwards')
            ->has('filters')
        );
    }

    public function test_subscriber_saflii_route_renders_saflii_component(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        $response = $this->actingAs($user)->get('/subscriber/analytics/saflii');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Subscriber/Analytics/SafliiCourts')
            ->has('filters')
        );
    }

    public function test_subscriber_analytics_no_longer_sends_raw_cases_in_inertia_response(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);
        CcmaAnalytics::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/subscriber');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Subscriber/Analytics/SafliiCourts')
            ->missing('cases')
        );
    }

    public function test_analytics_data_endpoint_requires_authentication(): void
    {
        $response = $this->get('/subscriber/analytics/data');

        $response->assertRedirect(route('login'));
    }

    public function test_analytics_data_endpoint_returns_ccma_payload(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        CcmaAnalytics::factory()->count(3)->create([
            'employer' => 'TestCorp Ltd',
            'court_location' => 'Gauteng [Johannesburg]',
            'reason_for_dismissal' => 'MISCONDUCT',
        ]);

        $response = $this->actingAs($user)->getJson('/subscriber/analytics/data?target_name=sabinet_ccma');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'type',
            'cases',
            'filter_options' => ['provinces', 'employers', 'months'],
        ]);
        $response->assertJsonPath('type', 'ccma');
        $this->assertCount(3, $response->json('cases'));
    }

    public function test_analytics_data_endpoint_filters_ccma_by_province(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        CcmaAnalytics::factory()->create(['court_location' => 'Gauteng [Johannesburg]', 'reason_for_dismissal' => 'misconduct']);
        CcmaAnalytics::factory()->create(['court_location' => 'Western Cape [Cape Town]', 'reason_for_dismissal' => 'misconduct']);

        $response = $this->actingAs($user)->getJson('/subscriber/analytics/data?target_name=sabinet_ccma&province=Gauteng');

        $response->assertStatus(200);
        $response->assertJsonPath('type', 'ccma');
        $this->assertCount(1, $response->json('cases'));
        $this->assertStringContainsString('Gauteng', $response->json('cases.0.court_location'));
    }

    public function test_analytics_data_endpoint_returns_saflii_courts_payload(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        LegalAnalytics::factory()->create([
            'target_name' => 'ZACC',
            'target_type' => 'cases',
            'court' => 'Constitutional Court of South Africa',
            'case_number' => 'CCT 01/20',
            'document_date' => '2020-05-15',
            'data' => [
                'extracted_data' => [
                    'court' => 'Constitutional Court of South Africa',
                    'reportable' => true,
                    'summary' => 'Landmark constitutional rights case.',
                    'ratio_decidendi' => 'Section 27 enforces right to access.',
                    'judges' => ['Cameron J', 'Froneman J'],
                    'precedents_cited' => [
                        ['case_name_citation' => '[1999] ZACC 17', 'treatment' => 'Applied/Followed'],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/subscriber/analytics/data?type=saflii_courts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'type',
            'totals' => ['total_cases', 'reportable_count', 'reportable_percentage', 'total_precedents', 'avg_precedents_per_case', 'total_judges', 'avg_hearing_to_judgment_days'],
            'courts_breakdown',
            'timeline_trend' => ['years', 'counts', 'avg_duration_days'],
            'precedents_intelligence' => ['top_cited', 'treatment_distribution', 'density_distribution'],
            'bench_intelligence' => ['top_judges', 'panel_sizes'],
            'typology_distribution' => ['labels', 'series'],
            'cases',
            'total_filtered_cases',
            'filter_options' => ['courts', 'judges', 'years'],
        ]);
        $response->assertJsonPath('type', 'saflii_courts');
        $response->assertJsonPath('totals.total_cases', 1);
        $this->assertCount(1, $response->json('cases'));
        $this->assertEquals('Section 27 enforces right to access.', $response->json('cases.0.ratio_decidendi'));
        $this->assertNotEmpty($response->json('typology_distribution.labels'));
    }

    public function test_analytics_data_endpoint_supports_limit_parameter(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        LegalAnalytics::factory()->count(5)->create([
            'target_name' => 'ZACC',
            'target_type' => 'cases',
            'court' => 'ZACC',
            'data' => [
                'extracted_data' => [
                    'court' => 'Constitutional Court of South Africa',
                    'reportable' => true,
                    'ratio_decidendi' => 'Constitutional precedent.',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/subscriber/analytics/data?type=saflii_courts&limit=2');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('cases'));
        $this->assertEquals(5, $response->json('total_filtered_cases'));
    }

    public function test_analytics_data_endpoint_falls_back_to_demo_data_when_no_records_exist(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        // Ensure database tables for legal records are completely empty
        LegalAnalytics::query()->delete();
        if (\Illuminate\Support\Facades\Schema::hasTable('scrubbed_records')) {
            \Illuminate\Support\Facades\DB::table('scrubbed_records')->delete();
        }

        $response = $this->actingAs($user)->getJson('/subscriber/analytics/data?type=saflii_courts');

        $response->assertStatus(200);
        $response->assertJsonPath('type', 'saflii_courts');
        $this->assertGreaterThan(0, $response->json('totals.total_cases'));
        $this->assertNotEmpty($response->json('cases'));
    }

    public function test_analytics_data_endpoint_returns_legal_payload(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        TargetVanity::create([
            'target_name' => 'ZACC',
            'vanity_name' => 'Constitutional Court of South Africa',
            'target_type' => 'cases',
        ]);

        LegalAnalytics::factory()->count(4)->create([
            'target_name' => 'ZACC',
            'court' => 'ZACC',
        ]);

        $response = $this->actingAs($user)->getJson('/subscriber/analytics/data?target_name=ZACC');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'type',
            'target_name',
            'vanity_name',
            'target_type',
            'totals' => ['total', 'with_case_number', 'with_date'],
            'by_year',
            'by_month',
            'by_document_type',
            'top_courts',
            'recent',
        ]);
        $response->assertJsonPath('type', 'legal');
        $response->assertJsonPath('target_name', 'ZACC');
        $response->assertJsonPath('totals.total', 4);
    }

    public function test_analytics_data_endpoint_filters_saflii_by_court_acronym_and_standard_name(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        LegalAnalytics::factory()->create([
            'target_name' => 'ZACC',
            'target_type' => 'cases',
            'court' => 'ZACC',
            'data' => [
                'extracted_data' => [
                    'court' => 'Constitutional Court of South Africa',
                    'reportable' => true,
                ],
            ],
        ]);

        LegalAnalytics::factory()->create([
            'target_name' => 'ZASCA',
            'target_type' => 'cases',
            'court' => 'ZASCA',
            'data' => [
                'extracted_data' => [
                    'court' => 'Supreme Court of Appeal of South Africa',
                    'reportable' => true,
                ],
            ],
        ]);

        // Filter by acronym 'ZACC'
        $responseZacc = $this->actingAs($user)->getJson('/subscriber/analytics/data?type=saflii_courts&court=ZACC');
        $responseZacc->assertStatus(200);
        $this->assertCount(1, $responseZacc->json('cases'));
        $this->assertEquals('ZACC', $responseZacc->json('cases.0.target_name'));

        // Filter by full standardized court name 'Supreme Court of Appeal of South Africa'
        $responseSca = $this->actingAs($user)->getJson('/subscriber/analytics/data?type=saflii_courts&court=' . urlencode('Supreme Court of Appeal of South Africa'));
        $responseSca->assertStatus(200);
        $this->assertCount(1, $responseSca->json('cases'));
        $this->assertEquals('ZASCA', $responseSca->json('cases.0.target_name'));
    }

    public function test_old_analytics_route_no_longer_exists(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        $response = $this->actingAs($user)->get('/subscriber/analytics');

        $response->assertNotFound();
    }
}

