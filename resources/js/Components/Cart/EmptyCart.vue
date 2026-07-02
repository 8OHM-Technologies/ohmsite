<script setup>
import { Link } from '@inertiajs/vue3';
import { ShoppingBag } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: 'Your cart is empty'
    },
    message: {
        type: String,
        default: "Looks like you haven't added anything to your cart yet. Let's find something you love!"
    }
});
</script>

<template>
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-full mb-6">
            <ShoppingBag class="w-16 h-16 text-gray-400" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ title }}</h2>
        <p class="text-gray-600 dark:text-gray-400 max-w-sm mb-8">
            {{ message }}
        </p>
        <Link
            :href="route('shop.index')"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-200"
        >
            Explore Shop
        </Link>
    </div>
</template>
