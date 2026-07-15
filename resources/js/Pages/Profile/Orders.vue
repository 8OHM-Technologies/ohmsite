<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    orders: Array,
    auth: Object,
    apiStats: Object
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
        case 'cancelled': return 'text-red-500 bg-red-500/10';
        default: return 'text-zinc-500 bg-zinc-500/10';
    }
};

const getDatasetLabel = (value) => {
    const datasets = usePage().props.datasets || [];
    const dataset = datasets.find(d => d.slug === value);
    if (dataset) return dataset.name;

    switch (value) {
        case 'ccma': return 'CCMA Awards';
        case 'labour-court': return 'Labour Court Judgments';
        case 'all': return 'All Datasets';
        default: return value || 'CCMA Awards';
    }
};

const getFrequencyLabel = (value) => {
    switch (value) {
        case 'monthly': return 'Monthly';
        case 'annually': return 'Annually';
        default: return value || 'Monthly';
    }
};
</script>

<template>
    <MainLayout :auth="auth" title="My Orders">
        <div class="max-w-7xl mx-auto">
            <div class="mb-16">
                <h1 class="text-7xl font-black uppercase tracking-tighter text-white leading-none">My Services</h1>
                <p class="text-zinc-500 font-black uppercase tracking-widest text-xs mt-4">Manage your subscriptions or
                    download once-off dataset purchases.</p>
            </div>

            <!-- API Usage Card -->
            <div v-if="apiStats" class="bg-zinc-900 rounded-[3rem] p-8 border border-sky-500/20 mb-8 overflow-hidden relative">
                <div class="absolute top-0 right-0 w-64 h-64 bg-sky-500/5 rounded-full blur-3xl -z-10"></div>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-sky-500/10 rounded-2xl flex items-center justify-center border border-sky-500/20 text-sky-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white mb-1">Developer API Call Limit</h3>
                            <p class="text-zinc-500 text-xs font-medium">Your API usage counter resets on the 1st of every month.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-1">Monthly Used</p>
                            <p class="text-lg font-bold text-white">{{ apiStats.used }} / {{ apiStats.limit }}</p>
                        </div>
                        <div class="text-right bg-sky-500/10 px-6 py-3 rounded-2xl border border-sky-500/20">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-sky-400 mb-1">Remaining Calls</p>
                            <p class="text-2xl font-black text-sky-400">{{ apiStats.remaining }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="orders.length === 0" class="text-center py-20 bg-zinc-900 rounded-[3rem] border border-white/5">
                <div class="mb-6 flex justify-center">
                    <div class="w-20 h-20 bg-zinc-800 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">No orders found</h3>
                <p class="text-zinc-500 mb-8">You haven't placed any orders yet.</p>
                <Link :href="route('home')"
                    class="inline-block bg-white text-black px-10 py-4 rounded-full font-black uppercase tracking-widest text-xs hover:bg-zinc-200 transition-all">
                    Browse Data Solutions
                </Link>
            </div>

            <div v-else class="space-y-6">
                <div v-for="order in orders" :key="order.id"
                    class="bg-zinc-900 rounded-[3rem] p-8 border border-white/5 overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-6 mb-8 pb-8 border-b border-white/5">
                        <div class="flex items-center gap-6">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-1">Order ID
                                </p>
                                <p class="text-sm font-bold text-white">#{{ order.id }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-1">Date</p>
                                <p class="text-sm font-bold text-white">{{ formatDate(order.created_at) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-1">Total
                                </p>
                                <p class="text-sm font-bold text-white">R{{ parseFloat(order.total_amount).toFixed(2) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                :class="[getStatusColor(order.status), 'px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest']">
                                {{ order.status }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-for="item in order.items" :key="item.id" class="flex gap-4">
                            <div
                                class="w-20 h-20 bg-black rounded-2xl overflow-hidden flex-shrink-0 border border-white/5">
                                <img :src="item.product.image" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-white truncate">{{ item.product.name }}</h4>
                                <p class="text-xs text-zinc-500 mt-1 uppercase tracking-tighter">
                                    Qty: {{ item.quantity }}
                                    <br>
                                    <template v-if="item.options?.dataset">
                                        • Dataset: {{ getDatasetLabel(item.options.dataset) }}
                                    </template>
                                    <br>
                                    <template v-if="item.options?.frequency">
                                        • Billing: {{ getFrequencyLabel(item.options.frequency) }}
                                    </template>
                                </p>
                                <p class="text-sm font-bold text-white">R{{ parseFloat(item.unit_price).toFixed(2) }}
                                </p>

                                <!-- Product Specific Action Links -->
                                <template v-if="order.payment_status === 'paid'">
                                    <div v-if="item.product.name.toLowerCase().includes('once-off dataset')"
                                        class="mt-3 flex gap-2 flex-wrap">
                                        <a :href="route('downloads.dataset', { dataset: item.options?.dataset || 'all', format: 'csv' })"
                                            class="inline-flex items-center gap-1.5 bg-amber-500/10 hover:bg-amber-500 hover:text-black text-amber-500 text-[10px] font-black uppercase tracking-wider px-3.5 py-1.5 rounded-full transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Download CSV
                                        </a>
                                        <a :href="route('downloads.dataset', { dataset: item.options?.dataset || 'all', format: 'json' })"
                                            class="inline-flex items-center gap-1.5 bg-sky-500/10 hover:bg-sky-500 hover:text-black text-sky-400 text-[10px] font-black uppercase tracking-wider px-3.5 py-1.5 rounded-full transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Download JSON
                                        </a>
                                    </div>
                                    <div v-else-if="item.product.name.toLowerCase().includes('developer api')"
                                        class="mt-3">
                                        <Link :href="route('developer.docs')"
                                            class="inline-flex items-center gap-1.5 bg-sky-400/10 hover:bg-sky-400 hover:text-black text-sky-400 text-[10px] font-black uppercase tracking-wider px-3.5 py-1.5 rounded-full transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                            API Documentation
                                        </Link>
                                    </div>
                                    <div v-else-if="item.product.name.toLowerCase().includes('analytics dashboard') || item.product.name.toLowerCase().includes('pro analytics')"
                                        class="mt-3">
                                        <Link :href="route('pro-dashboard.index')"
                                            class="inline-flex items-center gap-1.5 bg-emerald-400/10 hover:bg-emerald-400 hover:text-black text-emerald-400 text-[10px] font-black uppercase tracking-wider px-3.5 py-1.5 rounded-full transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
                                            </svg>
                                            Access Pro Analytics
                                        </Link>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
