<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderPlaced;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

use App\Http\Requests\StoreOrderRequest;

class CheckoutController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the checkout page.
     */
    public function index(): Response
    {
        $cart = $this->cartService->getCart();
        
        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return Inertia::render('Checkout/Index', [
            'cart' => new CartResource($cart),
            'summary' => $this->cartService->getSummary(),
            'auth' => [
                'user' => Auth::user(),
            ],
        ]);
    }

    /**
     * Process the checkout.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'apartment' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
        ]);

        $cart = $this->cartService->getCart();
        $summary = $this->cartService->getSummary();

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => Auth::id(),
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'address' => $request->address,
                'apartment' => $request->apartment,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'phone' => $request->phone,
                'total_amount' => $summary['total'],
                'shipping_fee' => $summary['shipping'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => 'cash',
                'shipping_address' => $this->formatShippingAddress($request->all()),
            ]);

            foreach ($cart->items as $item) {
                // Lock the product for update to prevent race conditions during stock decrement
                $product = \App\Models\Product::where('id', $item->product_id)->lockForUpdate()->first();

                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'options' => json_encode($item->options),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Decrement product stock
                $product->decrement('stock', $item->quantity);
            }

            $this->cartService->clearCart();

            // Notify Admins
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new OrderPlaced($order));

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Order placed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    private function formatShippingAddress(array $data): string
    {
        return "{$data['address']}, {$data['apartment']}, {$data['city']}, {$data['postal_code']}, {$data['country']}";
    }
}
