<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = app(CartService::class);
    }

    public function test_checkout_screen_redirects_if_cart_is_empty()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user);

        $response = $this->get(route('checkout.index'));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error', 'Your cart is empty.');
    }

    public function test_checkout_screen_renders_if_cart_has_items()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user);

        $product = Product::factory()->create(['price' => 100]);
        $cart = Cart::create(['user_id' => $user->id]);
        $cartItem = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $mockCartService = $this->mock(CartService::class);
        $mockCartService->shouldReceive('getCart')->andReturn($cart);
        $mockCartService->shouldReceive('getSummary')->andReturn([
            'subtotal' => 100.00,
            'discount' => 0.00,
            'total' => 100.00,
        ]);

        $response = $this->get(route('checkout.index'));

        $response->assertStatus(200);
    }

    public function test_can_process_checkout_for_authenticated_user()
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create([
            'price' => 100,
            'stock' => 10,
        ]);

        // Log in user and add item to cart
        $this->actingAs($user);
        $this->cartService->addItem($product->id, 2);

        $response = $this->post(route('checkout.store'), [
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'South Africa',
            'phone' => '0211234567',
        ]);

        // Redirects to payment checkout
        $order = Order::latest()->first();
        $response->assertRedirect(route('payment.checkout', $order));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'total_amount' => 200.00,
            'status' => 'pending',
        ]);

        // Verify cart is cleared
        $this->assertTrue($this->cartService->getCart()->items->isEmpty());
    }

    public function test_can_process_checkout_and_save_profile_info()
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'customer',
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
        ]);
        $product = Product::factory()->create(['price' => 100]);

        $this->actingAs($user);
        $this->cartService->addItem($product->id, 1);

        $response = $this->post(route('checkout.store'), [
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company_name' => 'Acme Inc',
            'country' => 'South Africa',
            'phone' => '0211234567',
            'save_info' => true,
        ]);

        $order = Order::latest()->first();
        $response->assertRedirect(route('payment.checkout', $order));

        $user->refresh();
        $this->assertSame('John', $user->first_name);
        $this->assertSame('Doe', $user->last_name);
        $this->assertSame('Acme Inc', $user->company_name);
        $this->assertSame('0211234567', $user->phone);
        $this->assertSame('South Africa', $user->country);
    }

    public function test_guest_checkout_redirects_to_login()
    {
        $response = $this->post(route('checkout.store'), [
            'email' => 'guest@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'country' => 'South Africa',
            'phone' => '0119876543',
        ]);

        $response->assertRedirect(route('login'));
    }
}
