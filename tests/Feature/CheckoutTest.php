<?php

namespace Tests\Feature;

use App\Models\Cart;
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
        $response = $this->get(route('checkout.index'));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error', 'Your cart is empty.');
    }

    public function test_checkout_screen_renders_if_cart_has_items()
    {
        $product = Product::factory()->create(['price' => 100]);
        $cart = Cart::create();
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

        // Redirects to orders index for authenticated user
        $response->assertRedirect(route('orders.index'));

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

    public function test_can_process_checkout_for_guest_user()
    {
        Notification::fake();

        $product = Product::factory()->create([
            'price' => 150,
            'stock' => 5,
        ]);

        $cart = Cart::create();
        $cartItem = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 150,
        ]);

        $mockCartService = $this->mock(CartService::class);
        $mockCartService->shouldReceive('getCart')->andReturn($cart);
        $mockCartService->shouldReceive('getSummary')->andReturn([
            'subtotal' => 150.00,
            'discount' => 0.00,
            'total' => 150.00,
        ]);
        $mockCartService->shouldReceive('clearCart')->once();

        $response = $this->post(route('checkout.store'), [
            'email' => 'guest@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'country' => 'South Africa',
            'phone' => '0119876543',
        ]);

        // Redirects to home for guest user
        $response->assertRedirect(route('home'));

        $this->assertDatabaseHas('orders', [
            'user_id' => null,
            'email' => 'guest@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'total_amount' => 150.00,
        ]);
    }
}
