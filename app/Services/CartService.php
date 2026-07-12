<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Discount;
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

        $item = $cart->items()->where('product_id', $productId)->get()->first(function ($item) use ($options) {
            return $item->options === $options;
        });

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            $item = $cart->items()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $this->resolveUnitPrice($product, $options),
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
     * Apply a discount to the cart.
     */
    public function applyDiscount(string $code): bool
    {
        $discount = Discount::where('code', $code)->first();

        if (! $discount || ! $discount->isValid()) {
            return false;
        }

        $cart = $this->getCart();
        $subtotal = $this->calculateSubtotal($cart);

        if ($subtotal < $discount->min_order) {
            return false;
        }

        $cart->update(['discount_id' => $discount->id]);

        return true;
    }

    /**
     * Remove discount from cart.
     */
    public function removeDiscount(): bool
    {
        $cart = $this->getCart();

        return $cart->update(['discount_id' => null]);
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
            'discount' => $cart->discount ? [
                'code' => $cart->discount->code,
                'type' => $cart->discount->type,
                'value' => $cart->discount->value,
            ] : null,
            'free_shipping_progress' => min(100, ($subtotal / $freeShippingThreshold) * 100),
            'free_shipping_threshold' => $freeShippingThreshold,
        ];
    }

    private function calculateSubtotal(Cart $cart): float
    {
        return $cart->items->sum(fn ($item) => $item->unit_price * $item->quantity);
    }

    private function calculateDiscount(Cart $cart, float $subtotal): float
    {
        if (! $cart->discount) {
            return 0;
        }

        if ($cart->discount->type === 'percentage') {
            return $subtotal * ($cart->discount->value / 100);
        }

        return min($subtotal, $cart->discount->value);
    }

    private function calculateShipping(float $amount, ?float $threshold = null): float
    {
        $threshold = $threshold ?? config('shop.shipping.free_threshold');

        return $amount >= $threshold ? 0 : config('shop.shipping.cost');
    }

    /**
     * Resolve the unit price of a product dynamically based on its options.
     */
    private function resolveUnitPrice(Product $product, array $options): float
    {
        // Once-off Dataset
        if ($product->name === 'Once-off Dataset') {
            $dataset = $options['dataset'] ?? 'ccma';
            if ($dataset === 'all') {
                return 9000.00;
            }

            return 5000.00;
        }

        // Developer API
        if ($product->name === 'Developer API') {
            $frequency = $options['frequency'] ?? 'monthly';
            $dataset = $options['dataset'] ?? 'ccma';

            if ($frequency === 'monthly') {
                return $dataset === 'all' ? 480.00 : 380.00;
            } else { // annually
                return $dataset === 'all' ? 4800.00 : 3800.00;
            }
        }

        // Analytics Dashboard
        if ($product->name === 'Analytics Dashboard') {
            $frequency = $options['frequency'] ?? 'monthly';

            return $frequency === 'monthly' ? 3800.00 : 38000.00;
        }

        return $product->sale_price ?? $product->price;
    }
}
