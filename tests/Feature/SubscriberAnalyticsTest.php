<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\Order;
use App\Models\Product;
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
        $response = $this->get('/pro-dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_unsubscribed_user_is_redirected_to_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/pro-dashboard');

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('error', 'Please subscribe to the Pro Analytics package to access this section.');
    }

    public function test_subscribed_user_can_access_subscriber_analytics(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        $response = $this->actingAs($user)->get('/pro-dashboard');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_subscriber_analytics_without_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/pro-dashboard');

        $response->assertStatus(200);
    }

    public function test_subscriber_analytics_renders_with_cases_from_database(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);
        Analytics::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/pro-dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Subscriber/Analytics/Index')
            ->has('cases', 5)
        );
    }

    public function test_subscriber_analytics_passes_correct_case_structure(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);
        Analytics::factory()->create([
            'title' => 'Test v TestCorp, TC1',
            'employer' => 'TestCorp Ltd',
            'employee' => '[REDACTED]',
            'court_location' => 'Gauteng [Johannesburg]',
            'reason_for_dismissal' => 'MISCONDUCT',
        ]);

        $response = $this->actingAs($user)->get('/pro-dashboard');

        $response->assertStatus(200);

        $cases = $response->original->getData()['page']['props']['cases'];
        $this->assertCount(1, $cases);
        $this->assertEquals('TestCorp Ltd', $cases[0]['employer']);
        $this->assertEquals('[REDACTED]', $cases[0]['employee']);
        $this->assertEquals('Gauteng [Johannesburg]', $cases[0]['court_location']);
    }

    public function test_subscriber_analytics_renders_empty_when_no_cases(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        $response = $this->actingAs($user)->get('/pro-dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Subscriber/Analytics/Index')
            ->has('cases', 0)
        );
    }

    public function test_old_analytics_route_no_longer_exists(): void
    {
        $user = User::factory()->create();
        $this->subscribeUser($user);

        $response = $this->actingAs($user)->get('/pro-dashboard/analytics');

        $response->assertNotFound();
    }
}
