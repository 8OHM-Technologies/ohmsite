<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SubscriberLayout from '@/Layouts/SubscriberLayout.vue';
import VueApexCharts from 'vue3-apexcharts';
import axios from 'axios';
import Skeleton from 'primevue/skeleton';
import {
    Scale,
    Gavel,
    Calendar,
    Clock,
    BookOpen,
    Layers,
    TrendingUp,
    Users,
    Search,
    SlidersHorizontal,
    RefreshCw,
    ExternalLink,
    X,
    FileText,
    CheckCircle2,
    AlertCircle,
    Bookmark,
    Award,
    Compass,
    Hash
} from 'lucide-vue-next';

const props = defineProps({
    filters: { type: Array, default: () => [] },
});

const analyticsData = ref(null);
const analyticsLoading = ref(false);

// Filters State
const filterCourt = ref('All');
const filterJudge = ref('All');
const filterYear = ref('All');
const filterReportable = ref('All');
const searchQuery = ref('');

const activeTab = ref('overview');
const activePrecedentTab = ref('all');

const tabs = [
    { id: 'overview', label: 'Jurisprudence Overview', icon: Layers },
    { id: 'precedents', label: 'Precedents & Citations Network', icon: BookOpen },
    { id: 'bench', label: 'Judicial Bench & Panels', icon: Users },
];

const loadAnalytics = async () => {
    analyticsLoading.value = true;
    try {
        const params = {
            type: 'saflii_courts',
            court: filterCourt.value,
            judge: filterJudge.value,
            year: filterYear.value,
            reportable: filterReportable.value,
            search: searchQuery.value,
        };
        const { data } = await axios.get(route('subscriber.analytics.data'), { params });
        analyticsData.value = data;
    } catch (e) {
        console.error('SAFLII Analytics load failed', e);
    } finally {
        analyticsLoading.value = false;
    }
};

const resetFilters = () => {
    filterCourt.value = 'All';
    filterJudge.value = 'All';
    filterYear.value = 'All';
    filterReportable.value = 'All';
    searchQuery.value = '';
    loadAnalytics();
};

let searchDebounce = null;
watch(searchQuery, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        loadAnalytics();
    }, 350);
});

watch([filterCourt, filterJudge, filterYear, filterReportable], () => {
    loadAnalytics();
});

onMounted(() => loadAnalytics());

// Data references
const totals = computed(() => analyticsData.value?.totals ?? {
    total_cases: 0,
    reportable_count: 0,
    reportable_percentage: 0,
    total_precedents: 0,
    avg_precedents_per_case: 0,
    total_judges: 0,
    avg_hearing_to_judgment_days: 0,
});

const courtsBreakdown = computed(() => analyticsData.value?.courts_breakdown ?? []);
const timelineTrend = computed(() => analyticsData.value?.timeline_trend ?? { years: [], counts: [], avg_duration_days: [] });
const precedentsIntel = computed(() => analyticsData.value?.precedents_intelligence ?? { top_cited: [], treatment_distribution: {}, density_distribution: {} });
const benchIntel = computed(() => analyticsData.value?.bench_intelligence ?? { top_judges: [], panel_sizes: {} });
const filterOptions = computed(() => analyticsData.value?.filter_options ?? { courts: [], judges: [], years: [] });

// Visualizations

// 1. Timeline Volume Area Chart
const timelineChartOptions = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, background: 'transparent' },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#8dd7da', '#ff8800'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: [0.35, 0.1],
            opacityTo: [0.01, 0.01],
            stops: [20, 100],
        },
    },
    xaxis: {
        categories: timelineTrend.value.years,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#71717a', fontSize: '10px' } },
    },
    yaxis: [
        {
            title: { text: 'Judgments Published', style: { color: '#8dd7da', fontSize: '10px' } },
            labels: { style: { colors: '#71717a' } },
        },
        {
            opposite: true,
            title: { text: 'Avg Duration (Days)', style: { color: '#ff8800', fontSize: '10px' } },
            labels: { style: { colors: '#71717a' } },
        }
    ],
    grid: { borderColor: 'rgba(255,255,255,0.05)', strokeDashArray: 4 },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' },
    legend: { show: true, position: 'top', horizontalAlign: 'right', labels: { colors: '#a0a0b0' } },
}));

