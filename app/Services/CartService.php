<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Service class for handling shopping cart logic.
 */
class CartService
{
    /**
     * Get the current cart for the authenticated user or guest session.
     */
    public function getCart(): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $sessionId = Session::getId();
        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(int $productId, int $quantity = 1, array $options = []): CartItem
    {
        $cart = $this->getCart();
        $product = Product::findOrFail($productId);

        $item = $cart->items()->where('product_id', $productId)
            ->where('options', json_encode($options))
            ->first();

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            $item = $cart->items()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $product->sale_price ?? $product->price,
                'options' => $options,
            ]);
        }

        return $item;
    }

    /**
     * Update item quantity.
     */
    public function updateItemQuantity(int $itemId, int $quantity): bool
    {
        $item = CartItem::findOrFail($itemId);
        
        if ($quantity <= 0) {
            return $item->delete();
        }

        return $item->update(['quantity' => $quantity]);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $itemId): bool
    {
        return CartItem::destroy($itemId) > 0;
    }

    /**
     * Clear the entire cart.
     */
    public function clearCart(): bool
    {
        $cart = $this->getCart();
        return $cart->items()->delete() >= 0;
    }

    /**
     * Apply a coupon to the cart.
     */
    public function applyCoupon(string $code): bool
    {
        $coupon = Coupon::where('code', $code)->first();
        
        if (!$coupon || !$coupon->isValid()) {
            return false;
        }

        $cart = $this->getCart();
        $subtotal = $this->calculateSubtotal($cart);

        if ($subtotal < $coupon->min_order) {
            return false;
        }

        $cart->update(['coupon_id' => $coupon->id]);
        return true;
    }

    /**
     * Remove coupon from cart.
     */
    public function removeCoupon(): bool
    {
        $cart = $this->getCart();
        return $cart->update(['coupon_id' => null]);
    }

    /**
     * Calculate cart totals.
     */
    public function getSummary(): array
    {
        $cart = $this->getCart();
        $subtotal = $this->calculateSubtotal($cart);
        $discount = $this->calculateDiscount($cart, $subtotal);
        $shipping = $this->calculateShipping($subtotal - $discount);
        $total = $subtotal - $discount + $shipping;

        $freeShippingThreshold = config('shop.shipping.free_threshold');

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping' => round($shipping, 2),
            'total' => round($total, 2),
            'coupon' => $cart->coupon ? [
                'code' => $cart->coupon->code,
                'type' => $cart->coupon->type,
                'value' => $cart->coupon->value,
            ] : null,
            'free_shipping_progress' => min(100, ($subtotal / $freeShippingThreshold) * 100),
            'free_shipping_threshold' => $freeShippingThreshold,
        ];
    }

    private function calculateSubtotal(Cart $cart): float
    {
        return $cart->items->sum(fn($item) => $item->unit_price * $item->quantity);
    }

    private function calculateDiscount(Cart $cart, float $subtotal): float
    {
        if (!$cart->coupon) return 0;

        if ($cart->coupon->type === 'percentage') {
            return $subtotal * ($cart->coupon->value / 100);
        }

        return min($subtotal, $cart->coupon->value);
    }

    private function calculateShipping(float $amount, ?float $threshold = null): float
    {
        $threshold = $threshold ?? config('shop.shipping.free_threshold');
        return $amount >= $threshold ? 0 : config('shop.shipping.cost');
    }
}
