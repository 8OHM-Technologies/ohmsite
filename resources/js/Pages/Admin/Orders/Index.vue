<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    orders: Object,
    filters: Object
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const payment_status = ref(props.filters.payment_status || '');

const selectedOrder = ref(null);
const isDrawerOpen = ref(false);

const openOrderDetails = (order) => {
    selectedOrder.value = order;
    isDrawerOpen.value = true;
};

const closeDrawer = () => {
    isDrawerOpen.value = false;
};

const updateFilters = debounce(() => {
    router.get(route('admin.orders.index'), {
        search: search.value,
        status: status.value,
        payment_status: payment_status.value
    }, {
        preserveState: true,
        replace: true
    });
}, 300);

watch([search, status, payment_status], () => {
    updateFilters();
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusColor = (status) => {
    switch (status.toLowerCase()) {
        case 'pending': return 'bg-amber-400/10 text-amber-400 border-amber-400/20';
        case 'confirmed': return 'bg-blue-400/10 text-blue-400 border-blue-400/20';
        case 'packed': return 'bg-purple-400/10 text-purple-400 border-purple-400/20';
        case 'shipped': return 'bg-cyan-400/10 text-cyan-400 border-cyan-400/20';
        case 'delivered': return 'bg-green-400/10 text-green-400 border-green-400/20';
        case 'cancelled': return 'bg-red-400/10 text-red-400 border-red-400/20';
        default: return 'bg-zinc-500/10 text-zinc-500 border-zinc-500/20';
    }
};

const updateOrderStatus = (newStatus) => {
    router.patch(route('admin.orders.update-status', selectedOrder.value.id), {
        status: newStatus
    }, {
        onSuccess: () => {
            selectedOrder.value.status = newStatus;
        }
    });
};
</script>

<template>
    <Head title="Orders Management" />

    <AdminLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Header section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-white tracking-tight">Orders</h1>
                    <p class="text-zinc-500 mt-1">Track and manage every purchase from your customers.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <button class="bg-white/5 hover:bg-white/10 text-white px-6 py-3 rounded-2xl font-bold transition-all border border-white/5">
                        Export Orders
                    </button>
                </div>
            </div>

            <!-- Filters & Search -->
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
                        placeholder="Search by Order ID or customer..."
                        class="block w-full pl-12 pr-4 py-3 bg-white/5 border-none rounded-2xl text-white placeholder-zinc-500 focus:ring-2 focus:ring-admin-modern transition-all"
                    />
                </div>

                <div class="flex gap-4 w-full md:w-auto">
                    <select v-model="status" class="bg-white/5 border-none rounded-2xl text-white py-3 px-6 focus:ring-2 focus:ring-admin-modern transition-all cursor-pointer min-w-[150px]">
                        <option value="" class="bg-zinc-900">All Status</option>
                        <option value="pending" class="bg-zinc-900">Pending</option>
                        <option value="confirmed" class="bg-zinc-900">Confirmed</option>
                        <option value="shipped" class="bg-zinc-900">Shipped</option>
                        <option value="delivered" class="bg-zinc-900">Delivered</option>
                    </select>

                    <select v-model="payment_status" class="bg-white/5 border-none rounded-2xl text-white py-3 px-6 focus:ring-2 focus:ring-admin-modern transition-all cursor-pointer min-w-[150px]">
                        <option value="" class="bg-zinc-900">Payment</option>
                        <option value="paid" class="bg-zinc-900">Paid</option>
                        <option value="pending" class="bg-zinc-900">Pending</option>
                        <option value="failed" class="bg-zinc-900">Failed</option>
                    </select>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 rounded-[2.5rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-8 py-6 text-xs font-bold text-zinc-500 uppercase tracking-widest">Order ID</th>
                                <th class="px-8 py-6 text-xs font-bold text-zinc-500 uppercase tracking-widest">Customer</th>
                                <th class="px-8 py-6 text-xs font-bold text-zinc-500 uppercase tracking-widest">Date</th>
                                <th class="px-8 py-6 text-xs font-bold text-zinc-500 uppercase tracking-widest text-right">Total</th>
                                <th class="px-8 py-6 text-xs font-bold text-zinc-500 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-6 text-xs font-bold text-zinc-500 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="order in orders.data" :key="order.id" class="group hover:bg-white/[0.02] transition-colors cursor-pointer" @click="openOrderDetails(order)">
                                <td class="px-8 py-6">
                                    <span class="text-sm font-black text-white">#{{ order.id }}</span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center font-bold text-xs text-zinc-400">
                                            {{ order.user?.name.charAt(0) }}
                                        </div>
                                        <p class="text-sm font-bold text-white group-hover:text-admin-modern transition-colors">{{ order.user?.name }}</p>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-sm text-zinc-400">
                                    {{ formatDate(order.created_at) }}
                                </td>
                                <td class="px-8 py-6 text-right font-black text-white">
                                    {{ formatCurrency(order.total_amount) }}
                                </td>
                                <td class="px-8 py-6">
                                    <span 
                                        :class="getStatusColor(order.status)"
                                        class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                                    >
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button class="p-2 text-zinc-500 hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-8 py-6 border-t border-white/5 flex items-center justify-between">
                    <p class="text-sm text-zinc-500">
                        Page <span class="text-white">{{ orders.current_page }}</span> of <span class="text-white">{{ orders.last_page }}</span>
                    </p>
                    <div class="flex gap-2">
                        <Link v-for="link in orders.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                            :class="[link.active ? 'bg-admin-modern text-black shadow-lg shadow-admin-modern/20' : 'bg-white/5 text-zinc-400 hover:bg-white/10', !link.url ? 'opacity-50 cursor-not-allowed' : '']"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Detail Drawer -->
        <div v-if="isDrawerOpen" class="fixed inset-0 z-50 overflow-hidden pointer-events-none">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm pointer-events-auto" @click="closeDrawer"></div>
            <div class="absolute inset-y-0 right-0 max-w-2xl w-full bg-zinc-950/95 backdrop-blur-2xl border-l border-white/10 shadow-2xl pointer-events-auto">
                <div class="h-full flex flex-col p-10 overflow-y-auto custom-scrollbar" v-if="selectedOrder">
                    <div class="flex items-center justify-between mb-10">
                        <div>
                            <h2 class="text-2xl font-black text-white uppercase tracking-tighter">Order Detail</h2>
                            <p class="text-admin-modern font-bold text-sm">#ORD-{{ selectedOrder.id }}</p>
                        </div>
                        <button @click="closeDrawer" class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 hover:text-white transition-all">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="space-y-10">
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-zinc-900/50 p-6 rounded-[2rem] border border-white/5">
                                <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-1">Total Amount</p>
                                <p class="text-2xl font-black text-white">{{ formatCurrency(selectedOrder.total_amount) }}</p>
                            </div>
                            <div class="bg-zinc-900/50 p-6 rounded-[2rem] border border-white/5">
                                <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-1">Order Status</p>
                                <div :class="getStatusColor(selectedOrder.status)" class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border mt-1">
                                    {{ selectedOrder.status }}
                                </div>
                            </div>
                        </div>

                        <!-- Customer & Shipping -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <h4 class="text-xs font-black text-white uppercase tracking-widest border-b border-white/5 pb-2">Customer Info</h4>
                                <div class="space-y-2">
                                    <p class="text-sm font-bold text-white">{{ selectedOrder.user?.name }}</p>
                                    <p class="text-xs text-zinc-400">{{ selectedOrder.user?.email }}</p>
                                    <p class="text-xs text-zinc-400">{{ selectedOrder.phone || 'No phone provided' }}</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <h4 class="text-xs font-black text-white uppercase tracking-widest border-b border-white/5 pb-2">Shipping Address</h4>
                                <p class="text-xs text-zinc-400 leading-relaxed">{{ selectedOrder.shipping_address || 'No address provided' }}</p>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-white uppercase tracking-widest border-b border-white/5 pb-2">Order Items</h4>
                            <div class="space-y-4">
                                <div v-for="item in selectedOrder.items" :key="item.id" class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5 group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-16 rounded-xl bg-zinc-800 overflow-hidden border border-white/10 group-hover:border-admin-modern/30 transition-all">
                                            <img :src="item.product?.image" class="w-full h-full object-cover" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white">{{ item.product?.name }}</p>
                                            <p class="text-[10px] text-zinc-500 font-black uppercase">Qty: {{ item.quantity }} × {{ formatCurrency(item.unit_price) }}</p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-black text-white">{{ formatCurrency(item.quantity * item.unit_price) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Management Actions -->
                        <div class="space-y-6 pt-6 border-t border-white/5">
                            <h4 class="text-xs font-black text-white uppercase tracking-widest">Update Order Status</h4>
                            <div class="grid grid-cols-3 gap-2">
                                <button v-for="s in ['Confirmed', 'Shipped', 'Delivered']" :key="s" 
                                    @click="updateOrderStatus(s)"
                                    class="py-3 px-4 bg-white/5 hover:bg-admin-modern hover:text-black rounded-xl text-[10px] font-black uppercase tracking-widest text-white transition-all border border-white/5"
                                >
                                    {{ s }}
                                </button>
                            </div>
                            
                            <div class="flex gap-4">
                                <button class="flex-1 py-4 px-6 bg-white text-black rounded-2xl font-black uppercase tracking-widest hover:bg-zinc-200 transition-all">
                                    Print Invoice
                                </button>
                                <button @click="updateOrderStatus('Cancelled')" class="flex-1 py-4 px-6 border border-red-500/50 text-red-500 rounded-2xl font-black uppercase tracking-widest hover:bg-red-500/10 transition-all">
                                    Cancel Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
