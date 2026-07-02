<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_product_to_cart()
    {
        $product = Product::factory()->create(['price' => 100]);

        $response = $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 2
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }

    public function test_can_remove_product_from_cart()
    {
        $product = Product::factory()->create();
        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1
        ]);

        $cartItem = \App\Models\CartItem::first();

        $response = $this->delete(route('cart.destroy', $cartItem->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }
}
