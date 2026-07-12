<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import SubscriberLayout from '@/Layouts/SubscriberLayout.vue';
import {
    Package,
    Search,
    ChevronRight,
    Eye
} from 'lucide-vue-next';

const props = defineProps({
    products: Array
});
</script>

<template>

    <Head title="My Products" />

    <SubscriberLayout>
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 lg:mb-12 gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-2 h-8 bg-admin-modern rounded-full"></div>
                    <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tighter text-white">My Collection
                    </h1>
                </div>
                <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">Access and manage your premium
                    acquisitions</p>
            </div>
        </div>

        <!-- Inventory List -->
        <div class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden">
            <!-- Table Controls/Search (Optional placeholder) -->
            <div class="p-6 sm:p-8 border-b border-white/5 flex flex-col sm:flex-row justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" />
                    <input type="text" placeholder="Search collection..."
                        class="w-full bg-black/40 border border-white/5 rounded-xl py-3 pl-11 pr-4 text-xs font-bold text-white focus:ring-1 focus:ring-admin-modern/30" />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="px-4 py-3 bg-zinc-800/50 text-zinc-400 rounded-xl text-[10px] font-black uppercase tracking-widest border border-white/5 hover:text-white transition-all">Export
                        CSV</button>
                </div>
            </div>

            <div class="p-4 sm:p-8">
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left border-separate border-spacing-y-4">
                        <thead>
                            <tr>
                                <th class="pb-2 px-6 font-black uppercase text-[10px] tracking-[0.2em] text-zinc-600">
                                    Product Details</th>
                                <th class="pb-2 px-6 font-black uppercase text-[10px] tracking-[0.2em] text-zinc-600">
                                    Brand / Category</th>
                                <th class="pb-2 px-6 font-black uppercase text-[10px] tracking-[0.2em] text-zinc-600">
                                    Purchased For</th>
                                <th class="pb-2 px-6 font-black uppercase text-[10px] tracking-[0.2em] text-zinc-600">
                                    Access Status</th>
                                <th
                                    class="pb-2 px-6 font-black uppercase text-[10px] tracking-[0.2em] text-zinc-600 text-right">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products" :key="product.id" class="group">
                                <td
                                    class="py-4 px-6 bg-black/20 rounded-l-3xl border-y border-l border-white/5 group-hover:bg-zinc-800/40 transition-all duration-300">
                                    <div class="flex items-center gap-5">
                                        <div
                                            class="w-16 h-16 rounded-2xl bg-zinc-900 border border-white/5 p-2 flex items-center justify-center overflow-hidden shrink-0">
                                            <img :src="product.image" :alt="product.name"
                                                class="w-full h-full object-contain group-hover:scale-110 group-hover:-rotate-6 transition-all duration-700" />
                                        </div>
                                        <div>
                                            <p
                                                class="font-black text-white uppercase tracking-tight leading-none mb-1.5 group-hover:text-admin-modern transition-colors">
                                                {{ product.name }}</p>
                                            <p class="text-[9px] font-black text-zinc-600 uppercase tracking-widest">
                                                SKU: SH-{{ 1000 + product.id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="py-4 px-6 bg-black/20 border-y border-white/5 group-hover:bg-zinc-800/40 transition-all duration-300">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs font-black text-zinc-300 uppercase tracking-wide">
                                            {{ product.brands?.length ? product.brands[0].name : 'Essential' }}
                                        </span>
                                        <span class="text-[9px] font-bold text-zinc-600 uppercase tracking-widest">{{
                                            product.category?.name || 'Main' }}</span>
                                    </div>
                                </td>
                                <td
                                    class="py-4 px-6 bg-black/20 border-y border-white/5 group-hover:bg-zinc-800/40 transition-all duration-300">
                                    <div class="flex flex-col">
                                        <span class="font-black text-xl tracking-tighter text-white">
                                            R{{ (product.sale_price && parseFloat(product.sale_price) > 0) ?
                                                parseFloat(product.sale_price).toFixed(0) :
                                                parseFloat(product.price).toFixed(0) }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="py-4 px-6 bg-black/20 border-y border-white/5 group-hover:bg-zinc-800/40 transition-all duration-300">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-2 h-2 rounded-full animate-pulse bg-admin-modern shadow-[0_0_10px_rgba(159,232,112,0.4)]">
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-[0.1em] text-zinc-400">
                                            Active
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="py-4 px-6 bg-black/20 rounded-r-3xl border-y border-r border-white/5 group-hover:bg-zinc-800/40 transition-all duration-300 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('shop.show', product.id)"
                                            class="p-3 bg-zinc-900 border border-white/5 text-zinc-400 hover:text-admin-modern hover:border-admin-modern/30 rounded-xl transition-all flex items-center justify-center gap-2">
                                            <Eye class="w-4 h-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden space-y-4">
                    <div v-for="product in products" :key="product.id"
                        class="bg-black/20 rounded-3xl border border-white/5 p-6 space-y-6">
                        <div class="flex items-center gap-5">
                            <div
                                class="w-20 h-20 rounded-2xl bg-zinc-900 border border-white/5 p-2 flex items-center justify-center overflow-hidden shrink-0">
                                <img :src="product.image" :alt="product.name" class="w-full h-full object-contain" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-black text-lg text-white uppercase tracking-tighter truncate">{{
                                    product.name }}</h3>
                                <p class="text-[9px] font-black text-zinc-600 uppercase tracking-widest mb-2">{{
                                    product.category?.name }}</p>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-xl text-admin-modern tracking-tighter">R{{
                                        Math.round(product.sale_price || product.price) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/5">
                            <div class="flex flex-col gap-1">
                                <span
                                    class="text-[8px] font-black text-zinc-500 uppercase tracking-widest">Access</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-admin-modern"></div>
                                    <span
                                        class="text-[10px] font-black text-white uppercase tracking-widest">Active</span>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[8px] font-black text-zinc-500 uppercase tracking-widest">Brand</span>
                                <span class="text-[10px] font-black text-white uppercase tracking-widest truncate">{{
                                    product.brands?.[0]?.name || 'Premium' }}</span>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <Link :href="route('shop.show', product.id)"
                                class="flex-1 bg-white text-black py-4 rounded-xl font-black uppercase text-[10px] tracking-widest text-center">
                                View Product</Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="products.length === 0" class="py-32 text-center flex flex-col items-center">
                    <div
                        class="w-20 h-20 bg-zinc-800/50 rounded-full flex items-center justify-center mb-6 border border-white/5">
                        <Package class="w-8 h-8 text-zinc-600" />
                    </div>
                    <h3 class="text-2xl font-black uppercase tracking-tighter text-zinc-700 mb-2">No items found</h3>
                    <p class="text-zinc-600 font-bold uppercase tracking-widest text-[10px]">Your collection will appear
                        here once you acquire products</p>
                </div>
            </div>
        </div>
    </SubscriberLayout>
</template>

<style scoped>
/* Custom scrollbar for table container */
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
</style>
