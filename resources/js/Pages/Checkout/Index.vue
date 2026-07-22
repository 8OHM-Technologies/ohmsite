<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    cart: Object,
    summary: Object,
    auth: Object,
});

const formattedPrice = (price) => {
    return new Intl.NumberFormat('en-EU', {
        style: 'currency',
        currency: 'ZAR',
    }).format(price || 0);
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

const form = useForm({
    email: props.auth?.user?.email || '',
    first_name: props.auth?.user?.first_name || '',
    last_name: props.auth?.user?.last_name || '',
    company_name: props.auth?.user?.company_name || '',
    country: props.auth?.user?.country || 'South Africa',
    phone: props.auth?.user?.phone || '',
    save_info: false,
});

const submit = () => {
    form.post(route('checkout.store'));
};


</script>

<template>
    <MainLayout :auth="auth" title="Checkout">


        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">
                <!-- Left Side: Form -->
                <div class="lg:col-span-7 space-y-12">
                    <!-- Contact Information -->
                    <section class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-black uppercase tracking-tighter">Contact</h2>
                            <p v-if="!auth?.user" class="text-xs font-bold uppercase tracking-widest text-zinc-500">
                                Already have an account?
                                <Link :href="route('login')" class="text-white hover:underline">Log in</Link>
                            </p>
                        </div>
                        <div class="space-y-2">
                            <TextInput id="email" type="email" v-model="form.email"
                                class="w-full bg-zinc-900/50 border-white/5 py-4 px-6 rounded-xl text-sm font-bold"
                                placeholder="Email" required />
                            <InputError :message="form.errors.email" />
                        </div>
                    </section>

                    <!-- Account Details -->
                    <section class="space-y-6">
                        <h2 class="text-2xl font-black uppercase tracking-tighter">Account Details</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <TextInput id="first_name" type="text" v-model="form.first_name"
                                    class="w-full bg-zinc-900/50 border-white/5 py-4 px-6 rounded-xl text-sm font-bold text-white placeholder-zinc-500"
                                    placeholder="First Name" required />
                                <InputError :message="form.errors.first_name" />
                            </div>
                            <div class="space-y-2">
                                <TextInput id="last_name" type="text" v-model="form.last_name"
                                    class="w-full bg-zinc-900/50 border-white/5 py-4 px-6 rounded-xl text-sm font-bold text-white placeholder-zinc-500"
                                    placeholder="Last Name" required />
                                <InputError :message="form.errors.last_name" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <TextInput id="company_name" type="text" v-model="form.company_name"
                                    class="w-full bg-zinc-900/50 border-white/5 py-4 px-6 rounded-xl text-sm font-bold text-white placeholder-zinc-500"
                                    placeholder="Company Name (Optional)" />
                                <InputError :message="form.errors.company_name" />
                            </div>
                            <div class="space-y-2">
                                <TextInput id="phone" type="text" v-model="form.phone"
                                    class="w-full bg-zinc-900/50 border-white/5 py-4 px-6 rounded-xl text-sm font-bold text-white placeholder-zinc-500"
                                    placeholder="Phone" required />
                                <InputError :message="form.errors.phone" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <TextInput id="country" type="text" v-model="form.country"
                                class="w-full bg-zinc-900/50 border-white/5 py-4 px-6 rounded-xl text-sm font-bold text-white placeholder-zinc-500"
                                placeholder="Country (Optional)" />
                            <InputError :message="form.errors.country" />
                        </div>

                        <div v-if="auth?.user" class="flex items-center space-x-3 pt-2">
                            <input id="save_info" type="checkbox" v-model="form.save_info"
                                class="w-5 h-5 rounded border-white/10 bg-zinc-900/50 text-white focus:ring-0 focus:ring-offset-0 checked:bg-white checked:border-white" />
                            <label for="save_info" class="text-xs font-bold uppercase tracking-widest text-zinc-400 select-none cursor-pointer">
                                Save this information to my profile
                            </label>
                        </div>
                    </section>

                    <!-- Payment -->
                    <section class="space-y-6">
                        <h2 class="text-2xl font-black uppercase tracking-tighter">Payment</h2>
                        <div class="bg-zinc-900/50 border border-white/5 rounded-2xl p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-5 h-5 rounded-full border-4 border-white"></div>
                                    <span class="text-sm font-black uppercase tracking-widest">Paystack</span>
                                </div>
                                <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </section>

                    <div class="pt-8">
                        <button @click="submit" :disabled="form.processing"
                            class="w-full bg-white text-black py-6 rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-zinc-200 transition-all active:scale-[0.98] disabled:opacity-50">
                            Complete order
                        </button>
                    </div>
                </div>

                <!-- Right Side: Order Summary -->
                <div class="lg:col-span-5">
                    <div v-if="cart && summary" class="sticky top-40 space-y-8">
                        <div class="bg-zinc-900/30 border border-white/5 rounded-3xl p-8 backdrop-blur-xl">
                            <h3 class="text-xl font-black uppercase tracking-tighter mb-8">Order Summary</h3>

                            <div class="space-y-6 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="item in (cart?.data?.items || cart?.items || [])" :key="item.id"
                                    class="flex space-x-4">
                                    <div
                                        class="w-20 h-20 bg-zinc-900 rounded-xl overflow-hidden flex-shrink-0 border border-white/5">
                                        <img :src="item.product.image" :alt="item.product.name"
                                            class="w-full h-full object-cover" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-black uppercase tracking-widest truncate">{{
                                            item.product.name }}</h4>
                                        <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1">
                                            Qty: {{ item.quantity }}
                                            <span v-if="item.options?.dataset"> • Dataset: {{ getDatasetLabel(item.options.dataset) }}</span>
                                            <span v-if="item.options?.frequency"> • Billing: {{ getFrequencyLabel(item.options.frequency) }}</span>
                                        </p>
                                        <p class="text-xs font-black mt-2">{{ formattedPrice(item.subtotal) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 pt-8 border-t border-white/5 space-y-4">
                                <div
                                    class="flex justify-between text-xs font-bold uppercase tracking-widest text-zinc-400">
                                    <span>Subtotal</span>
                                    <span>{{ formattedPrice(summary.subtotal) }}</span>
                                </div>
                                <div
                                    class="flex justify-between text-lg font-black uppercase tracking-widest pt-4 border-t border-white/10">
                                    <span>Total</span>
                                    <span>{{ formattedPrice(summary.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}


</style>
