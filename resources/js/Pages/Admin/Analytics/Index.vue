<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    stats: Object,
    topProducts: Array,
    recentActivity: Array,
    liveStats: Object,
    trafficSources: Object,
    chartLabels: Array,
    currentTimeframe: String
});

const timeframe = ref(props.currentTimeframe || '30 Days');

const changeTimeframe = (t) => {
    timeframe.value = t;
    router.get(route('admin.analytics.index'), { timeframe: t }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const revenueChartOptions = {
    chart: {
        type: 'area',
        toolbar: { show: false },
        sparkline: { enabled: false },
        background: 'transparent',
        animations: { enabled: true, easing: 'easeinout', speed: 800 }
    },
    stroke: { curve: 'smooth', width: 3, colors: ['#9FE870'] },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [20, 100, 100, 100]
        }
    },
    xaxis: {
        categories: props.chartLabels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#71717a', fontSize: '12px' } }
    },
    yaxis: { show: false },
    grid: { show: false },
    dataLabels: { enabled: false },
    tooltip: {
        theme: 'dark',
        x: { show: true },
        y: { formatter: (val) => `€${val.toLocaleString()}` }
    }
};

const revenueSeries = [{
    name: 'Revenue',
    data: props.stats.revenue.chart
}];

const trafficOptions = {
    chart: { type: 'donut', background: 'transparent' },
    colors: ['#9FE870', '#38bdf8', '#a855f7', '#f43f5e'],
    labels: Object.keys(props.trafficSources),
    stroke: { show: false },
    legend: { show: false },
    plotOptions: {
        pie: {
            donut: {
                size: '75%',
                labels: {
                    show: true,
                    name: { show: true, color: '#fff', fontSize: '12px' },
                    value: { show: true, color: '#71717a', fontSize: '18px', fontWeight: 'bold' },
                    total: { show: true, label: 'Traffic', color: '#fff', fontSize: '12px' }
                }
            }
        }
    },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' }
};

const trafficSeries = Object.values(props.trafficSources);

const formatCurrency = (val) => {
    if (typeof val === 'string' && val.startsWith('€')) return val;
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EUR' }).format(val);
};

const getStatusIcon = (type) => {
    switch (type) {
        case 'order': return 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z';
        case 'refund': return 'M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z';
        case 'customer': return 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z';
        default: return '';
    }
};
</script>

