<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    customers: Object,
    stats: Object,
    filters: Object
});

// Safe filter initialization
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const handleFilter = () => {
    router.get(route('admin.customers.index'), {
        search: search.value,
        status: status.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

watch([search, status], () => {
    handleFilter();
});

// Safe price formatter to prevent White Screen of Death
const formatPrice = (value) => {
    const numericValue = parseFloat(value);
    if (isNaN(numericValue)) return '€0.00';
    return '€' + numericValue.toFixed(2);
};

const getStatusClass = (customer) => {
    if (customer?.is_banned) return 'bg-red-500/10 text-red-500';
    if (parseFloat(customer?.total_spent || 0) >= 1000) return 'bg-amber-500/10 text-amber-500';
    return 'bg-green-500/10 text-green-500';
};

const getStatusLabel = (customer) => {
    if (customer?.is_banned) return 'Banned';
    if (parseFloat(customer?.total_spent || 0) >= 1000) return 'VIP';
    return 'Active';
};
</script>

<template>
    <Head title="Customer Management" />
    <AdminLayout>
        <div class="p-10 space-y-10">
            <!-- Header -->
            <div>
                <h1 class="text-4xl font-black text-white tracking-tighter uppercase">Customer <span class="text-admin-modern">Management</span></h1>
                <p class="text-zinc-500 mt-2 font-medium">Manage your store's customer base and analytics.</p>
            </div>

            <!-- Stats -->
            <div v-if="stats" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="(val, key) in stats" :key="key" class="bg-zinc-900/40 border border-white/5 p-8 rounded-[2.5rem]">
                    <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">{{ key.replace('_', ' ') }}</p>
                    <p class="text-3xl font-black text-white mt-2">{{ val || 0 }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex gap-4">
                <input v-model="search" placeholder="Search customers..." class="bg-zinc-900/50 border border-white/5 rounded-2xl py-3 px-6 text-white w-full md:w-80 focus:ring-2 focus:ring-admin-modern transition-all" />
                <select v-model="status" class="bg-zinc-900/50 border border-white/5 rounded-2xl py-3 px-6 text-white focus:ring-2 focus:ring-admin-modern transition-all">
                    <option value="">All Status</option>
                    <option value="vip">VIP Only</option>
                    <option value="active">Recently Active</option>
                    <option value="banned">Banned</option>
                </select>
            </div>

            <!-- Table -->
            <div v-if="customers && customers.data" class="bg-zinc-900/40 border border-white/5 rounded-[2.5rem] overflow-hidden shadow-2xl">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/5 text-[10px] uppercase font-black text-zinc-500">
                            <th class="p-6">Customer</th>
                            <th class="p-6">Email</th>
                            <th class="p-6 text-center">Orders</th>
                            <th class="p-6 text-center">Spent</th>
                            <th class="p-6 text-center">Status</th>
                            <th class="p-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-white/[0.02] transition-colors group">
                            <td class="p-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center font-black text-xs text-white uppercase border border-white/5">
                                        {{ (customer.name || 'U').charAt(0) }}
                                    </div>
                                    <span class="font-bold text-white">{{ customer.name }}</span>
                                </div>
                            </td>
                            <td class="p-6 text-zinc-400 text-sm">{{ customer.email }}</td>
                            <td class="p-6 text-center text-zinc-400 font-medium">{{ customer.orders_count || 0 }}</td>
                            <td class="p-6 text-center text-white font-black">{{ formatPrice(customer.total_spent) }}</td>
                            <td class="p-6 text-center">
                                <span :class="getStatusClass(customer)" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">
                                    {{ getStatusLabel(customer) }}
                                </span>
                            </td>
                            <td class="p-6 text-right">
                                <div class="flex justify-end gap-3">
                                    <Link v-if="customer.id" :href="route('admin.customers.show', customer.id)" class="p-2 bg-white/5 hover:bg-admin-modern hover:text-black rounded-xl transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <button 
                                        v-if="customer.id"
                                        @click="router.post(route('admin.customers.toggle-status', customer.id))"
                                        class="p-2 rounded-xl transition-all"
                                        :class="customer.is_banned ? 'bg-green-500/10 text-green-500 hover:bg-green-500 hover:text-black' : 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white'"
                                    >
                                        <svg v-if="customer.is_banned" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="customers && customers.links && customers.links.length > 3" class="flex justify-center gap-2">
                <template v-for="(link, index) in customers.links" :key="index">
                    <Link v-if="link.url" :href="link.url" 
                          v-html="link.label"
                          class="px-5 py-3 rounded-2xl text-[10px] font-black uppercase transition-all"
                          :class="[
                              link.active ? 'bg-admin-modern text-black shadow-lg shadow-admin-modern/20' : 'bg-zinc-900 border border-white/5 text-zinc-500 hover:text-white'
                          ]"
                    />
                    <span v-else 
                          v-html="link.label"
                          class="px-5 py-3 rounded-2xl text-[10px] font-black uppercase bg-zinc-900/50 border border-white/5 text-zinc-700 cursor-not-allowed"
                    ></span>
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
