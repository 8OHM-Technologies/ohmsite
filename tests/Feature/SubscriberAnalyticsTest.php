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
            'cases',
            'filter_options' => ['courts', 'judges', 'years'],
        ]);
        $response->assertJsonPath('type', 'saflii_courts');
        $response->assertJsonPath('totals.total_cases', 1);
        $this->assertCount(1, $response->json('cases'));
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

    public function test_old_analytics_route_no_longer_exists(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        $response = $this->actingAs($user)->get('/subscriber/analytics');

        $response->assertNotFound();
    }
}
