<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
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

const orb1 = ref(null);
const orb2 = ref(null);
const orb3 = ref(null);

const handleMouseMove = (e) => {
    const x = e.clientX / window.innerWidth;
    const y = e.clientY / window.innerHeight;

    const list = [orb1.value, orb2.value, orb3.value];
    list.forEach((orb, index) => {
        if (!orb) return;
        const speed = (index + 1) * 20;
        const dx = (x - 0.5) * speed;
        const dy = (y - 0.5) * speed;
        orb.style.transform = `translate(${dx}px, ${dy}px)`;
    });
};

onMounted(() => {
    window.addEventListener('mousemove', handleMouseMove);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
});
</script>

<template>
    <MainLayout :auth="auth" title="Shopping Cart">
        <Toaster position="top-right" richColors />

        <!-- Background Visuals -->
        <div class="background-visuals">
            <div class="glow-orb orb-1" ref="orb1"></div>
            <div class="glow-orb orb-2" ref="orb2"></div>
            <div class="glow-orb orb-3" ref="orb3"></div>
            <div class="grid-overlay"></div>
            <div class="noise-overlay"></div>
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-10 space-y-4 sm:space-y-0">
                <div>
                    <Link href="/#services"
                        class="inline-flex items-center text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition-colors mb-2">
                        <ChevronLeft class="w-4 h-4 mr-1" />
                        Go back
                    </Link>
                    <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                        Shopping Cart
                        <span v-if="cartStore.itemCount > 0" class="text-zinc-500">({{ cartStore.itemCount }})</span>
                    </h1>
                </div>

                <button v-if="!cartStore.isEmpty" @click="cartStore.clearCart"
                    class="inline-flex items-center text-[10px] font-black uppercase tracking-widest text-rose-500 hover:text-rose-400 transition-colors">
                    <Trash2 class="w-4 h-4 mr-2" />
                    Clear Cart
                </button>
            </div>

            <div v-if="cartStore.isEmpty"
                class="bg-zinc-900/50 rounded-[3rem] border border-dashed border-white/5 p-12">
                <EmptyCart />
            </div>

            <div v-else class="lg:grid lg:grid-cols-12 lg:gap-x-12 lg:items-start">
                <!-- Cart Items List -->
                <div class="lg:col-span-8 space-y-2">
                    <CartItem v-for="item in cartStore.items" :key="item.id" :item="item"
                        @update:quantity="(qty) => cartStore.updateQuantity(item.id, qty)"
                        @remove="cartStore.removeItem(item.id)" @toggle-favorite="() => { }" />

                    <!-- Extra Info -->
                    <div
                        class="mt-10 p-8 bg-zinc-900 rounded-[2.5rem] border border-white/5 flex items-start space-x-6">
                        <div class="p-3 bg-zinc-800 rounded-2xl">
                            🚚
                        </div>
                        <div>
                            <h4 class="text-sm font-black uppercase tracking-widest text-white">Fast & Free Shipping
                            </h4>
                            <p class="text-sm text-zinc-500 font-bold uppercase mt-1">Free shipping on orders over €150.
                                Standard delivery in 2-4 business days.</p>
                        </div>
                    </div>
                </div>

                <!-- Summary Sidebar -->
                <div class="mt-16 lg:mt-0 lg:col-span-4">
                    <CartSummary :summary="cartStore.summary" @apply-coupon="cartStore.applyCoupon"
                        @remove-coupon="cartStore.removeCoupon" />
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

/* --- Background Visuals --- */
.background-visuals {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.glow-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(130px);
  opacity: 0.12;
  mix-blend-mode: screen;
  will-change: transform;
  transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.orb-1 {
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, var(--color-accent-primary) 0%, rgba(255, 136, 0, 0) 70%);
  top: -15%;
  left: -10%;
}

.orb-2 {
  width: 700px;
  height: 700px;
  background: radial-gradient(circle, var(--color-accent-secondary) 0%, rgba(141, 215, 218, 0) 70%);
  bottom: -15%;
  right: -10%;
}

.orb-3 {
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, #ff3c00 0%, rgba(255, 60, 0, 0) 70%);
  top: 40%;
  left: 35%;
}

.grid-overlay {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(to right, rgba(255, 255, 255, 0.015) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
  background-size: 64px 64px;
  mask-image: radial-gradient(circle at center, black 40%, transparent 95%);
  -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 95%);
}

.noise-overlay {
  position: absolute;
  inset: 0;
  opacity: 0.015;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
}
</style>