const timelineSeries = computed(() => [
    { name: 'Annual Decisions', type: 'area', data: timelineTrend.value.counts },
    { name: 'Adjudication Speed (Days)', type: 'line', data: timelineTrend.value.avg_duration_days },
]);

// 2. Precedent Treatments Donut
const treatmentsChartOptions = computed(() => {
    const dist = precedentsIntel.value.treatment_distribution || {};
    return {
        chart: { type: 'donut', background: 'transparent' },
        colors: ['#8dd7da', '#ff8800', '#f43f5e', '#a855f7'],
        labels: Object.keys(dist),
        stroke: { show: false },
        legend: { show: true, position: 'bottom', labels: { colors: '#a0a0b0' } },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Citations',
                            color: '#fff',
                            fontSize: '11px',
                            formatter: () => totals.value.total_precedents.toLocaleString(),
                        },
                    },
                },
            },
        },
        dataLabels: { enabled: false },
        tooltip: { theme: 'dark' },
    };
});
const treatmentsSeries = computed(() => Object.values(precedentsIntel.value.treatment_distribution || {}));

// 3. Citation Density Bar Chart
const densityChartOptions = computed(() => {
    const density = precedentsIntel.value.density_distribution || {};
    return {
        chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 5, columnWidth: '50%' } },
        colors: ['#ff8800'],
        dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: 'bold', colors: ['#000'] } },
        xaxis: {
            categories: Object.keys(density).map(k => k + ' Precedents'),
            labels: { style: { colors: '#71717a', fontSize: '10px' } },
        },
        yaxis: { labels: { style: { colors: '#71717a' } } },
        grid: { borderColor: 'rgba(255,255,255,0.05)' },
        tooltip: { theme: 'dark' },
    };
});
const densitySeries = computed(() => [{ name: 'Cases', data: Object.values(precedentsIntel.value.density_distribution || {}) }]);

// 4. Panel Sizes Donut
const panelSizeChartOptions = computed(() => {
    const sizes = benchIntel.value.panel_sizes || {};
    return {
        chart: { type: 'donut', background: 'transparent' },
        colors: ['#8dd7da', '#ff8800', '#a855f7'],
        labels: Object.keys(sizes),
        stroke: { show: false },
        legend: { show: true, position: 'bottom', labels: { colors: '#a0a0b0' } },
        dataLabels: { enabled: false },
        tooltip: { theme: 'dark' },
    };
});
const panelSizeSeries = computed(() => Object.values(benchIntel.value.panel_sizes || {}));

const filterByJudgeQuick = (judgeName) => {
    filterJudge.value = judgeName;
    activeTab.value = 'overview';
};
</script>

