<script setup>
import { onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Toaster } from 'vue-sonner';
import { useCartStore } from '@/Stores/useCartStore';
import MainLayout from '@/Layouts/MainLayout.vue';
import CartItem from '@/Components/Cart/CartItem.vue';
import CartSummary from '@/Components/Cart/CartSummary.vue';
import EmptyCart from '@/Components/Cart/EmptyCart.vue';
import SavedItems from '@/Components/Cart/SavedItems.vue';
import { ChevronLeft, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    cart: Object,
    summary: Object,
    auth: Object,
});

const cartStore = useCartStore();

// Sync props with Pinia store
onMounted(() => {
    cartStore.updateFromProps(props.cart.data, props.summary);
});

// Watch for prop changes (from Inertia navigations/reloads)
watch(() => props.cart.data, (newData) => {
    cartStore.items = newData.items;
}, { deep: true });

watch(() => props.summary, (newSummary) => {
    cartStore.summary = newSummary;
}, { deep: true });
</script>

<template>
    <MainLayout :auth="auth" title="Shopping Cart">
        <Toaster position="top-right" richColors />

        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-10 space-y-4 sm:space-y-0">
                <div>
                    <Link 
                        :href="route('shop.index')" 
                        class="inline-flex items-center text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition-colors mb-2"
                    >
                        <ChevronLeft class="w-4 h-4 mr-1" />
                        Continue Shopping
                    </Link>
                    <h1 class="text-6xl font-black uppercase italic tracking-tighter text-white">
                        Shopping Cart 
                        <span v-if="cartStore.itemCount > 0" class="text-zinc-500">({{ cartStore.itemCount }})</span>
                    </h1>
                </div>
                
                <button 
                    v-if="!cartStore.isEmpty"
                    @click="cartStore.clearCart"
                    class="inline-flex items-center text-[10px] font-black uppercase tracking-widest text-rose-500 hover:text-rose-400 transition-colors"
                >
                    <Trash2 class="w-4 h-4 mr-2" />
                    Clear Cart
                </button>
            </div>

            <div v-if="cartStore.isEmpty" class="bg-zinc-900/50 rounded-[3rem] border border-dashed border-white/5 p-12">
                <EmptyCart />
            </div>

            <div v-else class="lg:grid lg:grid-cols-12 lg:gap-x-12 lg:items-start">
                <!-- Cart Items List -->
                <div class="lg:col-span-8 space-y-2">
                    <CartItem 
                        v-for="item in cartStore.items" 
                        :key="item.id" 
                        :item="item"
                        @update:quantity="(qty) => cartStore.updateQuantity(item.id, qty)"
                        @remove="cartStore.removeItem(item.id)"
                        @toggle-favorite="() => {}"
                    />
                    
                    <!-- Extra Info -->
                    <div class="mt-10 p-8 bg-zinc-900 rounded-[2.5rem] border border-white/5 flex items-start space-x-6">
                        <div class="p-3 bg-zinc-800 rounded-2xl">
                            🚚
                        </div>
                        <div>
                            <h4 class="text-sm font-black uppercase tracking-widest text-white">Fast & Free Shipping</h4>
                            <p class="text-sm text-zinc-500 font-bold uppercase italic mt-1">Free shipping on orders over €150. Standard delivery in 2-4 business days.</p>
                        </div>
                    </div>
                </div>

                <!-- Summary Sidebar -->
                <div class="mt-16 lg:mt-0 lg:col-span-4">
                    <CartSummary 
                        :summary="cartStore.summary"
                        @apply-coupon="cartStore.applyCoupon"
                        @remove-coupon="cartStore.removeCoupon"
                    />
                </div>
            </div>

            <!-- Saved Items Section (Optional/Placeholder) -->
            <SavedItems :items="[]" />
        </div>
    </MainLayout>
</template>

<style scoped>
.animate-in {
    animation-fill-mode: forwards;
}
</style>
