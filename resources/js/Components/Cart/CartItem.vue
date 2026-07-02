<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { 
    Plus, 
    Minus, 
    Trash2, 
    Heart 
} from 'lucide-vue-next';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['update:quantity', 'remove', 'toggle-favorite']);

const formattedPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'EUR',
    }).format(price);
};

const hasSale = computed(() => props.item.product.sale_price !== null);
const lowStock = computed(() => props.item.product.stock > 0 && props.item.product.stock <= 5);
const outOfStock = computed(() => props.item.product.stock <= 0);

const handleQuantityChange = (newQty) => {
    if (newQty >= 1 && newQty <= props.item.product.stock) {
        emit('update:quantity', newQty);
    }
};
</script>

<template>
    <div class="flex py-6 space-x-4 border-b border-gray-200 dark:border-gray-800 last:border-0 group animate-in fade-in slide-in-from-bottom-2 duration-300">
        <!-- Image -->
        <div class="relative flex-shrink-0 w-24 h-24 overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800 sm:w-32 sm:h-32">
            <img 
                :src="item.product.image || '/assets/images/placeholder.png'" 
                :alt="item.product.name"
                class="object-cover object-center w-full h-full transition-transform duration-300 group-hover:scale-110"
                loading="lazy"
            />
            <div v-if="hasSale" class="absolute top-2 left-2 px-2 py-1 text-[10px] font-bold text-white uppercase bg-rose-500 rounded">
                Sale
            </div>
        </div>

        <!-- Details -->
        <div class="flex flex-col flex-1">
            <div class="flex justify-between text-base font-medium text-gray-900 dark:text-white">
                <div>
                    <h3 class="hover:text-indigo-600 transition-colors">
                        <Link :href="route('shop.show', item.product.id)">{{ item.product.name }}</Link>
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ item.options?.color || 'Standard' }} | {{ item.options?.size || 'One Size' }}
                    </p>
                </div>
                <div class="text-right">
                    <p v-if="hasSale" class="text-rose-500 font-bold">
                        {{ formattedPrice(item.unit_price) }}
                        <span class="block text-xs text-gray-400 line-through">{{ formattedPrice(item.product.price) }}</span>
                    </p>
                    <p v-else class="font-bold">{{ formattedPrice(item.unit_price) }}</p>
                </div>
            </div>

            <div class="flex items-center justify-between flex-1 mt-4">
                <!-- Quantity Stepper -->
                <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-full bg-white dark:bg-gray-900 overflow-hidden">
                    <button 
                        @click="handleQuantityChange(item.quantity - 1)"
                        :disabled="item.quantity <= 1"
                        class="p-2 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-25 transition-colors"
                    >
                        <Minus class="w-4 h-4" />
                    </button>
                    <span class="px-4 py-1 text-sm font-medium w-12 text-center select-none">{{ item.quantity }}</span>
                    <button 
                        @click="handleQuantityChange(item.quantity + 1)"
                        :disabled="item.quantity >= item.product.stock"
                        class="p-2 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-25 transition-colors"
                    >
                        <Plus class="w-4 h-4" />
                    </button>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-4">
                    <button 
                        @click="emit('toggle-favorite')"
                        class="p-2 text-gray-400 hover:text-rose-500 transition-colors"
                        title="Save for later"
                    >
                        <Heart class="w-5 h-5" />
                    </button>
                    <button 
                        @click="emit('remove')"
                        class="p-2 text-gray-400 hover:text-rose-500 transition-colors"
                        title="Remove item"
                    >
                        <Trash2 class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Status Badges -->
            <div class="mt-2 flex space-x-2">
                <span v-if="lowStock" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                    Low stock: Only {{ item.product.stock }} left
                </span>
                <span v-if="outOfStock" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">
                    Out of stock
                </span>
            </div>
        </div>
    </div>
</template>
