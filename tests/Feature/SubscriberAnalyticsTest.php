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
        $product = Product::factory()->create([
            'name' => 'Analytics Dashboard',
            'slug' => 'pro-analytics',
        ]);
        $order = Order::create([
            'user_id'        => $user->id,
            'email'          => $user->email,
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'address'        => '123 Street',
            'city'           => 'Johannesburg',
            'country'        => 'South Africa',
            'phone'          => '123456789',
            'total_amount'   => $product->price,
            'status'         => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity'   => 1,
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
            ->component('Subscriber/Analytics/Index')
            ->has('filters')
            ->where('filters.0.target_name', 'sabinet_ccma')
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
            ->component('Subscriber/Analytics/Index')
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
            'employer'             => 'TestCorp Ltd',
            'court_location'       => 'Gauteng [Johannesburg]',
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
            'court'       => 'ZACC',
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
