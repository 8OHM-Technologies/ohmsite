<script setup>
import { Link } from '@inertiajs/vue3';
import { ShoppingCart, Heart } from 'lucide-vue-next';

defineProps({
    items: {
        type: Array,
        default: () => [],
    }
});

const emit = defineEmits(['move-to-cart']);

const formattedPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'EUR',
    }).format(price);
};
</script>

<template>
    <div v-if="items.length > 0" class="mt-16 animate-in fade-in duration-700">
        <div class="flex items-center mb-6">
            <Heart class="w-6 h-6 text-rose-500 mr-2" />
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Saved for later</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
                v-for="item in items" 
                :key="item.id"
                class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4 flex flex-col group transition-all hover:shadow-xl"
            >
                <div class="relative aspect-square mb-4 overflow-hidden rounded-xl bg-gray-50 dark:bg-gray-800">
                    <img 
                        :src="item.image" 
                        :alt="item.name" 
                        class="object-cover w-full h-full transition-transform duration-500 group-hover:scale-110"
                    />
                </div>
                
                <h3 class="font-bold text-gray-900 dark:text-white mb-1 truncate">{{ item.name }}</h3>
                <p class="text-sm text-indigo-600 dark:text-indigo-400 font-bold mb-4">{{ formattedPrice(item.price) }}</p>

                <button 
                    @click="emit('move-to-cart', item.id)"
                    class="mt-auto flex items-center justify-center w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-bold hover:bg-indigo-600 dark:hover:bg-indigo-500 hover:text-white transition-all duration-300"
                >
                    <ShoppingCart class="w-4 h-4 mr-2" />
                    Move to Cart
                </button>
            </div>
        </div>
    </div>
</template>
