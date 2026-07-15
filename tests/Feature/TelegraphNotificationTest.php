<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Notifications\OrderPlaced;
use App\Notifications\PaymentCompleted;
use App\Notifications\PaymentFailedOrError;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Telegraph as TelegraphCore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TelegraphNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['telegraph.chat_id' => '123456789']);
        config(['telegraph.bot_token' => 'mock-bot-token']);
    }

    public function test_user_model_routing_for_telegraph()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->assertSame('123456789', $admin->routeNotificationForTelegraph(new OrderPlaced(new Order())));
        $this->assertNull($customer->routeNotificationForTelegraph(new OrderPlaced(new Order())));
    }

    public function test_notifications_via_channel_selection()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $order = Order::create([
            'user_id' => $customer->id,
            'email' => $customer->email,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'total_amount' => 150.00,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'paystack',
        ]);

        $notification = new OrderPlaced($order);

        $this->assertContains('telegraph', $notification->via($admin));
        $this->assertContains('database', $notification->via($admin));

        $this->assertNotContains('telegraph', $notification->via($customer));
        $this->assertContains('database', $notification->via($customer));
    }

    public function test_order_placed_sends_telegram_message()
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 999]],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $product = Product::factory()->create([
            'name' => 'Pro Analytics',
            'slug' => 'pro-analytics',
            'description' => 'Test Product',
            'price' => 150.00,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'email' => $customer->email,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'total_amount' => 150.00,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'paystack',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 150.00,
            'options' => ['frequency' => 'monthly', 'dataset' => 'all'],
        ]);

        Notification::send($admin, new OrderPlaced($order));

        Telegraph::assertSent("🔔 <b>New Order Placed!</b>\n\n<b>Order ID:</b> #{$order->id}\n<b>Customer:</b> John Doe\n<b>Amount:</b> R150.00\n<b>Products:</b> Pro Analytics\n<b>Billing:</b> Monthly - Dataset: ALL");
    }

    public function test_payment_completed_sends_telegram_message()
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 999]],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $order = Order::create([
            'user_id' => $customer->id,
            'email' => $customer->email,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'total_amount' => 150.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_reference' => 'ref-12345',
            'payment_method' => 'paystack',
        ]);

        Notification::send($admin, new PaymentCompleted($order));

        Telegraph::assertSent("✅ <b>Payment Received!</b>\n\n<b>Order ID:</b> #{$order->id}\n<b>Amount:</b> R150.00\n<b>Reference:</b> <code>ref-12345</code>\n\nPayment verified successfully.");
    }

    public function test_payment_failed_sends_telegram_message()
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 999]],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $order = Order::create([
            'user_id' => $customer->id,
            'email' => $customer->email,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'total_amount' => 150.00,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'paystack',
        ]);

        Notification::send($admin, new PaymentFailedOrError($order, 'Paystack Verification Failure', 'Insufficient funds'));

        Telegraph::assertSent("⚠️ <b>Payment Failed or Error!</b>\n\n<b>Error Type:</b> Paystack Verification Failure\n<b>Order ID:</b> #{$order->id}\n<b>Amount:</b> R150.00\n<b>Message:</b> <code>Insufficient funds</code>");
    }
}
