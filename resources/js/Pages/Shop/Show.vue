<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import MainLayout from '@/Layouts/MainLayout.vue';
import { useCartStore } from '@/Stores/useCartStore';

const props = defineProps({
    product: Object,
    relatedProducts: Array,
    auth: Object
});

const cartStore = useCartStore();

const activeImage = ref(props.product.image);
const selectedColor = ref(props.product.colors?.[0] || '');
const selectedSize = ref('');

const allImages = computed(() => {
    const images = [props.product.image];
    if (props.product.images && Array.isArray(props.product.images)) {
        images.push(...props.product.images);
    }
    return images.slice(0, 5);
});

const sortedSizes = computed(() => {
    if (!props.product.sizes) return [];
    return [...props.product.sizes].sort((a, b) => parseFloat(a) - parseFloat(b));
});

const toggleFavorite = () => {
    if (!props.auth.user) {
        router.visit(route('login'));
        return;
    }

    router.post(route('favorites.toggle', props.product.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Updated by reload
        }
    });
};

const addToCart = () => {
    if (props.product.sizes?.length && !selectedSize.value) {
        alert('Please select a size');
        return;
    }

    cartStore.addItem(props.product.id, 1, {
        size: selectedSize.value,
        color: selectedColor.value
    });
};

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
    <MainLayout :auth="auth" :title="product.name">
        <!-- Background Visuals -->
        <div class="background-visuals">
            <div class="glow-orb orb-1" ref="orb1"></div>
            <div class="glow-orb orb-2" ref="orb2"></div>
            <div class="glow-orb orb-3" ref="orb3"></div>
            <div class="grid-overlay"></div>
            <div class="noise-overlay"></div>
        </div>

        <div class="relative z-10">
            <!-- Breadcrumbs -->
            <div
                class="flex items-center space-x-2 text-[10px] font-black uppercase tracking-widest text-zinc-600 mb-10">
                <Link :href="route('shop.index')" class="hover:text-white transition-colors">Shop</Link>
                <span>/</span>
                <span class="text-zinc-400">{{ product.category?.name }}</span>
                <span>/</span>
                <span class="text-white">{{ product.name }}</span>
            </div>

            <div class="flex flex-col lg:flex-row gap-20">
                <!-- Gallery Section -->
                <div class="flex-1 flex gap-6 h-[700px]">
                    <!-- Vertical Thumbnails -->
                    <div class="w-24 space-y-4">
                        <button v-for="(img, idx) in allImages" :key="idx" @click="activeImage = img"
                            :class="activeImage === img ? 'border-white' : 'border-white/5 hover:border-white/20'"
                            class="w-full aspect-square bg-zinc-900 rounded-2xl border p-2 transition-all flex items-center justify-center overflow-hidden">
                            <img :src="img" class="max-h-full object-contain" />
                        </button>
                    </div>

                    <!-- Large Image -->
                    <div
                        class="flex-1 bg-zinc-900 rounded-[3rem] border border-white/5 flex items-center justify-center relative overflow-hidden group shadow-[0_50px_100px_-20px_rgba(0,0,0,0.5)]">
                        <img src="/assets/images/popular-bg.png"
                            class="absolute inset-0 w-full h-full object-cover opacity-10 group-hover:opacity-30 transition-all duration-1000 scale-150 group-hover:rotate-6" />
                        <img :src="activeImage"
                            class="max-h-[500px] object-contain relative z-10 drop-shadow-[0_30px_30px_rgba(0,0,0,0.7)] transform -rotate-[5deg] group-hover:rotate-0 transition-all duration-1000" />
                    </div>
                </div>

                <!-- Info Section -->
                <div class="w-full lg:w-[500px] space-y-12">
                    <div class="space-y-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <h4 v-for="brand in product.brands" :key="brand.id"
                                        class="text-zinc-500 font-black uppercase tracking-[0.3em] text-[10px]">{{
                                            brand.name }}</h4>
                                </div>
                                <h1 class="text-4xl font-black uppercase tracking-tighter leading-none">{{ product.name
                                    }}</h1>
                            </div>
                            <button @click="toggleFavorite"
                                :class="product.is_favorited ? 'text-red-500 scale-110' : 'text-zinc-700 hover:text-white'"
                                class="p-4 bg-zinc-900/50 rounded-2xl border border-white/5 transition-all">
                                <svg class="w-8 h-8" :fill="product.is_favorited ? 'currentColor' : 'none'"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center space-x-6 pt-4">
                            <span class="text-4xl font-black tracking-tighter">R{{ product.sale_price || product.price
                                }}</span>
                            <template
                                v-if="product.sale_price && parseFloat(product.sale_price) < parseFloat(product.price)">
                                <span class="text-2xl text-zinc-700 line-through font-black tracking-tighter">R{{
                                    product.price }}</span>
                                <span
                                    class="bg-white text-black px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest shadow-xl">
                                    -{{ Math.round((1 - parseFloat(product.sale_price) / parseFloat(product.price)) *
                                        100) }}% OFF
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="w-full h-px bg-white/5"></div>

                    <!-- Description -->
                    <div class="space-y-4">
                        <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500">About the product
                        </h5>
                        <p class="text-zinc-400 font-bold text-sm leading-relaxed uppercase">
                            {{ product.description || 'No description available for this product.' }}
                        </p>
                    </div>

                    <!-- Selectors -->
                    <div class="space-y-10">
                        <!-- Colors -->
                        <div v-if="product.colors?.length" class="space-y-4">
                            <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500">Color</h5>
                            <div class="flex gap-4">
                                <button v-for="color in product.colors" :key="color" @click="selectedColor = color"
                                    :class="selectedColor === color ? 'border-white scale-110 ring-4 ring-white/10' : 'border-white/5 hover:border-white/20'"
                                    class="w-10 h-10 rounded-full border-2 transition-all overflow-hidden shadow-inner flex items-center justify-center"
                                    :style="{ backgroundColor: color }" :title="color">
                                    <span v-if="!color.startsWith('#') && color.length > 5"
                                        class="text-[6px] font-black uppercase leading-tight">{{ color.substring(0, 3)
                                        }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Sizes -->
                        <div v-if="product.sizes?.length" class="space-y-4">
                            <div class="flex justify-between items-end">
                                <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500">Size</h5>
                                <button
                                    class="text-[10px] font-black uppercase tracking-widest text-zinc-700 hover:text-white underline">Size
                                    Guide</button>
                            </div>
                            <div class="grid grid-cols-5 gap-3">
                                <button v-for="size in sortedSizes" :key="size" @click="selectedSize = size"
                                    :class="selectedSize === size ? 'bg-white text-black' : 'bg-zinc-900 text-zinc-500 border border-white/5 hover:border-white/20'"
                                    class="py-4 rounded-xl text-xs font-black transition-all">
                                    {{ size }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-6">
                        <button @click="addToCart"
                            class="w-full bg-white text-black py-6 rounded-2xl font-black uppercase tracking-widest hover:bg-zinc-200 transition-all hover:scale-[1.02] active:scale-95 shadow-2xl">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <section class="mt-40 space-y-16">
                <div class="flex justify-between items-end">
                    <h2 class="text-4xl font-black uppercase tracking-tighter leading-none">You might also like</h2>
                    <Link :href="route('shop.index')"
                        class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition-colors">
                        See all products</Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
                    <div v-for="related in relatedProducts" :key="related.id"
                        class="group cursor-pointer relative space-y-6">
                        <Link :href="route('shop.show', related.id)" class="block">
                            <div
                                class="aspect-square bg-zinc-900 rounded-[2.5rem] p-10 flex items-center justify-center relative overflow-hidden border border-white/5 group-hover:border-white/20 transition-all duration-700 shadow-2xl">
                                <img src="/assets/images/popular-bg.png"
                                    class="absolute inset-0 w-full h-full object-cover opacity-5 group-hover:opacity-20 transition-all duration-700 scale-150 group-hover:rotate-12" />
                                <img :src="related.image"
                                    class="h-32 object-contain relative z-10 transition-all duration-1000 group-hover:scale-110 group-hover:-rotate-12 drop-shadow-[0_20px_20px_rgba(0,0,0,0.5)]" />
                            </div>
                            <div class="px-2 mt-6 space-y-1 text-center">
                                <h4 class="font-black text-lg text-white uppercase tracking-tighter">{{ related.name }}
                                </h4>
                                <p class="text-white/40 font-black text-base tracking-widest">R{{
                                    Math.round(related.sale_price ||
                                        related.price) }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </MainLayout>
</template>

<style scoped>
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
