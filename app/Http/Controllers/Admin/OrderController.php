<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        // Search by ID or Customer Name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'payment_status']),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string',
            'payment_status' => 'nullable|string',
        ]);

        $oldPaymentStatus = $order->payment_status;
        $order->update($request->only(['status', 'payment_status']));

        if ($order->payment_status === 'paid' && $oldPaymentStatus !== 'paid') {
            $user = $order->user;
            if ($user) {
                $order->load('items.product');
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if (in_array($product->slug, ['developer-api', 'pro-analytics', 'managed-data-pipeline'])) {
                        $user->update([
                            'subscription_status' => 'active',
                            'subscribed_at' => now(),
                            'subscription_code' => $user->subscription_code ?? 'sub_manual_' . time(),
                        ]);
                    }
                }
            }
        } elseif ($order->payment_status !== 'paid' && $oldPaymentStatus === 'paid') {
            $user = $order->user;
            if ($user) {
                $order->load('items.product');
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if (in_array($product->slug, ['developer-api', 'pro-analytics', 'managed-data-pipeline'])) {
                        $hasOtherSub = $user->orders()
                            ->where('id', '!=', $order->id)
                            ->where('payment_status', 'paid')
                            ->whereHas('items.product', function ($query) use ($product) {
                                $query->where('slug', $product->slug);
                            })
                            ->exists();

                        if (! $hasOtherSub) {
                            $user->update([
                                'subscription_status' => null,
                                'subscribed_at' => null,
                                'subscription_code' => null,
                            ]);
                        }
                    }
                }
            }
        }

        return back()->with('success', 'Order updated successfully.');
    }
}
