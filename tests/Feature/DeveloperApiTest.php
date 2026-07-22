<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\ApiKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeveloperApiTest extends TestCase
{
    use RefreshDatabase;

    protected function purchaseProduct(User $user, string $productName): void
    {
        $slug = match ($productName) {
            'Once-off Dataset' => 'once-off-dataset',
            'Developer API' => 'developer-api',
            'Analytics Dashboard' => 'pro-analytics',
            'Managed Data Pipeline' => 'managed-data-pipeline',
            default => str($productName)->slug()->toString(),
        };

        $product = Product::factory()->create([
            'name' => $productName,
            'slug' => $slug,
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

    public function test_api_keys_can_be_generated_and_deleted(): void
    {
        $user = User::factory()->create();
        $this->purchaseProduct($user, 'Developer API');

        $response = $this->actingAs($user)->post(route('developer.api-keys.store'), ['name' => 'My Key']);
        $response->assertRedirect();

        $this->assertDatabaseCount('api_keys', 1);
        $key = ApiKey::first();
        $this->assertStringStartsWith('ohm_live_', $key->key);
        $this->assertEquals('My Key', $key->name);

        $response = $this->actingAs($user)->delete(route('developer.api-keys.destroy', $key->id));
        $response->assertRedirect();
        $this->assertDatabaseCount('api_keys', 0);
    }

    public function test_api_requires_valid_key(): void
    {
        $response = $this->getJson('/api/v1/cases');
        $response->assertStatus(401);
        $response->assertJson(['error' => 'API key is missing.']);

        $response = $this->withHeaders(['Authorization' => 'Bearer invalid_key'])->getJson('/api/v1/cases');
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid API key.']);
    }

    public function test_api_requires_active_subscription(): void
    {
        $user = User::factory()->create();
        $key = ApiKey::create([
            'user_id' => $user->id,
            'key' => 'ohm_live_123456',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$key->key])->getJson('/api/v1/cases');
        $response->assertStatus(403);
        $response->assertJson(['error' => 'Active Developer API or Pro Analytics subscription is required.']);
    }

    public function test_api_allows_access_with_valid_subscription(): void
    {
        $user = User::factory()->create();
        $this->purchaseProduct($user, 'Developer API');
        $key = ApiKey::create([
            'user_id' => $user->id,
            'key' => 'ohm_live_123456',
        ]);

        Analytics::factory()->create([
            'court' => 'CCMA',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$key->key])->getJson('/api/v1/cases');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertDatabaseCount('api_calls', 1);
    }

    public function test_api_rate_limiting_developer_api_package(): void
    {
        $user = User::factory()->create();
        $this->purchaseProduct($user, 'Developer API');
        $key = ApiKey::create([
            'user_id' => $user->id,
            'key' => 'ohm_live_123456',
        ]);

        // Create 1000 simulated calls in bulk
        $calls = [];
        for ($i = 0; $i < 1000; $i++) {
            $calls[] = [
                'user_id' => $user->id,
                'api_key_id' => $key->id,
                'endpoint' => 'api/v1/cases',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('api_calls')->insert($calls);

        Analytics::factory()->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$key->key])->getJson('/api/v1/cases');
        $response->assertStatus(429);
        $response->assertJson(['error' => 'API rate limit exceeded for this month.']);
    }

    public function test_api_rate_limiting_pro_analytics_package(): void
    {
        $user = User::factory()->create();
        $this->purchaseProduct($user, 'Analytics Dashboard'); // slug: pro-analytics
        $key = ApiKey::create([
            'user_id' => $user->id,
            'key' => 'ohm_live_123456',
        ]);

        // Create 2999 simulated calls in bulk
        $calls = [];
        for ($i = 0; $i < 2999; $i++) {
            $calls[] = [
                'user_id' => $user->id,
                'api_key_id' => $key->id,
                'endpoint' => 'api/v1/cases',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('api_calls')->insert($calls);

        Analytics::factory()->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$key->key])->getJson('/api/v1/cases');
        $response->assertStatus(200);

        // One more call hits limit
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$key->key])->getJson('/api/v1/cases');
        $response->assertStatus(429);
    }

    public function test_api_rate_limiting_override(): void
    {
        $user = User::factory()->create([
            'api_limit_override' => 5,
        ]);
        $key = ApiKey::create([
            'user_id' => $user->id,
            'key' => 'ohm_live_123456',
        ]);

        // Create 5 simulated calls
        $calls = [];
        for ($i = 0; $i < 5; $i++) {
            $calls[] = [
                'user_id' => $user->id,
                'api_key_id' => $key->id,
                'endpoint' => 'api/v1/cases',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('api_calls')->insert($calls);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$key->key])->getJson('/api/v1/cases');
        $response->assertStatus(429);
    }

    public function test_api_banned_user_cannot_access(): void
    {
        $user = User::factory()->create([
            'is_banned' => true,
            'ban_reason' => 'Spamming',
        ]);
        $key = ApiKey::create([
            'user_id' => $user->id,
            'key' => 'ohm_live_123456',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$key->key])->getJson('/api/v1/cases');
        $response->assertStatus(403);
        $response->assertJson(['error' => 'Your account has been banned: Spamming']);
    }

    public function test_orders_page_shows_remaining_calls(): void
    {
        $user = User::factory()->create();
        $this->purchaseProduct($user, 'Developer API');

        $response = $this->actingAs($user)->get(route('orders.index'));
        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->has('apiStats', function ($stats) {
                $stats->where('used', 0)
                    ->where('limit', 1000)
                    ->where('remaining', 1000)
                    ->where('has_access', true);
            });
        });
    }
}