<template>
    <Head title="8OHM | SAFLII Superior Courts Jurisprudence Analytics">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <SubscriberLayout>
        <div class="space-y-8 animate-in fade-in duration-700">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/5 pb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <Gavel class="w-6 h-6 text-admin-modern" />
                        <h1 class="text-xl sm:text-2xl font-black uppercase tracking-wider text-white">
                            SAFLII Courts Jurisprudence Intelligence
                        </h1>
                    </div>
                    <p class="text-xs text-zinc-400 mt-1 font-medium">
                        Deep precedent citation networks, judicial bench analysis, and case intelligence across South African court decisions.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="loadAnalytics" :disabled="analyticsLoading"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-white/5 hover:bg-white/10 border border-white/10 text-white flex items-center gap-2 transition-all">
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': analyticsLoading }" />
                        <span>Refresh Intelligence</span>
                    </button>
                    <button @click="resetFilters"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-admin-modern/10 hover:bg-admin-modern/20 text-admin-modern border border-admin-modern/20 transition-all">
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Global Search & Filter Bar -->
            <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-4 rounded-2xl space-y-3">
                <div class="flex flex-col md:flex-row gap-3">
                    <!-- Search Field -->
                    <div class="relative flex-1">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                        <input v-model="searchQuery" type="text"
                            placeholder="Search legal principles, ratio decidendi, case number, parties, judges..."
                            class="w-full bg-zinc-950 border border-white/10 rounded-xl pl-10 pr-4 py-2 text-xs text-white placeholder-zinc-500 focus:border-admin-modern focus:outline-none" />
                    </div>

                    <!-- Multi Filters -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 shrink-0">
                        <!-- Court -->
                        <div>
                            <select v-model="filterCourt"
                                class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-admin-modern focus:outline-none">
                                <option value="All">All Superior Courts</option>
                                <option v-for="c in filterOptions.courts" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <!-- Judge -->
                        <div>
                            <select v-model="filterJudge"
                                class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-admin-modern focus:outline-none">
                                <option value="All">All Judges / Benches</option>
                                <option v-for="j in filterOptions.judges" :key="j" :value="j">{{ j }}</option>
                            </select>
                        </div>
                        <!-- Year -->
                        <div>
                            <select v-model="filterYear"
                                class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-admin-modern focus:outline-none">
                                <option value="All">All Decision Years</option>
                                <option v-for="y in filterOptions.years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                        <!-- Reportable -->
                        <div>
                            <select v-model="filterReportable"
                                class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-admin-modern focus:outline-none">
                                <option value="All">All Status</option>
                                <option value="Yes">Reportable Precedents</option>
                                <option value="No">Non-Reportable</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="flex items-center gap-2 border-b border-white/5 pb-2 overflow-x-auto custom-scrollbar">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-all shrink-0 cursor-pointer"
                    :class="activeTab === tab.id ? 'btn btn-primary font-bold shadow-lg shadow-primary/20' : 'text-zinc-400 hover:text-white hover:bg-white/5'">
                    <component :is="tab.icon" class="w-4 h-4" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- TAB 1: JURISPRUDENCE OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
                <!-- Skeleton State for Tab 1 -->
                <template v-if="analyticsLoading || !analyticsData">
                    <!-- KPI Grid Skeleton -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="i in 4" :key="i" class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <Skeleton width="45%" height="0.8rem" class="bg-zinc-800" />
                                <Skeleton width="1.25rem" height="1.25rem" shape="circle" class="bg-zinc-800" />
                            </div>
                            <Skeleton width="60%" height="2.25rem" class="bg-zinc-800" />
                            <Skeleton width="75%" height="0.75rem" class="bg-zinc-800" />
                        </div>
                    </div>

                    <!-- Courts Distribution Row Skeleton -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div v-for="i in 3" :key="i" class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl flex items-center justify-between">
                            <div class="space-y-2 w-2/3">
                                <Skeleton width="40%" height="0.75rem" class="bg-zinc-800" />
                                <Skeleton width="70%" height="1.25rem" class="bg-zinc-800" />
                                <Skeleton width="50%" height="0.75rem" class="bg-zinc-800" />
                            </div>
                            <div class="space-y-1.5 w-1/4 flex flex-col items-end">
                                <Skeleton width="70%" height="1.75rem" class="bg-zinc-800" />
                                <Skeleton width="90%" height="0.6rem" class="bg-zinc-800" />
                            </div>
                        </div>
                    </div>

                    <!-- 30-Year Jurisprudence Timeline Skeleton -->
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                        <div class="space-y-2">
                            <Skeleton width="40%" height="1.25rem" class="bg-zinc-800" />
                            <Skeleton width="60%" height="0.85rem" class="bg-zinc-800" />
                        </div>
                        <Skeleton width="100%" height="320px" class="bg-zinc-800/40 rounded-xl" />
                    </div>
                </template>

                <!-- Loaded State for Tab 1 -->
                <template v-else>
                    <!-- KPI Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl relative overflow-hidden group">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Total Judgments</span>
                                <Scale class="w-4 h-4 text-admin-modern" />
                            </div>
                            <div class="text-3xl font-black text-white mt-2">{{ totals.total_cases }}</div>
                            <p class="text-[10px] text-zinc-500 mt-1">ZACC & ZACAC Appellate Decisions</p>
                        </div>

                        <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl relative overflow-hidden group">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Reportable Rate</span>
                                <Award class="w-4 h-4 text-emerald-400" />
                            </div>
                            <div class="text-3xl font-black text-emerald-400 mt-2">{{ totals.reportable_percentage }}%</div>
                            <p class="text-[10px] text-zinc-500 mt-1">{{ totals.reportable_count }} precedent-setting judgments</p>
                        </div>

                        <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl relative overflow-hidden group">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Precedents Network</span>
                                <BookOpen class="w-4 h-4 text-purple-400" />
                            </div>
                            <div class="text-3xl font-black text-purple-400 mt-2">{{ totals.total_precedents.toLocaleString() }}</div>
                            <p class="text-[10px] text-zinc-500 mt-1">Avg {{ totals.avg_precedents_per_case }} citations per decision</p>
                        </div>

                        <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl relative overflow-hidden group">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Adjudication Velocity</span>
                                <Clock class="w-4 h-4 text-rose-400" />
                            </div>
                            <div class="text-3xl font-black text-rose-400 mt-2">{{ totals.avg_hearing_to_judgment_days }} <span class="text-xs font-normal text-zinc-400">days</span></div>
                            <p class="text-[10px] text-zinc-500 mt-1">Hearing to judgment delivery avg</p>
                        </div>
                    </div>

                    <!-- Courts Distribution Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div v-for="c in courtsBreakdown" :key="c.court" class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-admin-modern">Court Authority</span>
                                <h4 class="text-sm font-bold text-white">{{ c.court }}</h4>
                                <p class="text-xs text-zinc-400">{{ c.count }} Judgments published</p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-black text-white">{{ c.percentage }}%</div>
                                <span class="text-[10px] text-zinc-500 font-bold uppercase">Jurisdiction Share</span>
                            </div>
                        </div>
                    </div>

                    <!-- 30-Year Jurisprudence Timeline Area Chart -->
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Jurisprudential Timeline & Adjudication Speed (1995–2026)</h3>
                                <p class="text-xs text-zinc-400 mt-0.5">Annual volume of published judgments alongside average days from hearing to decision.</p>
                            </div>
                        </div>
                        <VueApexCharts type="area" height="320" :options="timelineChartOptions" :series="timelineSeries" />
                    </div>
                </template>
            </div>

            <!-- TAB 2: PRECEDENTS & CITATIONS NETWORK -->
            <div v-if="activeTab === 'precedents'" class="space-y-6">
                <!-- Skeleton State for Tab 2 -->
                <template v-if="analyticsLoading || !analyticsData">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                            <div class="space-y-2">
                                <Skeleton width="50%" height="1.25rem" class="bg-zinc-800" />
                                <Skeleton width="80%" height="0.8rem" class="bg-zinc-800" />
                            </div>
                            <Skeleton width="100%" height="280px" class="bg-zinc-800/40 rounded-xl" />
                        </div>
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl lg:col-span-2 space-y-4">
                            <div class="space-y-2">
                                <Skeleton width="40%" height="1.25rem" class="bg-zinc-800" />
                                <Skeleton width="70%" height="0.8rem" class="bg-zinc-800" />
                            </div>
                            <Skeleton width="100%" height="280px" class="bg-zinc-800/40 rounded-xl" />
                        </div>
                    </div>

                    <!-- Top Cited Authorities Skeleton Grid -->
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between">
                            <Skeleton width="35%" height="1.25rem" class="bg-zinc-800" />
                            <Skeleton width="15%" height="0.85rem" class="bg-zinc-800" />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="i in 6" :key="i" class="bg-zinc-950/60 border border-white/5 p-4 rounded-xl space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2 w-3/4">
                                        <Skeleton width="1.25rem" height="1.25rem" shape="circle" class="bg-zinc-800" />
                                        <Skeleton width="80%" height="1rem" class="bg-zinc-800" />
                                    </div>
                                    <Skeleton width="20%" height="1.2rem" class="bg-zinc-800" />
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-white/5">
                                    <Skeleton width="50%" height="0.75rem" class="bg-zinc-800" />
                                    <Skeleton width="25%" height="0.75rem" class="bg-zinc-800" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Loaded State for Tab 2 -->
                <template v-else>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Precedent Treatment Breakdown -->
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Treatment of Citations</h3>
                                <p class="text-xs text-zinc-400 mt-0.5">How cited precedents and statutes were applied, referred, or distinguished.</p>
                            </div>
                            <VueApexCharts type="donut" height="280" :options="treatmentsChartOptions" :series="treatmentsSeries" />
                        </div>

                        <!-- Right: Citation Density Distribution -->
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl lg:col-span-2 space-y-4">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Citation Intensity Distribution</h3>
                                <p class="text-xs text-zinc-400 mt-0.5">Number of decisions by volume of precedents and statutory sections cited.</p>
                            </div>
                            <VueApexCharts type="bar" height="280" :options="densityChartOptions" :series="densitySeries" />
                        </div>
                    </div>

                    <!-- Top Cited Authorities Leaderboard -->
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-white">Most Cited Landmark Authorities & Acts</h3>
                            <span class="text-xs text-zinc-500 font-bold uppercase">Top 15 References</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="(p, idx) in precedentsIntel.top_cited" :key="p.citation"
                                class="bg-zinc-950/60 border border-white/5 p-4 rounded-xl space-y-2 hover:border-admin-modern/30 transition-all group">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-white/5 flex items-center justify-center text-[10px] font-mono font-bold text-zinc-400 group-hover:text-admin-modern">
                                            {{ idx + 1 }}
                                        </span>
                                        <span class="font-bold text-xs text-white group-hover:text-admin-modern transition-colors line-clamp-1">
                                            {{ p.citation }}
                                        </span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-admin-modern/10 text-admin-modern border border-admin-modern/20 shrink-0">
                                        {{ p.count }} citations
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-zinc-500 pt-1 border-t border-white/5">
                                    <span>Primary Treatment: <strong class="text-zinc-300">{{ p.treatment }}</strong></span>
                                    <a v-if="p.url" :href="p.url" target="_blank" rel="noopener noreferrer"
                                        class="text-admin-modern hover:underline flex items-center gap-1">
                                        LawCite <ExternalLink class="w-3 h-3" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- TAB 3: JUDICIAL BENCH & PANELS -->
            <div v-if="activeTab === 'bench'" class="space-y-6">
                <!-- Skeleton State for Tab 3 -->
                <template v-if="analyticsLoading || !analyticsData">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                            <div class="space-y-2">
                                <Skeleton width="45%" height="1.25rem" class="bg-zinc-800" />
                                <Skeleton width="75%" height="0.8rem" class="bg-zinc-800" />
                            </div>
                            <Skeleton width="100%" height="280px" class="bg-zinc-800/40 rounded-xl" />
                        </div>

                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl lg:col-span-2 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="space-y-2 w-1/2">
                                    <Skeleton width="60%" height="1.25rem" class="bg-zinc-800" />
                                    <Skeleton width="80%" height="0.8rem" class="bg-zinc-800" />
                                </div>
                                <Skeleton width="20%" height="0.85rem" class="bg-zinc-800" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-for="i in 6" :key="i" class="bg-zinc-950/60 border border-white/5 p-4 rounded-xl flex items-center justify-between">
                                    <div class="space-y-2 w-2/3">
                                        <Skeleton width="70%" height="1rem" class="bg-zinc-800" />
                                        <Skeleton width="90%" height="0.75rem" class="bg-zinc-800" />
                                    </div>
                                    <Skeleton width="25%" height="1.75rem" class="bg-zinc-800 rounded-lg" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Loaded State for Tab 3 -->
                <template v-else>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Panel Sizes -->
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Bench Composition</h3>
                                <p class="text-xs text-zinc-400 mt-0.5">Distribution of single-judge vs appellate bench panels.</p>
                            </div>
                            <VueApexCharts type="donut" height="280" :options="panelSizeChartOptions" :series="panelSizeSeries" />
                        </div>

                        <!-- Right: Most Active Judges Leaderboard -->
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl lg:col-span-2 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-bold uppercase tracking-wider text-white">Active Presiding Judges & Justices</h3>
                                    <p class="text-xs text-zinc-400 mt-0.5">Judges ranked by judgment authoring and panel appearances.</p>
                                </div>
                                <span class="text-xs text-zinc-500 font-bold uppercase">{{ benchIntel.top_judges.length }} Judges</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-for="j in benchIntel.top_judges" :key="j.name"
                                    class="bg-zinc-950/60 border border-white/5 p-4 rounded-xl flex items-center justify-between hover:border-admin-modern/30 transition-all">
                                    <div>
                                        <h5 class="text-xs font-bold text-white">{{ j.name }}</h5>
                                        <p class="text-[10px] text-zinc-400 mt-0.5">Avg {{ j.avg_precedents }} citations cited in decisions</p>
                                    </div>
                                    <button @click="filterByJudgeQuick(j.name)"
                                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-admin-modern/10 text-admin-modern border border-admin-modern/20 hover:bg-admin-modern hover:text-black transition-all">
                                        {{ j.cases_count }} Cases
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </SubscriberLayout>
</template>
