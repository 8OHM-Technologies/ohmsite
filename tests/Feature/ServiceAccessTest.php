<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function purchaseProduct(User $user, string $productName): void
    {
        $slug = match ($productName) {
            'Once-off Dataset' => 'once-off-dataset',
            'Developer API' => 'developer-api',
            'Analytics Dashboard' => 'analytics-dashboard',
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

    public function test_guest_is_redirected_from_downloads(): void
    {
        $response = $this->get(route('downloads.dataset', ['dataset' => 'ccma']));
        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_api_docs(): void
    {
        $response = $this->get(route('developer.docs'));
        $response->assertRedirect('/login');
    }

    public function test_unpaid_customer_cannot_download_dataset(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('downloads.dataset', ['dataset' => 'ccma']));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('error', 'You must purchase the Once-off Dataset to access this download.');
    }

    public function test_unpaid_customer_cannot_access_api_docs(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('developer.docs'));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('error', 'You must have an active Developer API subscription to access the API documentation.');
    }

    public function test_paid_customer_can_download_dataset(): void
    {
        $user = User::factory()->create();
        $this->purchaseProduct($user, 'Once-off Dataset');

        $response = $this->actingAs($user)->get(route('downloads.dataset', ['dataset' => 'ccma']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="8ohm_ccma_dataset.csv"');
    }

    public function test_paid_customer_can_access_api_docs(): void
    {
        $user = User::factory()->create();
        $this->purchaseProduct($user, 'Developer API');

        $response = $this->actingAs($user)->get(route('developer.docs'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Developer/Docs'));
    }

    public function test_admin_can_access_both_without_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $responseDownload = $this->actingAs($admin)->get(route('downloads.dataset', ['dataset' => 'ccma']));
        $responseDownload->assertStatus(200);

        $responseDocs = $this->actingAs($admin)->get(route('developer.docs'));
        $responseDocs->assertStatus(200);
    }
}
