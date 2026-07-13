import { defineStore } from 'pinia';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

/**
 * Pinia store for managing cart state and actions.
 */
export const useCartStore = defineStore('cart', {
    state: () => ({
        items: [],
        summary: {
            subtotal: 0,
            discount: 0,
            total: 0,
        },
        loading: false,
    }),

    getters: {
        itemCount: (state) => state.items.reduce((acc, item) => acc + item.quantity, 0),
        isEmpty: (state) => state.items.length === 0,
        hasDiscount: (state) => state.summary.discount > 0,
    },

    actions: {
        /**
         * Update local state with data from Inertia props.
         * Useful for initial load and after mutations.
         */
        updateFromProps(cart, summary) {
            this.items = cart.items;
            this.summary = summary;
        },

        /**
         * Add an item to the cart via Inertia.
         */
        async addItem(productId, quantity = 1, options = {}) {
            this.loading = true;
            router.post(route('cart.store'), {
                product_id: productId,
                quantity,
                options,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.loading = false;
                    toast.success('Added to cart');
                },
                onError: () => {
                    this.loading = false;
                    toast.error('Failed to add item');
                }
            });
        },

        /**
         * Update item quantity with optimistic UI update.
         */
        updateQuantity(itemId, quantity) {
            if (quantity < 1) {
                this.removeItem(itemId);
                return;
            }

            // Optimistic update
            const item = this.items.find(i => i.id === itemId);
            if (item) item.quantity = quantity;

            router.patch(route('cart.update', itemId), { quantity }, {
                preserveScroll: true,
                onSuccess: () => toast.success('Quantity updated'),
            });
        },

        /**
         * Remove an item from the cart.
         */
        removeItem(itemId) {
            router.delete(route('cart.destroy', itemId), {
                preserveScroll: true,
                onSuccess: () => toast.success('Item removed'),
            });
        },

        /**
         * Clear the entire cart.
         */
        clearCart() {
            router.delete(route('cart.clear'), {
                preserveScroll: true,
                onSuccess: () => toast.success('Cart cleared'),
            });
        },

        /**
         * Apply a discount code.
         */
        applyDiscount(code) {
            router.post(route('cart.discount.apply'), { code }, {
                preserveScroll: true,
                onSuccess: () => toast.success('Discount applied'),
                onError: (errors) => toast.error(errors.code || 'Invalid discount'),
            });
        },

        /**
         * Remove currently applied discount.
         */
        removeDiscount() {
            router.delete(route('cart.discount.remove'), {
                preserveScroll: true,
                onSuccess: () => toast.success('Discount removed'),
            });
        }
    }
});
