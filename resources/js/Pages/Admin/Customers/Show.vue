<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    customer: Object
});

const formatDate = (dateString) => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <Head :title="'Customer: ' + customer.name" />
    <AdminLayout>
        <div class="p-10 space-y-10">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <Link :href="route('admin.customers.index')" class="p-3 bg-zinc-900 border border-white/5 rounded-2xl text-zinc-500 hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-4xl font-black text-white tracking-tighter uppercase">{{ customer.name }}</h1>
                        <p class="text-zinc-500 font-medium">{{ customer.email }} • Joined {{ formatDate(customer.created_at) }}</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <button 
                        @click="router.post(route('admin.customers.toggle-status', customer.id))"
                        class="px-8 py-3 rounded-2xl font-black uppercase tracking-widest text-xs transition-all border border-white/5"
                        :class="customer.is_banned ? 'bg-green-500 text-black' : 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white'"
                    >
                        {{ customer.is_banned ? 'Unban User' : 'Ban User' }}
                    </button>
                    <button 
                        @click="router.post(route('admin.customers.toggle-vip', customer.id))"
                        class="px-8 py-3 rounded-2xl font-black uppercase tracking-widest text-xs transition-all border border-white/5"
                        :class="customer.is_vip ? 'bg-amber-500 text-black' : 'bg-amber-500/10 text-amber-500 hover:bg-amber-500 hover:text-white'"
                    >
                        {{ customer.is_vip ? 'Remove VIP' : 'Make VIP' }}
                    </button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2.5rem]">
                    <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Lifetime Value</p>
                    <p class="text-3xl font-black text-admin-modern mt-2">€{{ parseFloat(customer.total_spent || 0).toFixed(2) }}</p>
                </div>
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2.5rem]">
                    <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Total Orders</p>
                    <p class="text-3xl font-black text-white mt-2">{{ customer.orders.length }}</p>
                </div>
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2.5rem]">
                    <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Last Active</p>
                    <p class="text-xl font-black text-white mt-2">{{ formatDate(customer.last_active_at) }}</p>
                </div>
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2.5rem]">
                    <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Status</p>
                    <p class="text-xl font-black mt-2 uppercase italic" :class="customer.is_banned ? 'text-red-500' : (customer.is_vip ? 'text-amber-500' : 'text-green-500')">
                        {{ customer.is_banned ? 'Banned' : (customer.is_vip ? 'VIP' : 'Active') }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order History -->
                <div class="lg:col-span-2 space-y-6">
                    <h2 class="text-2xl font-black italic uppercase tracking-tighter text-white">Order History</h2>
                    <div class="bg-zinc-900/40 border border-white/5 rounded-[2.5rem] overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-white/5 text-[10px] uppercase font-black text-zinc-500">
                                    <th class="p-6">Order ID</th>
                                    <th class="p-6">Date</th>
                                    <th class="p-6">Items</th>
                                    <th class="p-6">Amount</th>
                                    <th class="p-6">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="order in customer.orders" :key="order.id" class="hover:bg-white/[0.02]">
                                    <td class="p-6 font-bold text-white">#{{ order.id }}</td>
                                    <td class="p-6 text-zinc-400">{{ formatDate(order.created_at) }}</td>
                                    <td class="p-6 text-zinc-400">{{ order.items.length }} Items</td>
                                    <td class="p-6 text-white font-black">€{{ parseFloat(order.total_amount).toFixed(2) }}</td>
                                    <td class="p-6">
                                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-black uppercase">{{ order.status }}</span>
                                    </td>
                                </tr>
                                <tr v-if="customer.orders.length === 0">
                                    <td colspan="5" class="p-10 text-center text-zinc-500 font-bold uppercase tracking-widest">No orders placed yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Favorites -->
                <div class="space-y-6">
                    <h2 class="text-2xl font-black italic uppercase tracking-tighter text-white">Wishlist</h2>
                    <div class="bg-zinc-900/40 border border-white/5 rounded-[2.5rem] p-8 space-y-6">
                        <div v-for="product in customer.favorites" :key="product.id" class="flex items-center gap-4 group">
                            <div class="w-16 h-16 bg-zinc-800 rounded-2xl overflow-hidden border border-white/5">
                                <img :src="product.image" class="w-full h-full object-cover group-hover:scale-110 transition-all" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white leading-tight">{{ product.name }}</p>
                                <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mt-1">€{{ product.price }}</p>
                            </div>
                        </div>
                        <p v-if="customer.favorites.length === 0" class="text-center text-zinc-500 font-bold uppercase tracking-widest py-10">Wishlist is empty</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
