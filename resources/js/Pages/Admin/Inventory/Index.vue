<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    products: Object,
    stats: Object,
    filters: Object
});

const search = ref(props.filters.search || '');

const updateFilters = debounce(() => {
    router.get(route('admin.licenses.index'), {
        search: search.value,
    }, { preserveState: true, replace: true });
}, 300);

watch(search, () => updateFilters());

const formatCurrency = (val) => new Intl.NumberFormat('en-ZA', { style: 'currency', currency: 'ZAR' }).format(val);
</script>

<template>

    <Head title="License Management" />

    <AdminLayout>
        <div class="space-y-8 animate-in fade-in duration-700">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-black text-white tracking-tight uppercase">Licenses <span
                            class="text-admin-modern">Manager</span></h1>
                    <p class="text-zinc-500 mt-1 font-medium">Monitor active subscriptions, dataset accesses, and
                        platforms licenses.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.products.create')"
                        class="btn-primary text-black px-6 py-3 rounded-2xl font-bold transition-all shadow-lg shadow-admin-modern/20">
                        Add New Service
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="stat in [
                    { label: 'Total Services', value: stats.total_products, color: 'text-white' },
                    { label: 'Active Licenses', value: stats.active_licenses, color: 'text-admin-modern' },
                    { label: 'Pending Orders', value: stats.pending_orders, color: 'text-amber-400' },
                    { label: 'Total Customers', value: stats.total_customers, color: 'text-white' }
                ]" :key="stat.label"
                    class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-6 rounded-[2.5rem] group hover:bg-zinc-900/60 transition-all duration-500">
                    <p class="text-xs font-black text-zinc-500 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                    <p class="text-2xl font-black transition-all" :class="stat.color">{{ stat.value }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="flex flex-col md:flex-row gap-4 items-center bg-zinc-900/40 backdrop-blur-md border border-white/5 p-4 rounded-[2rem]">
                <div class="relative flex-1 group w-full">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-zinc-500 group-focus-within:text-admin-modern transition-colors"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search services or SKU..."
                        class="block w-full pl-12 pr-4 py-3 bg-white/5 border-none rounded-2xl text-white placeholder-zinc-500 focus:ring-2 focus:ring-admin-modern transition-all" />
                </div>
            </div>

            <!-- Licenses/Products Table -->
            <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 rounded-[2.5rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest">
                                    Service Details</th>
                                <th class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest">
                                    Classification</th>
                                <th
                                    class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest text-right">
                                    Price</th>
                                <th class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest">
                                    Fulfillment Type</th>
                                <th
                                    class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="product in products.data" :key="product.id"
                                class="group hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-14 h-14 rounded-2xl bg-zinc-800 overflow-hidden border border-white/10 group-hover:border-admin-modern/50 transition-all">
                                            <img :src="product.image" class="w-full h-full object-cover" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-bold text-white group-hover:text-admin-modern transition-colors leading-tight">
                                                {{ product.name }}</p>
                                            <p
                                                class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mt-1">
                                                SKU: 8OHM-{{ 1000 + product.id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span
                                        class="text-xs font-bold text-zinc-400 bg-white/5 px-3 py-1 rounded-lg border border-white/5">{{
                                            product.category?.name || 'Uncategorized' }}</span>
                                </td>
                                <td class="px-8 py-6 text-right font-black text-white">
                                    {{ formatCurrency(product.sale_price || product.price) }}
                                </td>
                                <td class="px-8 py-6">
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border bg-green-400/10 text-green-400 border-green-400/20">
                                        Unlimited SaaS
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.products.edit', product.id)"
                                            class="p-2 bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white rounded-xl transition-all border border-white/5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-8 py-6 border-t border-white/5 flex items-center justify-between">
                    <p class="text-sm text-zinc-500 font-medium">Items <span class="text-white">{{ products.from }}-{{
                        products.to }}</span> of <span class="text-white">{{ products.total }}</span></p>
                    <div class="flex gap-2">
                        <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'"
                            v-html="link.label"
                            :class="[link.active ? 'btn-primary text-black shadow-lg shadow-admin-modern/20' : 'bg-white/5 text-zinc-400 hover:bg-white/10', !link.url ? 'opacity-50 cursor-not-allowed' : '']"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all" />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped></style>
