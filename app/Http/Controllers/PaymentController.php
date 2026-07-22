<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\PaymentCompleted;
use App\Notifications\PaymentFailedOrError;
use Binkode\Paystack\Support\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Step 1: Initialize checkout and redirect to Paystack
     */
    public function checkout(Order $order)
    {
        $order->load('items.product');
        $hasSubscription = false;
        $planCode = null;

        foreach ($order->items as $item) {
            $product = $item->product;
            if (in_array($product->slug, ['developer-api', 'pro-analytics', 'managed-data-pipeline'])) {
                $hasSubscription = true;
                $frequency = $item->options['frequency'] ?? 'monthly';
                $planCode = config("paystack.plans.{$product->slug}.{$frequency}");
                break;
            }
        }

        $params = [
            'email' => $order->email ?? auth()->user()->email,
            'amount' => (int) ($order->total_amount * 100), // convert to cents (kobo)
            'reference' => 'ORD-'.$order->id.'-'.time(),
            'callback_url' => route('payment.callback'),
            'metadata' => [
                'order_id' => $order->id,
                'has_subscription' => $hasSubscription,
                'plan_code' => $planCode,
            ],
        ];

        if ($hasSubscription && $planCode) {
            $params['plan'] = $planCode;
        }

        try {
            $response = Transaction::initialize($params);
        } catch (\Throwable $e) {
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new PaymentFailedOrError($order, 'Paystack Initialization Failure', $e->getMessage()));

            return redirect()->route('orders.index')->with('error', 'Unable to initialize transaction with Paystack: '.$e->getMessage());
        }

        if (isset($response['status']) && $response['status'] === true) {
            // Save reference to the order
            $order->update([
                'payment_reference' => $response['data']['reference'],
                'status' => 'pending',
            ]);

            // Redirect user to the Paystack checkout page
            return Inertia::location($response['data']['authorization_url']);
        }

        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new PaymentFailedOrError($order, 'Paystack Initialization Failure', 'Paystack API returned false status.'));

        return redirect()->route('orders.index')->with('error', 'Unable to initialize transaction with Paystack.');
    }

    /**
     * Step 2: Handle user redirection back from Paystack (Callback)
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('orders.index')->with('error', 'No reference returned.');
        }

        try {
            $response = Transaction::verify($reference);
        } catch (\Throwable $e) {
            $order = Order::where('payment_reference', $reference)->first();
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new PaymentFailedOrError($order, 'Paystack Verification Failure', $e->getMessage()));

            return redirect()->route('orders.index')->with('error', 'Payment verification failed: '.$e->getMessage());
        }

        if (isset($response['data']['status']) && $response['data']['status'] === 'success') {
            $order = Order::where('payment_reference', $reference)->firstOrFail();

            // Avoid double processing (idempotency check)
            if ($order->status !== 'completed') {
                $order->update([
                    'status' => 'completed',
                    'payment_status' => 'paid',
                ]);

                // Update user subscription details if applicable
                $user = $order->user;
                if ($user) {
                    $order->load('items.product');
                    foreach ($order->items as $item) {
                        $product = $item->product;
                        if (in_array($product->slug, ['developer-api', 'pro-analytics', 'managed-data-pipeline'])) {
                            $user->update([
                                'subscription_status' => 'active',
                                'subscribed_at' => now(),
                                'subscription_code' => $response['data']['subscription'] ?? 'sub_mock_'.time(),
                            ]);
                        }
                    }
                }

                // Notify Admins
                $admins = User::where('role', 'admin')->get();
                Notification::send($admins, new PaymentCompleted($order));
            }

            return redirect()->route('orders.index')->with('success', 'Payment successful!');
        }

        $order = Order::where('payment_reference', $reference)->first();
        $admins = User::where('role', 'admin')->get();
        $msg = $response['message'] ?? ($response['data']['gateway_response'] ?? 'Payment verification failed.');
        Notification::send($admins, new PaymentFailedOrError($order, 'Paystack Verification Failure', $msg));

        return redirect()->route('orders.index')->with('error', 'Payment verification failed.');
    }
}
