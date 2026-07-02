<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { useCartStore } from '@/Stores/useCartStore';
import { 
    Filter, 
    X, 
    ChevronDown, 
    ShoppingCart, 
    Heart, 
    Search,
    LayoutGrid,
    Check
} from 'lucide-vue-next';

const props = defineProps({
    products: Object,
    categories: Array,
    brands: Array,
    filters: Object,
    auth: Object
});

const cartStore = useCartStore();
const isFilterDrawerOpen = ref(false);

const activeFilters = ref({
    search: props.filters.search || '',
    category: props.filters.category || '',
    brand: props.filters.brand || '',
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
    sort: props.filters.sort || 'newest'
});

const applyFilters = () => {
    router.get(route('shop.index'), activeFilters.value, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isFilterDrawerOpen.value = false;
        }
    });
};

watch(() => activeFilters.value.sort, applyFilters);

const toggleFavorite = (product) => {
    if (!props.auth.user) {
        router.visit(route('login'));
        return;
    }

    router.post(route('favorites.toggle', product.id), {}, {
        preserveScroll: true,
    });
};

// Handle body scroll when drawer is open
watch(isFilterDrawerOpen, (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
});
</script>

<template>
    <MainLayout :auth="auth" title="Shop">
        <div class="relative">
            <!-- Mobile Header / Sort / Filter Trigger -->
            <div class="lg:hidden flex items-center justify-between mb-8 sticky top-24 z-30 bg-black/80 backdrop-blur-xl p-4 rounded-3xl border border-white/5">
                <button 
                    @click="isFilterDrawerOpen = true"
                    class="flex items-center gap-2 px-4 py-2 bg-white text-black rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl active:scale-95 transition-all"
                >
                    <Filter class="w-3.5 h-3.5" />
                    Filters
                </button>
                
                <div class="flex items-center gap-4">
                    <span class="text-[8px] font-black uppercase text-zinc-500 tracking-[0.3em]">Sort</span>
                    <div class="relative group">
                        <select 
                            v-model="activeFilters.sort" 
                            class="appearance-none !bg-none bg-zinc-900 border border-white/5 rounded-xl px-4 py-2 text-[10px] font-black uppercase tracking-widest focus:ring-1 focus:ring-white/10 text-white pr-10 cursor-pointer"
                        >
                            <option value="featured">Featured</option>
                            <option value="newest">Newest</option>
                            <option value="price_low">Price ↑</option>
                            <option value="price_high">Price ↓</option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 w-3 h-3 text-zinc-500 pointer-events-none" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-8 xl:gap-16">
                <!-- Desktop Sidebar Filters -->
                <aside class="hidden lg:block w-72 flex-shrink-0 space-y-16 sticky top-40 h-fit">
                    <div>
                        <div class="flex items-end gap-3 mb-10">
                            <h3 class="text-4xl font-black uppercase italic tracking-tighter leading-none">Filters</h3>
                            <div class="h-1 w-10 bg-admin-modern mb-1 shadow-[0_0_15px_rgba(229,255,69,0.4)]"></div>
                        </div>
                        
                        <!-- Categories -->
                        <div class="space-y-8 mb-16">
                            <div class="flex items-center justify-between">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.4em] text-zinc-700">Collection</h4>
                            </div>
                            <div class="space-y-4">
                                <button 
                                    @click="activeFilters.category = ''; applyFilters()"
                                    class="group flex items-center justify-between w-full text-[11px] font-black uppercase tracking-[0.2em] transition-all duration-300"
                                    :class="activeFilters.category === '' ? 'text-white translate-x-2' : 'text-zinc-600 hover:text-zinc-400'"
                                >
                                    <span class="flex items-center gap-3">
                                        <div v-if="activeFilters.category === ''" class="w-1.5 h-1.5 bg-admin-modern rounded-full shadow-[0_0_10px_rgba(229,255,69,0.5)]"></div>
                                        All Styles
                                    </span>
                                    <span class="text-[8px] opacity-20 group-hover:opacity-40">→</span>
                                </button>
                                <button 
                                    v-for="category in categories" 
                                    :key="category.id"
                                    @click="activeFilters.category = category.name; applyFilters()"
                                    class="group flex items-center justify-between w-full text-[11px] font-black uppercase tracking-[0.2em] transition-all duration-300"
                                    :class="activeFilters.category === category.name ? 'text-white translate-x-2' : 'text-zinc-600 hover:text-zinc-400'"
                                >
                                    <span class="flex items-center gap-3">
                                        <div v-if="activeFilters.category === category.name" class="w-1.5 h-1.5 bg-admin-modern rounded-full shadow-[0_0_10px_rgba(229,255,69,0.5)]"></div>
                                        {{ category.name }}
                                    </span>
                                    <span class="text-[8px] opacity-20 group-hover:opacity-40">→</span>
                                </button>
                            </div>
                        </div>

                        <!-- Brands -->
                        <div class="space-y-8 mb-16">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.4em] text-zinc-700">The Houses</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <button 
                                    v-for="brand in brands" 
                                    :key="brand.id"
                                    @click="activeFilters.brand = brand.name; applyFilters()"
                                    :class="activeFilters.brand === brand.name ? 'border-white bg-white/[0.03] shadow-2xl shadow-white/5 scale-95' : 'border-white/5 hover:border-white/10 bg-zinc-900/20'"
                                    class="border p-5 rounded-[2rem] transition-all duration-500 group aspect-square flex items-center justify-center relative overflow-hidden"
                                >
                                    <div v-if="activeFilters.brand === brand.name" class="absolute top-2 right-2 w-1.5 h-1.5 bg-admin-modern rounded-full"></div>
                                    <img :src="brand.logo" class="w-full h-full object-contain transition-all duration-700 brightness-200" :class="activeFilters.brand === brand.name ? 'opacity-100' : 'opacity-20 grayscale group-hover:opacity-50 group-hover:grayscale-0'" />
                                </button>
                            </div>
                            <button 
                                v-if="activeFilters.brand"
                                @click="activeFilters.brand = ''; applyFilters()"
                                class="w-full py-3 bg-zinc-900/50 rounded-xl text-[9px] font-black uppercase tracking-[0.3em] text-zinc-600 hover:text-white border border-white/5 transition-all"
                            >
                                Reset Brands
                            </button>
                        </div>

                        <!-- Price Range -->
                        <div class="space-y-8">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.4em] text-zinc-700">Valuation</h4>
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-2">
                                        <span class="text-[8px] font-black text-zinc-800 uppercase tracking-widest ml-1">Minimum</span>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-zinc-700 italic">€</span>
                                            <input type="number" v-model="activeFilters.min_price" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 pl-8 pr-4 text-xs font-black text-white focus:ring-1 focus:ring-white/10 transition-all" />
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <span class="text-[8px] font-black text-zinc-800 uppercase tracking-widest ml-1">Maximum</span>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-zinc-700 italic">€</span>
                                            <input type="number" v-model="activeFilters.max_price" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 pl-8 pr-4 text-xs font-black text-white focus:ring-1 focus:ring-white/10 transition-all" />
                                        </div>
                                    </div>
                                </div>
                                <button @click="applyFilters" class="w-full bg-white text-black py-5 rounded-[2rem] font-black uppercase text-[10px] tracking-[0.3em] hover:bg-admin-modern transition-all shadow-2xl active:scale-95">
                                    Filter Results
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Product Grid Area -->
                <div class="flex-1">
                    <!-- Desktop Header -->
                    <div class="hidden lg:flex justify-between items-end mb-16">
                        <div>
                            <h2 class="text-8xl font-black uppercase italic tracking-tighter leading-none mb-6">The Vault</h2>
                            <div class="flex items-center gap-6">
                                <span class="bg-white text-black px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-2xl">{{ products.total }} Silhouettes</span>
                                <div class="h-[1px] w-32 bg-white/5"></div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-8 bg-zinc-900/30 backdrop-blur-xl rounded-full py-3 px-8 border border-white/5">
                            <div class="flex items-center gap-3">
                                <span class="text-[9px] font-black uppercase tracking-[0.4em] text-zinc-600">Sort By:</span>
                                <div class="relative group">
                                    <select 
                                        v-model="activeFilters.sort" 
                                        class="appearance-none !bg-none bg-transparent border-none text-[10px] font-black uppercase tracking-[0.3em] focus:ring-0 cursor-pointer text-white p-0 pr-6"
                                    >
                                        <option value="featured" class="bg-zinc-900 text-white">Featured</option>
                                        <option value="newest" class="bg-zinc-900 text-white">New Arrivals</option>
                                        <option value="price_low" class="bg-zinc-900 text-white">Price Low</option>
                                        <option value="price_high" class="bg-zinc-900 text-white">Price High</option>
                                    </select>
                                    <ChevronDown class="absolute right-0 top-1/2 -translate-y-1/2 w-3 h-3 text-zinc-500 pointer-events-none group-hover:text-white transition-colors" />
                                </div>
                            </div>
                            <div class="h-4 w-px bg-white/10"></div>
                            <div class="flex items-center gap-2">
                                <button class="p-2 text-white bg-white/5 rounded-full border border-white/10"><LayoutGrid class="w-3.5 h-3.5" /></button>
                                <button class="p-2 text-zinc-600 hover:text-zinc-300 transition-colors"><Filter class="w-3.5 h-3.5" /></button>
                            </div>
                        </div>
                    </div>

                    <!-- Main Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 sm:gap-8 xl:gap-10">
                        <div v-for="product in products.data" :key="product.id" class="group relative flex flex-col">
                            <!-- Favorite Button -->
                            <button 
                                @click.stop="toggleFavorite(product)"
                                :class="[
                                    'absolute top-4 right-4 z-20 p-3 rounded-full backdrop-blur-md transition-all duration-300',
                                    product.is_favorited ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/20' : 'bg-black/20 text-white hover:bg-white hover:text-black opacity-0 group-hover:opacity-100'
                                ]"
                            >
                                <Heart class="w-4 h-4" :fill="product.is_favorited ? 'currentColor' : 'none'" />
                            </button>

                            <!-- Product Link -->
                            <Link :href="route('shop.show', product.id)" class="flex-1 flex flex-col">
                                <div class="aspect-[4/5] bg-zinc-900 rounded-[2rem] sm:rounded-[3rem] p-8 flex items-center justify-center relative overflow-hidden border border-white/5 group-hover:border-white/20 transition-all duration-700 shadow-2xl">
                                    <!-- Dynamic BG Gradient based on brand color or static -->
                                    <div class="absolute inset-0 bg-gradient-to-br from-zinc-800/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                                    <img :src="product.image" class="w-full h-full object-contain relative z-10 transition-all duration-700 group-hover:scale-110 group-hover:-rotate-6 drop-shadow-[0_25px_25px_rgba(0,0,0,0.6)]" />
                                    
                                    <!-- Sale Badge -->
                                    <div v-if="product.sale_price && parseFloat(product.sale_price) < parseFloat(product.price)" class="absolute bottom-6 left-6 z-20 bg-admin-modern text-black px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-xl">
                                        OFFER
                                    </div>
                                </div>

                                <div class="mt-6 px-4 flex-1 flex flex-col">
                                    <div class="flex justify-between items-start gap-4 mb-2">
                                        <h4 class="font-black text-lg sm:text-xl text-white uppercase italic tracking-tighter leading-tight">{{ product.name }}</h4>
                                        <span class="text-[8px] font-black uppercase tracking-widest text-zinc-600 border border-white/10 px-2 py-1 rounded-md shrink-0">
                                            {{ product.brands?.[0]?.name || 'Premium' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <p class="text-white font-black text-lg tracking-widest italic">€{{ Math.round(product.sale_price || product.price) }}</p>
                                        <p v-if="product.sale_price && parseFloat(product.sale_price) < parseFloat(product.price)" class="text-zinc-700 line-through font-bold text-xs tracking-widest">€{{ Math.round(product.price) }}</p>
                                    </div>
                                </div>
                            </Link>

                            <!-- Quick Action Area -->
                            <div class="mt-6 px-4">
                                <Link 
                                    :href="route('shop.show', product.id)"
                                    class="w-full bg-white text-black py-4 rounded-2xl flex items-center justify-center font-black uppercase text-[10px] tracking-[0.2em] hover:bg-admin-modern transition-all duration-300 transform translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 shadow-2xl"
                                >
                                    Select Size
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="products.links.length > 3" class="mt-24 flex justify-center flex-wrap gap-2">
                        <template v-for="(link, k) in products.links" :key="k">
                            <div v-if="link.url === null" class="px-5 py-3 text-zinc-700 text-[10px] font-black uppercase opacity-50" v-html="link.label"></div>
                            <Link v-else 
                                :href="link.url" 
                                class="px-5 py-3 rounded-2xl text-[10px] font-black uppercase transition-all min-w-[3rem] text-center"
                                :class="link.active ? 'bg-white text-black shadow-xl shadow-white/5' : 'text-zinc-500 hover:text-white hover:bg-white/5'"
                                v-html="link.label"
                            />
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div v-if="products.data.length === 0" class="py-40 text-center flex flex-col items-center">
                        <div class="p-8 bg-zinc-900 rounded-full mb-8">
                            <ShoppingBag class="w-12 h-12 text-zinc-700" />
                        </div>
                        <h3 class="text-4xl font-black uppercase italic tracking-tighter text-zinc-800 mb-4">No results found</h3>
                        <p class="text-zinc-600 font-bold uppercase tracking-widest text-xs">Try adjusting your filters or search query</p>
                        <button @click="activeFilters = {search:'', category:'', brand:'', min_price:'', max_price:'', sort:'newest'}; applyFilters()" class="mt-8 text-white border-b border-white/20 pb-1 text-[10px] font-black uppercase tracking-widest hover:border-white transition-all">Clear All Filters</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Filter Drawer -->
        <Transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition duration-400 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div v-if="isFilterDrawerOpen" class="fixed inset-0 z-[100] lg:hidden">
                <div class="absolute inset-0 bg-black/95 backdrop-blur-xl" @click="isFilterDrawerOpen = false"></div>
                
                <div class="absolute inset-y-0 right-0 w-full sm:w-[450px] bg-zinc-900 shadow-2xl flex flex-col border-l border-white/5">
                    <div class="p-8 flex items-center justify-between border-b border-white/5">
                        <h3 class="text-2xl font-black uppercase italic tracking-tighter text-white">Refine Selection</h3>
                        <button @click="isFilterDrawerOpen = false" class="p-2 text-zinc-500 hover:text-white"><X class="w-6 h-6" /></button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8 space-y-12 pb-40">
                        <!-- Categories -->
                        <div class="space-y-6">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500">Shop by Category</h4>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="cat in [{name: 'All', val: ''}, ...categories.map(c => ({name: c.name, val: c.name}))]" 
                                    :key="cat.name"
                                    @click="activeFilters.category = cat.val"
                                    :class="activeFilters.category === cat.val ? 'bg-white text-black' : 'bg-zinc-800 text-zinc-400 border border-white/5'"
                                    class="px-5 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                                >
                                    {{ cat.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Brands -->
                        <div class="space-y-6">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500">Brands</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <button 
                                    v-for="brand in brands" 
                                    :key="brand.id"
                                    @click="activeFilters.brand = brand.name"
                                    :class="activeFilters.brand === brand.name ? 'bg-white/10 border-white' : 'bg-black/40 border-white/5'"
                                    class="border rounded-2xl transition-all flex items-center justify-center p-6 aspect-square"
                                >
                                    <img :src="brand.logo" class="h-6 w-full object-contain" :class="activeFilters.brand === brand.name ? 'opacity-100 grayscale-0' : 'opacity-40 grayscale'" />
                                </button>
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div class="space-y-6">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500">Price Range</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <span class="text-[9px] font-bold text-zinc-600 uppercase">Min Price</span>
                                    <input type="number" v-model="activeFilters.min_price" class="w-full bg-black border-white/5 rounded-xl py-4 px-4 text-xs font-bold text-white" />
                                </div>
                                <div class="space-y-2">
                                    <span class="text-[9px] font-bold text-zinc-600 uppercase">Max Price</span>
                                    <input type="number" v-model="activeFilters.max_price" class="w-full bg-black border-white/5 rounded-xl py-4 px-4 text-xs font-bold text-white" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 bg-zinc-900 border-t border-white/5 fixed bottom-0 w-full sm:w-[450px]">
                        <button 
                            @click="applyFilters"
                            class="w-full bg-white text-black py-5 rounded-2xl font-black uppercase text-xs tracking-[0.2em] shadow-2xl active:scale-95 transition-all"
                        >
                            Show Results
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </MainLayout>
</template>

<style scoped>
/* Hide scrollbar for cleaner look */
::-webkit-scrollbar {
    width: 0px;
    background: transparent;
}
</style>
