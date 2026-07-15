<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAutoIncrementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that order ID starts at 10000 instead of 1.
     */
    public function test_order_id_starts_at_10000(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'US',
            'total_amount' => 150.00,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->assertEquals(10000, $order->id);
    }
}
