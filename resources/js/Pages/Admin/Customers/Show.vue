<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    customer: Object,
    products: Array
});

const formatDate = (dateString) => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Modals state
const isEditModalOpen = ref(false);
const isOrderModalOpen = ref(false);

// Edit Form
const editForm = useForm({
    first_name: props.customer.first_name || '',
    last_name: props.customer.last_name || '',
    email: props.customer.email || '',
    phone: props.customer.phone || '',
    company_name: props.customer.company_name || '',
    country: props.customer.country || '',
    role: props.customer.role || 'customer',
    api_limit_override: props.customer.api_limit_override !== null && props.customer.api_limit_override !== undefined ? props.customer.api_limit_override : ''
});

const openEditModal = () => {
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
};

const submitEditForm = () => {
    editForm.put(route('admin.customers.update', props.customer.id), {
        onSuccess: () => closeEditModal()
    });
};

// Order Form
const orderForm = useForm({
    product_id: '',
    status: 'completed',
    payment_status: 'paid',
    total_amount: 0,
    frequency: 'monthly'
});

const openOrderModal = () => {
    orderForm.reset();
    if (props.products && props.products.length > 0) {
        orderForm.product_id = props.products[0].id;
        orderForm.total_amount = props.products[0].price;
    }
    isOrderModalOpen.value = true;
};

const closeOrderModal = () => {
    isOrderModalOpen.value = false;
};

const handleProductChange = (productId) => {
    const selected = props.products.find(p => p.id == productId);
    if (selected) {
        orderForm.total_amount = selected.price;
    }
};

const submitOrderForm = () => {
    orderForm.post(route('admin.customers.create-order', props.customer.id), {
        onSuccess: () => closeOrderModal()
    });
};

// Inline status updates
const updateOrderStatus = (orderId, status, paymentStatus) => {
    router.patch(route('admin.orders.update-status', orderId), {
        status: status,
        payment_status: paymentStatus
    }, {
        preserveScroll: true
    });
};
</script>

