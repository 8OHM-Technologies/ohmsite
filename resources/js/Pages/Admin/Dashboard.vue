<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ArrowUpRight, 
    ArrowDownRight, 
    ChevronRight, 
    TrendingUp, 
    Download,
    Eye,
    Edit3
} from 'lucide-vue-next';

const props = defineProps({
    stats: Array,
    recentProducts: Array,
    weeklySales: Array,
    growthRate: String
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <!-- Hero Section -->
        <div class="relative w-full min-h-[300px] lg:h-80 rounded-[2rem] lg:rounded-[3rem] overflow-hidden mb-8 lg:mb-12 bg-black group shadow-2xl">
            <img 
                src="/assets/images/hero-bg-standard.png" 
                alt="New Drop" 
                class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-1000"
            />
            <div class="absolute inset-0 bg-gradient-to-r from-black via-black/50 to-transparent"></div>
            
            <div class="relative h-full flex flex-col justify-center px-6 sm:px-12 py-10 lg:py-0 max-w-2xl z-20">
                <div class="inline-flex items-center px-3 py-1 bg-admin-modern text-black text-[10px] font-black uppercase tracking-widest rounded-full mb-6 self-start shadow-lg shadow-admin-modern/20">
                    <TrendingUp class="w-3 h-3 mr-2" />
                    Store Overview
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white italic uppercase tracking-tighter leading-[0.9] mb-4">
                    Elevating the <br class="hidden sm:block" /> sneaker game.
                </h1>
                <p class="text-zinc-400 text-sm sm:text-lg font-bold uppercase tracking-widest italic mb-8 max-w-md opacity-80">
                    Manage your inventory, track sales, and grow your sneaker empire.
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <Link 
                        :href="route('admin.products.index')"
                        class="w-full sm:w-auto bg-white text-black px-8 py-4 rounded-xl font-black uppercase tracking-widest text-xs hover:bg-admin-modern transition-all shadow-xl active:scale-95"
                    >
                        Manage Catalog
                    </Link>
                </div>
            </div>

            <!-- Floating Sneaker Image - Responsive handling -->
            <div class="absolute right-0 lg:right-12 top-1/2 -translate-y-1/2 w-64 h-64 sm:w-80 sm:h-80 lg:w-96 lg:h-96 pointer-events-none opacity-40 sm:opacity-100 group-hover:opacity-100 transition-opacity">
                <div class="relative w-full h-full">
                    <img 
                        v-if="recentProducts.length > 0"
                        :src="recentProducts[0].image" 
                        alt="Featured Sneaker" 
                        class="w-full h-full object-contain drop-shadow-[0_45px_45px_rgba(0,0,0,0.8)] animate-float"
                    />
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 lg:gap-8 mb-8 lg:mb-12">
            <div v-for="stat in stats" :key="stat.name" class="bg-zinc-900/40 p-6 lg:p-8 rounded-[2rem] border border-white/5 group hover:bg-zinc-900/60 transition-all duration-300">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-3.5 bg-zinc-800/80 border border-white/5 rounded-2xl group-hover:bg-admin-modern transition-all duration-300 shadow-xl group-hover:shadow-admin-modern/10">
                        <svg class="h-5 w-5 text-white group-hover:text-black transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="stat.icon" />
                        </svg>
                    </div>
                    <div 
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                        :class="[stat.trend === 'up' ? 'text-admin-modern bg-admin-modern/10' : 'text-rose-500 bg-rose-500/10']"
                    >
                        {{ stat.change }}
                        <ArrowUpRight v-if="stat.trend === 'up'" class="w-3 h-3" />
                        <ArrowDownRight v-else class="w-3 h-3" />
                    </div>
                </div>
                <div>
                    <p class="text-zinc-500 text-[10px] font-black uppercase tracking-[0.2em] mb-2">{{ stat.name }}</p>
                    <h3 class="text-3xl lg:text-4xl font-black text-white italic uppercase tracking-tighter">{{ stat.value }}</h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8 xl:items-start">
            <!-- Recent Products -->
            <div class="xl:col-span-2 bg-zinc-900/40 rounded-[2.5rem] p-6 sm:p-10 border border-white/5 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 lg:mb-12 gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-2 h-8 bg-admin-modern rounded-full shadow-lg shadow-admin-modern/20"></div>
                        <h2 class="text-2xl font-black italic uppercase tracking-tighter text-white">Recent Catalog Additions</h2>
                    </div>
                    <Link :href="route('admin.products.index')" class="inline-flex items-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 hover:text-white transition-all group">
                        Browse all 
                        <ChevronRight class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" />
                    </Link>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
                    <div v-for="product in recentProducts" :key="product.id" class="group relative bg-black/20 rounded-[2rem] p-6 hover:bg-zinc-800/40 transition-all duration-500 border border-white/5">
                        <div class="aspect-square rounded-3xl bg-zinc-900/50 overflow-hidden mb-6 flex items-center justify-center p-8 border border-white/5">
                            <img :src="product.image" :alt="product.name" class="w-full h-full object-contain group-hover:scale-110 group-hover:-rotate-6 transition-all duration-1000 drop-shadow-[0_20px_20px_rgba(0,0,0,0.4)]" />
                        </div>
                        <div class="flex justify-between items-end">
                            <div class="flex-1 mr-4">
                                <p class="text-[8px] font-black text-zinc-600 uppercase tracking-[0.2em] mb-1">In Stock: {{ product.stock }}</p>
                                <h3 class="font-black text-lg text-white uppercase italic tracking-tighter leading-tight">{{ product.name }}</h3>
                            </div>
                            <p class="font-black text-2xl text-white italic uppercase tracking-tighter border-b-2 border-admin-modern">€{{ Math.round(product.price) }}</p>
                        </div>
                        
                        <!-- Hover Actions -->
                        <div class="absolute top-8 right-8 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-4 group-hover:translate-x-0">
                            <Link :href="route('admin.products.edit', product.id)" class="p-3 bg-white text-black rounded-xl shadow-2xl hover:bg-admin-modern transition-all">
                                <Edit3 class="w-4 h-4" />
                            </Link>
                            <Link :href="route('shop.show', product.id)" class="p-3 bg-zinc-900 text-white rounded-xl shadow-2xl hover:bg-zinc-800 transition-all">
                                <Eye class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Analytics Sidebar -->
            <div class="bg-black rounded-[2.5rem] p-6 sm:p-10 border border-white/5 shadow-2xl relative overflow-hidden group">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-admin-modern opacity-[0.03] blur-[100px] pointer-events-none transition-opacity group-hover:opacity-5 group-hover:scale-150 duration-1000"></div>
                
                <h2 class="text-2xl font-black italic uppercase tracking-tighter mb-10 text-white flex items-center">
                    <TrendingUp class="w-5 h-5 mr-3 text-admin-modern" />
                    Growth Analytics
                </h2>
                
                <div class="space-y-10 relative z-10">
                    <!-- Custom Bar Chart -->
                    <div class="space-y-6">
                        <div class="h-64 flex items-end justify-between gap-1 sm:gap-2 px-2">
                            <div v-for="sale in weeklySales" :key="sale.day" class="flex-1 group relative">
                                <div 
                                    class="w-full bg-zinc-900 border border-white/5 rounded-t-xl transition-all duration-700 hover:bg-admin-modern/80 hover:shadow-[0_0_20px_rgba(159,232,112,0.2)]"
                                    :style="`height: ${sale.height}%` "
                                >
                                    <div class="absolute -top-12 left-1/2 -translate-x-1/2 bg-white text-black px-2.5 py-1.5 rounded-lg text-[10px] font-black opacity-0 group-hover:opacity-100 transition-all pointer-events-none shadow-2xl translate-y-2 group-hover:translate-y-0">
                                        €{{ sale.amount }}
                                    </div>
                                </div>
                                <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-[10px] font-black text-zinc-600 uppercase group-hover:text-white transition-colors">{{ sale.day }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Growth Progress -->
                    <div class="pt-10 border-t border-white/5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex flex-col">
                                <p class="text-zinc-500 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Performance Index</p>
                                <span class="text-white font-black text-3xl italic tracking-tighter">{{ growthRate }}</span>
                            </div>
                            <div class="p-3 bg-admin-modern/10 rounded-2xl">
                                <ArrowUpRight class="w-6 h-6 text-admin-modern" />
                            </div>
                        </div>
                        <div class="w-full h-1.5 bg-zinc-900 rounded-full overflow-hidden">
                            <div 
                                class="h-full rounded-full shadow-[0_0_20px_rgba(159,232,112,0.4)] transition-all duration-1000"
                                :class="[parseFloat(growthRate) >= 0 ? 'bg-admin-modern' : 'bg-rose-500']"
                                :style="{ width: Math.min(Math.abs(parseFloat(growthRate)), 100) + '%' }"
                            ></div>
                        </div>
                    </div>

                    <button class="w-full py-5 bg-white text-black rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all hover:bg-zinc-200 shadow-2xl flex items-center justify-center gap-3 group/btn active:scale-95">
                        <Download class="w-4 h-4 transition-transform group-hover/btn:-translate-y-1" />
                        Export Insights
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@keyframes float {
    0% { transform: translateY(0px) rotate(-5deg); }
    50% { transform: translateY(-15px) rotate(5deg); }
    100% { transform: translateY(0px) rotate(-5deg); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

/* Hide scrollbar for cleaner look */
::-webkit-scrollbar {
    width: 0px;
    background: transparent;
}
</style>
