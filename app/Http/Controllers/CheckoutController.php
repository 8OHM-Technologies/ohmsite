<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartResource;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderPlaced;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

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
    public function index(): Response|RedirectResponse
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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'country' => 'nullable|string|max:100',
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
                'country' => $request->country,
                'phone' => $request->phone,
                'total_amount' => $summary['total'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => 'paystack',
            ]);

            foreach ($cart->items as $item) {
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'options' => json_encode($item->options),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->cartService->clearCart();

            // Notify Admins
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new OrderPlaced($order));

            DB::commit();

            if (Auth::check()) {
                return redirect()->route('orders.index')->with('success', 'Order placed successfully!');
            }

            return redirect()->route('home')->with('success', 'Order placed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }
}