<template>

    <Head :title="'Customer: ' + customer.first_name + ' ' + customer.last_name" />
    <AdminLayout>
        <div class="p-10 space-y-10">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-6">
                    <Link :href="route('admin.customers.index')"
                        class="p-3 bg-zinc-900 border border-white/5 rounded-2xl text-zinc-500 hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-4xl font-black text-white tracking-tighter uppercase">{{ customer.first_name }} {{ customer.last_name }}</h1>
                        <p class="text-zinc-500 font-medium">{{ customer.email }} • Joined {{
                            formatDate(customer.created_at) }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button @click="openEditModal"
                        class="px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs transition-all border border-white/5 bg-white/5 text-white hover:bg-white/10">
                        Edit Details
                    </button>
                    <button @click="router.post(route('admin.customers.toggle-status', customer.id))"
                        class="px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs transition-all border border-white/5"
                        :class="customer.is_banned ? 'bg-green-500 text-black' : 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white'">
                        {{ customer.is_banned ? 'Unban User' : 'Ban User' }}
                    </button>
                    <button @click="router.post(route('admin.customers.toggle-vip', customer.id))"
                        class="px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs transition-all border border-white/5"
                        :class="customer.is_vip ? 'bg-amber-500 text-black' : 'bg-amber-500/10 text-amber-500 hover:bg-amber-500 hover:text-white'">
                        {{ customer.is_vip ? 'Remove VIP' : 'Make VIP' }}
                    </button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2.5rem]">
                    <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Lifetime Value</p>
                    <p class="text-3xl font-black text-admin-modern mt-2">R{{ parseFloat(customer.total_spent ||
                        0).toFixed(2) }}</p>
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
                    <p class="text-xl font-black mt-2 uppercase"
                        :class="customer.is_banned ? 'text-red-500' : (customer.is_vip ? 'text-amber-500' : 'text-green-500')">
                        {{ customer.is_banned ? 'Banned' : (customer.is_vip ? 'VIP' : 'Active') }}
                    </p>
                </div>
            </div>

            <div class="bg-zinc-900/40 border border-white/5 rounded-[2.5rem] p-8 space-y-6">
                <h3 class="text-lg font-black uppercase text-zinc-400 tracking-wider">Extended Profile Info</h3>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6 text-sm">
                    <div>
                        <span class="text-xs text-zinc-500 block">Phone</span>
                        <span class="font-bold text-white">{{ customer.phone || 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500 block">Company</span>
                        <span class="font-bold text-white">{{ customer.company_name || 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500 block">Country</span>
                        <span class="font-bold text-white">{{ customer.country || 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500 block">Subscription Status</span>
                        <span class="font-bold px-3 py-1.5 rounded-lg text-xs uppercase"
                            :class="customer.subscription_status === 'active' ? 'bg-green-500/10 text-green-400' : 'bg-zinc-500/10 text-zinc-400'">
                            {{ customer.subscription_status || 'Inactive' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500 block">API Limit Override</span>
                        <span class="font-bold text-white">{{ customer.api_limit_override !== null ? customer.api_limit_override : 'None' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order History -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-black uppercase tracking-tighter text-primary">Order History</h2>
                        <button @click="openOrderModal"
                            class="bg-admin-modern hover:opacity-95 text-black px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-lg shadow-admin-modern/20">
                            + Add Order / Subscription
                        </button>
                    </div>
                    <div class="bg-zinc-900/40 border border-white/5 rounded-[2.5rem] overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-white/5 text-[10px] uppercase font-black text-zinc-500">
                                    <th class="p-6">Order ID</th>
                                    <th class="p-6">Date</th>
                                    <th class="p-6">Items</th>
                                    <th class="p-6">Amount</th>
                                    <th class="p-6">Order Status</th>
                                    <th class="p-6">Payment Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="order in customer.orders" :key="order.id" class="hover:bg-white/[0.02]">
                                    <td class="p-6 font-bold text-white">#{{ order.id }}</td>
                                    <td class="p-6 text-zinc-400 text-xs">{{ formatDate(order.created_at) }}</td>
                                    <td class="p-6 text-zinc-400">
                                        <div class="text-xs text-white font-medium" v-for="item in order.items" :key="item.id">
                                            {{ item.product?.name || 'Unknown Product' }}
                                        </div>
                                    </td>
                                    <td class="p-6 text-white font-black">R{{ parseFloat(order.total_amount).toFixed(2) }}</td>
                                    <td class="p-6">
                                        <select :value="order.status" @change="e => updateOrderStatus(order.id, e.target.value, order.payment_status)"
                                            class="bg-zinc-950 border border-white/10 rounded-lg text-xs text-white py-1 px-2 focus:ring-1 focus:ring-admin-modern cursor-pointer">
                                            <option value="pending">Pending</option>
                                            <option value="active">Active</option>
                                            <option value="completed">Completed</option>
                                            <option value="expired">Expired</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </td>
                                    <td class="p-6">
                                        <select :value="order.payment_status || 'pending'" @change="e => updateOrderStatus(order.id, order.status, e.target.value)"
                                            class="bg-zinc-950 border border-white/10 rounded-lg text-xs py-1 px-2 focus:ring-1 focus:ring-admin-modern cursor-pointer"
                                            :class="order.payment_status === 'paid' ? 'text-green-400' : (order.payment_status === 'failed' ? 'text-red-400' : 'text-amber-400')">
                                            <option value="pending" class="text-white">Pending</option>
                                            <option value="paid" class="text-white">Paid</option>
                                            <option value="failed" class="text-white">Failed</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr v-if="customer.orders.length === 0">
                                    <td colspan="6"
                                        class="p-10 text-center text-zinc-500 font-bold uppercase tracking-widest">No
                                        orders placed yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Favorites -->
                <div class="space-y-6">
                    <h2 class="text-2xl font-black uppercase tracking-tighter text-primary">Wishlist</h2>
                    <div class="bg-zinc-900/40 border border-white/5 rounded-[2.5rem] p-8 space-y-6">
                        <div v-for="product in customer.favorites" :key="product.id"
                            class="flex items-center gap-4 group">
                            <div class="w-16 h-16 bg-zinc-800 rounded-2xl overflow-hidden border border-white/5">
                                <img :src="product.image"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-all" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white leading-tight">{{ product.name }}</p>
                                <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mt-1">R{{
                                    product.price }}</p>
                            </div>
                        </div>
                        <p v-if="customer.favorites.length === 0"
                            class="text-center text-zinc-500 font-bold uppercase tracking-widest py-10">Wishlist is
                            empty</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Customer Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 overflow-hidden flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm pointer-events-auto" @click="closeEditModal"></div>
            <div class="relative bg-zinc-950 border border-white/10 rounded-[2.5rem] w-full max-w-lg p-8 shadow-2xl space-y-6 pointer-events-auto">
                <div>
                    <h3 class="text-xl font-black text-white uppercase tracking-tight">Edit Customer Details</h3>
                    <p class="text-zinc-500 text-xs mt-1">Modify user metadata and admin privileges.</p>
                </div>

                <form @submit.prevent="submitEditForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">First Name</label>
                            <input v-model="editForm.first_name" type="text" required
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Last Name</label>
                            <input v-model="editForm.last_name" type="text" required
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern transition-all" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Email Address</label>
                        <input v-model="editForm.email" type="email" required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern transition-all" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Phone Number</label>
                            <input v-model="editForm.phone" type="text"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Company Name</label>
                            <input v-model="editForm.company_name" type="text"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern transition-all" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Country</label>
                            <input v-model="editForm.country" type="text"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">System Role</label>
                            <select v-model="editForm.role"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern cursor-pointer transition-all">
                                <option value="customer" class="bg-zinc-950">Customer</option>
                                <option value="admin" class="bg-zinc-950">Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">API Call Limit Override</label>
                        <input v-model="editForm.api_limit_override" type="number" placeholder="e.g. 5000 (leave empty for default)"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern transition-all" />
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="closeEditModal"
                            class="flex-1 py-3 px-4 bg-white/5 hover:bg-white/10 text-white rounded-xl font-bold uppercase tracking-wider text-xs transition-all border border-white/5">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editForm.processing"
                            class="flex-1 py-3 px-4 bg-admin-modern text-black rounded-xl font-black uppercase tracking-wider text-xs transition-all hover:opacity-90 disabled:opacity-50">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manual Order Modal -->
        <div v-if="isOrderModalOpen" class="fixed inset-0 z-50 overflow-hidden flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm pointer-events-auto" @click="closeOrderModal"></div>
            <div class="relative bg-zinc-950 border border-white/10 rounded-[2.5rem] w-full max-w-lg p-8 shadow-2xl space-y-6 pointer-events-auto">
                <div>
                    <h3 class="text-xl font-black text-white uppercase tracking-tight">Add Manual Order / Subscription</h3>
                    <p class="text-zinc-500 text-xs mt-1">Assign a product or subscription to this customer manually.</p>
                </div>

                <form @submit.prevent="submitOrderForm" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Select Product/Service</label>
                        <select v-model="orderForm.product_id" @change="e => handleProductChange(e.target.value)"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern cursor-pointer transition-all">
                            <option v-for="product in products" :key="product.id" :value="product.id" class="bg-zinc-950">
                                {{ product.name }} (R{{ product.price }})
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Order Amount (ZAR)</label>
                            <input v-model="orderForm.total_amount" type="number" step="0.01" required
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Billing Frequency</label>
                            <select v-model="orderForm.frequency"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern cursor-pointer transition-all">
                                <option value="monthly" class="bg-zinc-950">Monthly</option>
                                <option value="yearly" class="bg-zinc-950">Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Order Status</label>
                            <select v-model="orderForm.status"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern cursor-pointer transition-all">
                                <option value="pending" class="bg-zinc-950">Pending</option>
                                <option value="active" class="bg-zinc-950">Active</option>
                                <option value="completed" class="bg-zinc-950">Completed</option>
                                <option value="expired" class="bg-zinc-950">Expired</option>
                                <option value="cancelled" class="bg-zinc-950">Cancelled</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Payment Status</label>
                            <select v-model="orderForm.payment_status"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:ring-1 focus:ring-admin-modern focus:border-admin-modern cursor-pointer transition-all">
                                <option value="pending" class="bg-zinc-950">Pending</option>
                                <option value="paid" class="bg-zinc-950">Paid</option>
                                <option value="failed" class="bg-zinc-950">Failed</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="closeOrderModal"
                            class="flex-1 py-3 px-4 bg-white/5 hover:bg-white/10 text-white rounded-xl font-bold uppercase tracking-wider text-xs transition-all border border-white/5">
                            Cancel
                        </button>
                        <button type="submit" :disabled="orderForm.processing"
                            class="flex-1 py-3 px-4 bg-admin-modern text-black rounded-xl font-black uppercase tracking-wider text-xs transition-all hover:opacity-90 disabled:opacity-50">
                            Create Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