<template>
    <Head title="Analytics Dashboard" />

    <AdminLayout>
        <div class="space-y-10 animate-in fade-in duration-1000">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-white tracking-tighter uppercase italic">Intelligence <span class="text-admin-modern">Center</span></h1>
                    <p class="text-zinc-500 mt-2 font-bold uppercase tracking-widest text-[10px]">Real-time performance metrics and market analysis</p>
                </div>

                <div class="flex items-center gap-4 bg-zinc-900/50 backdrop-blur-xl border border-white/5 p-1 rounded-2xl">
                    <button v-for="t in ['Today', '7 Days', '30 Days', '12 Months']" :key="t"
                        @click="changeTimeframe(t)"
                        :class="[timeframe === t ? 'bg-white text-black font-black' : 'text-zinc-500 hover:text-white']"
                        class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                    >
                        {{ t }}
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="(item, key) in [
                    { label: 'Revenue Gain', value: formatCurrency(stats.revenue.total), growth: stats.revenue.growth, icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
                    { label: 'Volume Orders', value: stats.orders.total, growth: stats.orders.growth, icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' },
                    { label: 'Target CR', value: stats.conversion_rate.value + '%', growth: stats.conversion_rate.growth, icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' },
                    { label: 'Loyalty Index', value: stats.returning_customers.value + '%', growth: stats.returning_customers.growth, icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' }
                ]" :key="key" class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2.5rem] group hover:bg-zinc-900/60 transition-all duration-500">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon" /></svg>
                        </div>
                        <span :class="item.growth >= 0 ? 'text-admin-modern bg-admin-modern/10' : 'text-red-400 bg-red-400/10'" class="text-[10px] font-black px-2 py-1 rounded-lg">
                            {{ item.growth >= 0 ? '+' : '' }}{{ item.growth }}%
                        </span>
                    </div>
                    <p class="text-[10px] font-black text-zinc-600 uppercase tracking-widest">{{ item.label }}</p>
                    <p class="text-3xl font-black text-white mt-1 italic tracking-tighter">{{ item.value }}</p>
                </div>
            </div>

            <!-- Main Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Revenue Trend -->
                <div class="lg:col-span-2 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-white uppercase italic tracking-tighter">Revenue Trajectory</h3>
                            <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1">12-Month Performance Cycle</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black text-admin-modern tracking-tighter">{{ formatCurrency(stats.revenue.total) }}</p>
                            <p class="text-[9px] font-black text-zinc-600 uppercase tracking-widest">Active Period</p>
                        </div>
                    </div>
                    <div class="flex-1 min-h-[350px]">
                        <VueApexCharts width="100%" height="350" :options="revenueChartOptions" :series="revenueSeries" />
                    </div>
                </div>

                <!-- Traffic Sources -->
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col">
                    <h3 class="text-xl font-black text-white uppercase italic tracking-tighter mb-8">Origin Metrics</h3>
                    <div class="flex-1 flex flex-col items-center justify-center">
                        <VueApexCharts width="250" :options="trafficOptions" :series="trafficSeries" />
                        <div class="grid grid-cols-2 gap-4 w-full mt-10">
                            <div v-for="(label, i) in trafficOptions.labels" :key="label" class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: trafficOptions.colors[i] }"></div>
                                <span class="text-[10px] font-black text-zinc-600 uppercase tracking-widest">{{ label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Top Products & Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Top Products -->
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-10 rounded-[3rem]">
                    <h3 class="text-xl font-black text-white uppercase italic tracking-tighter mb-8">Elite Performance</h3>
                    <div class="space-y-6">
                        <div v-for="product in topProducts" :key="product.id" class="flex items-center justify-between p-4 rounded-3xl bg-white/[0.02] border border-white/5 hover:border-admin-modern/30 transition-all group">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-zinc-800 overflow-hidden border border-white/10 group-hover:border-admin-modern/50 transition-all">
                                    <img :src="product.image" class="w-full h-full object-cover" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-white uppercase italic tracking-tight leading-tight">{{ product.name }}</p>
                                    <p class="text-[10px] font-black text-zinc-600 uppercase tracking-widest mt-1">{{ product.sales }} Silhouettes</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-white tracking-tighter">{{ formatCurrency(product.revenue) }}</p>
                                <p class="text-[9px] font-black uppercase tracking-widest mt-1 text-admin-modern/50">
                                    {{ product.brand }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-10 rounded-[3rem]">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xl font-black text-white uppercase italic tracking-tighter">Market Pulse</h3>
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse shadow-[0_0_10px_rgba(239,68,68,0.5)]"></span>
                            <span class="text-[9px] font-black text-zinc-600 uppercase tracking-widest">Active Stream</span>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div v-for="activity in recentActivity" :key="activity.id" class="flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-zinc-800 flex items-center justify-center text-zinc-600 border border-white/5 group-hover:border-admin-modern/30 transition-all group-hover:text-admin-modern">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getStatusIcon(activity.type)" /></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white leading-tight">
                                        <span class="text-zinc-600 font-black uppercase text-[9px] mr-2">New {{ activity.type }}:</span> {{ activity.user }}
                                    </p>
                                    <p class="text-[9px] font-black text-zinc-700 uppercase tracking-widest mt-1">{{ activity.time }}</p>
                                </div>
                            </div>
                            <p v-if="activity.amount" class="text-sm font-black text-white tracking-tighter italic opacity-80">{{ activity.amount }}</p>
                        </div>
                    </div>
                    
                    <!-- Live Stats Widget -->
                    <div class="mt-12 grid grid-cols-3 gap-6 border-t border-white/5 pt-10">
                        <div>
                            <p class="text-[9px] font-black text-zinc-600 uppercase tracking-[0.2em] mb-2">Live Nodes</p>
                            <p class="text-2xl font-black text-white italic tracking-tighter">{{ liveStats.active_visitors }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-zinc-600 uppercase tracking-[0.2em] mb-2">Daily Txs</p>
                            <p class="text-2xl font-black text-white italic tracking-tighter">{{ liveStats.sales_today }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-zinc-600 uppercase tracking-[0.2em] mb-2">Daily Rev</p>
                            <p class="text-2xl font-black text-admin-modern italic tracking-tighter">{{ formatCurrency(liveStats.revenue_today) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
