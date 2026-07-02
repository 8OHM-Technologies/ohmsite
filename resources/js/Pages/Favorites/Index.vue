<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import axios from 'axios';
import { useCartStore } from '@/Stores/useCartStore';

const props = defineProps({
    favorites: Array,
    auth: Object,
});

const cartStore = useCartStore();

const removeFavorite = async (productId) => {
    try {
        await axios.post(route('favorites.toggle', productId));
        router.reload({ only: ['favorites'] });
    } catch (error) {
        console.error('Error removing favorite:', error);
    }
};
</script>

<template>
    <MainLayout :auth="auth" title="My Wishlist">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-16 gap-6">
                <div>
                    <h1 class="text-7xl font-black uppercase italic tracking-tighter text-white leading-none">My Wishlist</h1>
                    <p class="text-zinc-500 font-black uppercase tracking-widest text-xs mt-4">{{ favorites.length }} Items Saved</p>
                </div>
                <Link 
                    :href="route('shop.index')" 
                    class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition-colors underline underline-offset-8"
                >
                    Back to Shop
                </Link>
            </div>

            <div v-if="favorites.length === 0" class="bg-zinc-900/50 rounded-[3rem] border border-dashed border-white/5 p-24 text-center">
                <div class="mb-10 opacity-20">
                    <svg class="w-32 h-32 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <h3 class="text-4xl font-black text-white uppercase italic tracking-tighter mb-4">Your wishlist is empty</h3>
                <p class="text-zinc-500 mb-12 font-bold uppercase tracking-widest text-xs italic">Browse our collection and save the styles you love!</p>
                <Link 
                    :href="route('shop.index')" 
                    class="inline-block bg-white text-black px-12 py-5 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-zinc-200 transition-all shadow-2xl active:scale-95"
                >
                    Go Shopping
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                <div v-for="product in favorites" :key="product.id" class="group relative space-y-8">
                    <!-- Favorite Button (Remove) -->
                    <button 
                        @click="removeFavorite(product.id)"
                        class="absolute top-6 right-6 w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-rose-500 shadow-2xl hover:bg-rose-500 hover:text-white transition-all z-20 group-hover:scale-110 active:scale-90"
                    >
                        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>

                    <Link :href="route('shop.show', product.id)" class="block">
                        <div class="aspect-square bg-zinc-900 rounded-[3rem] p-12 flex items-center justify-center relative overflow-hidden border border-white/5 group-hover:border-white/20 transition-all duration-700 shadow-2xl">
                            <img src="/assets/images/popular-bg.png" class="absolute inset-0 w-full h-full object-cover opacity-5 group-hover:opacity-20 transition-all duration-700 scale-150 group-hover:rotate-12" />
                            <img :src="product.image" :alt="product.name" class="h-44 object-contain relative z-10 transition-all duration-1000 group-hover:scale-110 group-hover:-rotate-12 drop-shadow-[0_20px_20px_rgba(0,0,0,0.5)]" />
                        </div>
                        
                        <div class="px-4 mt-8 space-y-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-black text-2xl text-white italic uppercase tracking-tighter leading-tight group-hover:text-white transition-colors">{{ product.name }}</h4>
                                    <p class="text-[10px] font-black text-zinc-500 uppercase tracking-[0.3em] mt-1">{{ product.brands?.[0]?.name }}</p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="text-2xl font-black italic tracking-tighter text-white">€{{ Math.round(product.sale_price || product.price) }}</span>
                                    <span v-if="product.sale_price" class="text-[10px] text-zinc-700 line-through font-bold">€{{ Math.round(product.price) }}</span>
                                </div>
                            </div>
                        </div>
                    </Link>
                    
                    <Link 
                        :href="route('shop.show', product.id)"
                        class="w-full bg-white text-black py-5 rounded-[1.5rem] flex items-center justify-center font-black uppercase tracking-widest text-xs hover:bg-admin-modern transition-all shadow-xl opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 duration-500 active:scale-95"
                    >
                        View Product
                    </Link>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
