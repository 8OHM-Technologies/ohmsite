<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_handles_paystack_initialization_failure_gracefully()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'total_amount' => 100.00,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'paystack',
        ]);

        // Fake Paystack API to return 404 Not Found (e.g., plan not found)
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => false,
                'message' => 'Plan not found',
            ], 404),
        ]);

        $response = $this->get(route('payment.checkout', $order));

        // Assert redirect to orders index with error message
        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('error', 'Unable to initialize transaction with Paystack: Plan not found');
    }

    public function test_callback_handles_paystack_verification_failure_gracefully()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user);

        // Fake Paystack API to return 400 Bad Request or similar failure
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => false,
                'message' => 'Transaction not found',
            ], 400),
        ]);

        $response = $this->get(route('payment.callback', ['reference' => 'invalid-reference']));

        // Assert redirect to orders index with error message
        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('error', 'Payment verification failed: Transaction not found');
    }
}
