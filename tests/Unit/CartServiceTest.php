<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
    }

    public function test_calculate_subtotal_correctly()
    {
        $product1 = Product::factory()->create(['price' => 50]);
        $product2 = Product::factory()->create(['price' => 30]);

        $this->cartService->addItem($product1->id, 2); // 100
        $this->cartService->addItem($product2->id, 1); // 30

        $summary = $this->cartService->getSummary();
        $this->assertEquals(130, $summary['subtotal']);
    }

    public function test_apply_percentage_coupon()
    {
        $product = Product::factory()->create(['price' => 100]);
        $this->cartService->addItem($product->id, 1);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'min_order' => 50,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $this->cartService->applyCoupon('SAVE10');
        $summary = $this->cartService->getSummary();

        $this->assertEquals(10, $summary['discount']);
        $this->assertEquals(93, $summary['total']); // 100 - 10 + 3 (shipping)
    }
}
