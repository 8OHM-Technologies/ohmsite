<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;

/**
 * Controller for handling discount operations in the cart.
 */
class DiscountController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Apply a discount code to the current cart.
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:discounts,code',
        ]);

        $applied = $this->cartService->applyDiscount($request->code);

        if (!$applied) {
            return back()->withErrors(['code' => 'Invalid or expired discount code, or minimum order not met.']);
        }

        return back()->with('success', 'Discount applied successfully!');
    }

    /**
     * Remove the currently applied discount.
     */
    public function remove()
    {
        $this->cartService->removeDiscount();

        return back()->with('success', 'Discount removed.');
    }
}
