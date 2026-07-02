<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;

/**
 * Controller for handling coupon operations in the cart.
 */
class CouponController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Apply a coupon code to the current cart.
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:coupons,code',
        ]);

        $applied = $this->cartService->applyCoupon($request->code);

        if (!$applied) {
            return back()->withErrors(['code' => 'Invalid or expired coupon code, or minimum order not met.']);
        }

        return back()->with('success', 'Coupon applied successfully!');
    }

    /**
     * Remove the currently applied coupon.
     */
    public function remove()
    {
        $this->cartService->removeCoupon();

        return back()->with('success', 'Coupon removed.');
    }
}
