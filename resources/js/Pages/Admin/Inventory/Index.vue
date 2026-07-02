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
const stock_status = ref(props.filters.stock_status || '');

const selectedProduct = ref(null);
const isModalOpen = ref(false);
const newStock = ref(0);

const openRestockModal = (product) => {
    selectedProduct.value = product;
    newStock.value = product.stock;
    isModalOpen.value = true;
};

const updateStock = () => {
    router.patch(route('admin.inventory.update-stock', selectedProduct.value.id), {
        stock: newStock.value
    }, {
        onSuccess: () => {
            isModalOpen.value = false;
        }
    });
};

const updateFilters = debounce(() => {
    router.get(route('admin.inventory.index'), {
        search: search.value,
        stock_status: stock_status.value
    }, { preserveState: true, replace: true });
}, 300);

watch([search, stock_status], () => updateFilters());

const formatCurrency = (val) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);

const getStockBadge = (stock) => {
    if (stock <= 0) return 'bg-red-400/10 text-red-400 border-red-400/20';
    if (stock <= 10) return 'bg-amber-400/10 text-amber-400 border-amber-400/20';
    return 'bg-green-400/10 text-green-400 border-green-400/20';
};

const getStockStatus = (stock) => {
    if (stock <= 0) return 'Out of Stock';
    if (stock <= 10) return 'Low Stock';
    return 'In Stock';
};
</script>

<template>
    <Head title="Inventory Management" />

    <AdminLayout>
        <div class="space-y-8 animate-in fade-in duration-700">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-black text-white tracking-tight uppercase">Inventory <span class="text-admin-modern">Manager</span></h1>
                    <p class="text-zinc-500 mt-1 font-medium">Monitor stock levels, variants, and warehouse movement.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="bg-white/5 hover:bg-white/10 text-white px-6 py-3 rounded-2xl font-bold transition-all border border-white/5">
                        Download Report
                    </button>
                    <Link :href="route('admin.products.create')" class="bg-admin-modern text-black px-6 py-3 rounded-2xl font-bold transition-all shadow-lg shadow-admin-modern/20">
                        Add New Item
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="stat in [
                    { label: 'Total Products', value: stats.total_products, color: 'text-white' },
                    { label: 'Low Stock Items', value: stats.low_stock, color: 'text-amber-400' },
                    { label: 'Out of Stock', value: stats.out_of_stock, color: 'text-red-400' },
                    { label: 'Inventory Value', value: formatCurrency(stats.total_value), color: 'text-admin-modern' }
                ]" :key="stat.label" class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-6 rounded-[2.5rem] group hover:bg-zinc-900/60 transition-all duration-500">
                    <p class="text-xs font-black text-zinc-500 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                    <p class="text-2xl font-black transition-all" :class="stat.color">{{ stat.value }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col md:flex-row gap-4 items-center bg-zinc-900/40 backdrop-blur-md border border-white/5 p-4 rounded-[2rem]">
                <div class="relative flex-1 group w-full">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-zinc-500 group-focus-within:text-admin-modern transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by product name or SKU..."
                        class="block w-full pl-12 pr-4 py-3 bg-white/5 border-none rounded-2xl text-white placeholder-zinc-500 focus:ring-2 focus:ring-admin-modern transition-all"
                    />
                </div>

                <select v-model="stock_status" class="bg-white/5 border-none rounded-2xl text-white py-3 px-6 focus:ring-2 focus:ring-admin-modern transition-all cursor-pointer min-w-[180px]">
                    <option value="" class="bg-zinc-900">All Stock Status</option>
                    <option value="in" class="bg-zinc-900">In Stock</option>
                    <option value="low" class="bg-zinc-900">Low Stock</option>
                    <option value="out" class="bg-zinc-900">Out of Stock</option>
                </select>
            </div>

            <!-- Inventory Table -->
            <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 rounded-[2.5rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest">Product</th>
                                <th class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest">Category</th>
                                <th class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest text-center">Stock</th>
                                <th class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest text-right">Price</th>
                                <th class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="product in products.data" :key="product.id" class="group hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 rounded-2xl bg-zinc-800 overflow-hidden border border-white/10 group-hover:border-admin-modern/50 transition-all">
                                            <img :src="product.image" class="w-full h-full object-cover" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white group-hover:text-admin-modern transition-colors leading-tight">{{ product.name }}</p>
                                            <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mt-1">SKU: {{ product.sku || 'SKU-' + product.id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-xs font-bold text-zinc-400 bg-white/5 px-3 py-1 rounded-lg border border-white/5">{{ product.category?.name || 'Uncategorized' }}</span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span class="text-sm font-black text-white">{{ product.stock }}</span>
                                </td>
                                <td class="px-8 py-6 text-right font-black text-white">
                                    {{ formatCurrency(product.sale_price) }}
                                </td>
                                <td class="px-8 py-6">
                                    <span :class="getStockBadge(product.stock)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border">
                                        {{ getStockStatus(product.stock) }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openRestockModal(product)" class="p-2 bg-white/5 hover:bg-admin-modern hover:text-black rounded-xl transition-all border border-white/5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        </button>
                                        <Link :href="route('admin.products.edit', product.id)" class="p-2 bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white rounded-xl transition-all border border-white/5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-8 py-6 border-t border-white/5 flex items-center justify-between">
                    <p class="text-sm text-zinc-500 font-medium">Items <span class="text-white">{{ products.from }}-{{ products.to }}</span> of <span class="text-white">{{ products.total }}</span></p>
                    <div class="flex gap-2">
                        <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                            :class="[link.active ? 'bg-admin-modern text-black shadow-lg shadow-admin-modern/20' : 'bg-white/5 text-zinc-400 hover:bg-white/10', !link.url ? 'opacity-50 cursor-not-allowed' : '']"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Restock Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="isModalOpen = false"></div>
            <div class="relative bg-zinc-950 border border-white/10 w-full max-w-md rounded-[3rem] p-10 shadow-2xl animate-in zoom-in-95 duration-300">
                <h3 class="text-2xl font-black text-white uppercase tracking-tighter mb-2">Update Stock</h3>
                <p class="text-zinc-500 text-sm mb-8 font-medium">{{ selectedProduct?.name }}</p>

                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-2">Quantity in Warehouse</label>
                        <input v-model="newStock" type="number" class="w-full bg-white/5 border-white/10 rounded-2xl text-white text-2xl font-black py-4 px-6 focus:ring-2 focus:ring-admin-modern transition-all" />
                    </div>

                    <div class="flex gap-4">
                        <button @click="isModalOpen = false" class="flex-1 py-4 px-6 bg-white/5 text-zinc-400 rounded-2xl font-bold uppercase tracking-widest hover:bg-white/10 transition-all">Cancel</button>
                        <button @click="updateStock" class="flex-1 py-4 px-6 bg-admin-modern text-black rounded-2xl font-black uppercase tracking-widest hover:bg-opacity-90 transition-all shadow-lg shadow-admin-modern/20">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
</style>
