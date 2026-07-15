<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->product = Product::factory()->create([
            'name' => 'Pro Analytics',
            'slug' => 'pro-analytics',
            'price' => 3800.00,
        ]);
    }

    public function test_admin_can_update_customer_details(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.customers.update', $this->customer->id), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone' => '0821234567',
            'company_name' => 'Acme Corp',
            'country' => 'South Africa',
            'role' => 'customer',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Customer details updated successfully.');

        $this->customer->refresh();
        $this->assertEquals('Jane', $this->customer->first_name);
        $this->assertEquals('jane.doe@example.com', $this->customer->email);
        $this->assertEquals('Acme Corp', $this->customer->company_name);
    }

    public function test_admin_can_manually_create_order_for_customer(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.customers.create-order', $this->customer->id), [
            'product_id' => $this->product->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 3500.00,
            'frequency' => 'monthly',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Manual order created successfully.');

        // Verify order created in db
        $order = Order::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(3500.00, $order->total_amount);

        // Verify order item created
        $this->assertCount(1, $order->items);
        $this->assertEquals($this->product->id, $order->items->first()->product_id);

        // Verify customer subscription status synced
        $this->customer->refresh();
        $this->assertEquals('active', $this->customer->subscription_status);
        $this->assertNotNull($this->customer->subscribed_at);
        $this->assertNotNull($this->customer->subscription_code);
    }

    public function test_order_status_payment_status_sync_triggers_subscription_state(): void
    {
        // 1. Create a pending order for customer
        $order = Order::create([
            'user_id' => $this->customer->id,
            'email' => $this->customer->email,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'total_amount' => $this->product->price,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => $this->product->price,
        ]);

        // 2. Mark order as paid
        $response = $this->actingAs($this->admin)->patch(route('admin.orders.update-status', $order->id), [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);

        $this->customer->refresh();
        $this->assertEquals('active', $this->customer->subscription_status);

        // 3. Mark order as failed (revokes subscription)
        $response = $this->actingAs($this->admin)->patch(route('admin.orders.update-status', $order->id), [
            'status' => 'cancelled',
            'payment_status' => 'failed',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('failed', $order->payment_status);

        $this->customer->refresh();
        $this->assertNull($this->customer->subscription_status);
    }
}
