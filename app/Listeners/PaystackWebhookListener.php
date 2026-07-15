<?php

namespace App\Listeners;

use App\Models\Order;
use Binkode\Paystack\Events\Hook;
use Illuminate\Support\Facades\Log;

class PaystackWebhookListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Hook $event): void
    {
        $payload = $event->event;
        $eventType = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        Log::info("Paystack webhook received: {$eventType}");

        if ($eventType === 'charge.success') {
            $reference = $data['reference'] ?? null;
            if ($reference) {
                $order = Order::where('payment_reference', $reference)->first();
                if ($order && $order->status !== 'completed') {
                    $order->update([
                        'status' => 'completed',
                        'payment_status' => 'paid',
                    ]);

                    $user = $order->user;
                    if ($user) {
                        $order->load('items.product');
                        foreach ($order->items as $item) {
                            $product = $item->product;
                            if (in_array($product->slug, ['developer-api', 'pro-analytics', 'managed-data-pipeline'])) {
                                $user->update([
                                    'subscription_status' => 'active',
                                    'subscribed_at' => now(),
                                    'subscription_code' => $data['subscription'] ?? 'sub_mock_'.time(),
                                ]);
                            }
                        }
                    }

                    // Notify Admins
                    $admins = \App\Models\User::where('role', 'admin')->get();
                    \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\PaymentCompleted($order));

                    Log::info("Order #{$order->id} marked as completed via webhook.");
                }
            }
        }

        if ($eventType === 'charge.failed') {
            $reference = $data['reference'] ?? null;
            $order = $reference ? Order::where('payment_reference', $reference)->first() : null;
            $admins = \App\Models\User::where('role', 'admin')->get();
            $msg = $data['gateway_response'] ?? 'Charge failed';
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\PaymentFailedOrError($order, 'Paystack Charge Failed Webhook', $msg));
        }
    }
}
