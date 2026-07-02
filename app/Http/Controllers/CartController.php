<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing the shopping cart.
 */
class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the cart page.
     */
    public function index(): Response
    {
        $cart = $this->cartService->getCart();
        
        return Inertia::render('Cart/Index', [
            'cart' => new CartResource($cart),
            'summary' => $this->cartService->getSummary(),
        ]);
    }

    /**
     * Add an item to the cart.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'options' => 'nullable|array',
        ]);

        $this->cartService->addItem(
            $request->product_id,
            $request->quantity,
            $request->options ?? []
        );

        return back()->with('success', 'Item added to cart!');
    }

    /**
     * Update item quantity.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $this->cartService->updateItemQuantity($id, $request->quantity);

        return back()->with('success', 'Cart updated!');
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy($id)
    {
        $this->cartService->removeItem($id);

        return back()->with('success', 'Item removed from cart!');
    }

    /**
     * Clear the entire cart.
     */
    public function clear()
    {
        $this->cartService->clearCart();

        return back()->with('success', 'Cart cleared!');
    }
}
