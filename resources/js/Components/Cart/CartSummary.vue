<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import {
    Ticket,
    X,
    ArrowRight,
    Info,
    BadgeCheck
} from 'lucide-vue-next';

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['apply-discount', 'remove-discount']);

const discountForm = useForm({
    code: '',
});

const formattedPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'ZAR',
    }).format(price);
};

const handleApplyDiscount = () => {
    if (discountForm.code) {
        emit('apply-discount', discountForm.code);
        discountForm.code = '';
    }
};
</script>

<template>
    <div class="bg-zinc-900 rounded-[3rem] p-8 lg:p-10 sticky top-40 border border-white/5 shadow-2xl">
        <h2 class="text-2xl font-black uppercase tracking-tighter text-white mb-8">Order Summary</h2>

        <!-- Discount Input -->
        <div class="mb-10">
            <label for="discount" class="block text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-3">Have
                a
                discount code?</label>
            <div v-if="summary.discount"
                class="flex items-center justify-between p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl">
                <div class="flex items-center">
                    <BadgeCheck class="w-5 h-5 text-emerald-500 mr-3" />
                    <span class="text-sm font-black text-emerald-500 uppercase tracking-widest">{{ summary.discount.code
                    }}</span>
                </div>
                <button @click="emit('remove-discount')" class="text-emerald-500 hover:text-white transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>
            <div v-else class="relative">
                <input v-model="discountForm.code" type="text" id="discount" placeholder="Enter code"
                    class="block w-full px-6 py-4 bg-black border border-white/5 rounded-2xl text-xs font-bold uppercase tracking-widest text-white focus:ring-1 focus:ring-white/20 transition-all placeholder:text-zinc-700 placeholder:normal-case"
                    @keyup.enter="handleApplyDiscount" />
                <button @click="handleApplyDiscount"
                    class="absolute right-2 top-2 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-black bg-white rounded-xl hover:bg-zinc-200 transition-all">
                    Apply
                </button>
            </div>
        </div>

        <!-- Totals -->
        <div class="space-y-5 mb-10">
            <div class="flex justify-between text-xs font-bold uppercase tracking-widest">
                <span class="text-zinc-500">Subtotal</span>
                <span class="text-white">{{ formattedPrice(summary.subtotal) }}</span>
            </div>

            <div v-if="summary.discount > 0" class="flex justify-between text-xs font-bold uppercase tracking-widest">
                <span class="text-emerald-500">Discount</span>
                <span class="text-emerald-500">-{{ formattedPrice(summary.discount) }}</span>
            </div>

            <div class="pt-6 border-t border-white/5 flex justify-between items-end">
                <span class="text-xl font-black uppercase tracking-tighter text-white">Total</span>
                <span class="text-3xl font-black uppercase tracking-tighter text-white">{{ formattedPrice(summary.total)
                }}</span>
            </div>
        </div>

        <!-- Checkout Button -->
        <Link :href="route('checkout.index')" as="button"
            class="w-full flex items-center justify-center px-8 py-6 bg-white text-black rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-zinc-200 transition-all duration-300 group shadow-2xl active:scale-95">
            Checkout
            <ArrowRight class="w-5 h-5 ml-3 transition-transform group-hover:translate-x-1" />
        </Link>

        <!-- Trust Badges -->
        <div class="mt-10">
            <p class="text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-700 mt-6 leading-relaxed">
                🔒 Secure Payments using PayStack
            </p>
        </div>
    </div>
</template>
