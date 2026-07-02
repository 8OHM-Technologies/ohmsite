<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    orders: Array,
    auth: Object
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'pending': return 'text-amber-500 bg-amber-500/10';
        case 'processing': return 'text-blue-500 bg-blue-500/10';
        case 'shipped': return 'text-purple-500 bg-purple-500/10';
        case 'delivered': return 'text-green-500 bg-green-500/10';
        case 'cancelled': return 'text-red-500 bg-red-500/10';
        default: return 'text-zinc-500 bg-zinc-500/10';
    }
};
</script>

<template>
    <MainLayout :auth="auth" title="My Orders">
        <div class="max-w-7xl mx-auto">
            <div class="mb-16">
                <h1 class="text-7xl font-black uppercase italic tracking-tighter text-white leading-none">My Orders</h1>
                <p class="text-zinc-500 font-black uppercase tracking-widest text-xs mt-4">Track and manage your recent purchases</p>
            </div>

            <div v-if="orders.length === 0" class="text-center py-20 bg-zinc-900 rounded-[3rem] border border-white/5">
                <div class="mb-6 flex justify-center">
                    <div class="w-20 h-20 bg-zinc-800 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">No orders found</h3>
                <p class="text-zinc-500 mb-8">You haven't placed any orders yet.</p>
                <Link :href="route('shop.index')" class="inline-block bg-white text-black px-10 py-4 rounded-full font-black uppercase tracking-widest text-xs hover:bg-zinc-200 transition-all">
                    Start Shopping
                </Link>
            </div>

            <div v-else class="space-y-6">
                <div v-for="order in orders" :key="order.id" class="bg-zinc-900 rounded-[3rem] p-8 border border-white/5 overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-6 mb-8 pb-8 border-b border-white/5">
                        <div class="flex items-center gap-6">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-1">Order ID</p>
                                <p class="text-sm font-bold text-white">#{{ order.id }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-1">Date</p>
                                <p class="text-sm font-bold text-white">{{ formatDate(order.created_at) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-1">Total</p>
                                <p class="text-sm font-bold text-white">€{{ parseFloat(order.total_amount).toFixed(2) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span :class="[getStatusColor(order.status), 'px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest']">
                                {{ order.status }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-for="item in order.items" :key="item.id" class="flex gap-4">
                            <div class="w-20 h-20 bg-black rounded-2xl overflow-hidden flex-shrink-0 border border-white/5">
                                <img :src="item.product.image" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-white truncate">{{ item.product.name }}</h4>
                                <p class="text-xs text-zinc-500 mt-1 uppercase tracking-tighter">Qty: {{ item.quantity }} • Size: {{ (item.options || {}).size || 'N/A' }}</p>
                                <p class="text-sm font-bold text-white">€{{ parseFloat(item.unit_price).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
