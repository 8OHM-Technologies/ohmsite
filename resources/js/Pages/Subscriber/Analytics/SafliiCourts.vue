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
    Hash,
    ChevronDown,
    Code,
    Sparkles,
    Eye
} from 'lucide-vue-next';

const props = defineProps({
    filters: { type: Array, default: () => [] },
});

const COURT_NAMES_MAP = {
    'ZACC': 'Constitutional Court of South Africa',
    'ZASCA': 'Supreme Court of Appeal of South Africa',
    'ZAGPPHC': 'Gauteng High Court, Pretoria',
    'ZAGPJHC': 'Gauteng High Court, Johannesburg',
    'ZAWCHC': 'Western Cape High Court, Cape Town',
    'ZAFSHC': 'Free State High Court, Bloemfontein',
    'ZAKZNDHC': 'KwaZulu-Natal High Court, Durban',
    'ZAKZNHC': 'KwaZulu-Natal High Court, Pietermaritzburg',
    'ZAECGHC': 'Eastern Cape High Court, Grahamstown',
    'ZAECPEHC': 'Eastern Cape High Court, Port Elizabeth',
    'ZAECELHC': 'Eastern Cape High Court, East London',
    'ZAECBHC': 'Eastern Cape High Court, Bhisho',
    'ZALMPPHC': 'Limpopo High Court, Polokwane',
    'ZANWHC': 'North West High Court, Mahikeng',
    'ZANCHC': 'Northern Cape High Court, Kimberley',
    'ZALC': 'Labour Court of South Africa',
    'ZALAC': 'Labour Appeal Court of South Africa',
    'ZACAC': 'Competition Appeal Court of South Africa',
    'ZAEQC': 'Equality Court of South Africa',
    'ZALCC': 'Land Claims Court of South Africa',
    'ZATC': 'Tax Court of South Africa',
    'ZAECC': 'Electoral Court of South Africa',
    'ZALCJHB': 'Labour Court, Johannesburg',
    'ZALCPE': 'Labour Court, Port Elizabeth',
    'ZALCCT': 'Labour Court, Cape Town',
    'ZALCD': 'Labour Court, Durban',
    'ZALMPTHC': 'Limpopo High Court, Thohoyandou',
    'ZAMPMHC': 'Mpumalanga High Court, Middelburg',
    'ZAMPMBHC': 'Mpumalanga High Court, Mbombela',
    'ZAKZDHC': 'KwaZulu-Natal High Court, Durban',
    'ZAKZPHC': 'KwaZulu-Natal High Court, Pietermaritzburg',
    'ZAGPHC': 'Gauteng High Court',
    'ZAKZHC': 'KwaZulu-Natal High Court',
    'ZAECHC': 'Eastern Cape High Court',
};

const formatCourtName = (court) => {
    if (!court) return '';
    const cStr = String(court).trim();
    const cUpper = cStr.toUpperCase();
    if (COURT_NAMES_MAP[cUpper]) {
        return COURT_NAMES_MAP[cUpper];
    }
    return cStr;
};

// Standardised courts list for the dropdown
const standardizedCourts = computed(() => {
    if (props.filters && props.filters.length > 0) {
        const courtFilters = props.filters.filter(f =>
            (f.target_type === 'cases' || !f.target_type) &&
            f.target_name !== 'sabinet_ccma' &&
            f.vanity_name !== 'CCMA Awards'
        );
        if (courtFilters.length > 0) {
            return courtFilters;
        }
    }

    return Object.entries(COURT_NAMES_MAP)
        .map(([target_name, vanity_name]) => ({
            target_name,
            vanity_name,
            target_type: 'cases'
        }))
        .sort((a, b) => a.vanity_name.localeCompare(b.vanity_name));
});

const analyticsData = ref(null);
const analyticsLoading = ref(false);

// Filters State
const filterCourt = ref('All');
const filterJudge = ref('All');
const filterYear = ref('All');
const filterReportable = ref('All');
const searchQuery = ref('');

// Tab State & Modals
const activeTab = ref('overview'); // overview, precedents, bench, cases
const isTabDropdownOpen = ref(false);
const isMetricsModalOpen = ref(false);
const selectedCaseDetail = ref(null);
const displayCasesLimit = ref(20);

const tabs = [
    { id: 'overview', label: 'Jurisprudence Overview', icon: Layers },
    { id: 'precedents', label: 'Precedents & Citations Network', icon: BookOpen },
    { id: 'bench', label: 'Judicial Bench & Panels', icon: Users },
    { id: 'cases', label: 'Ratio Decidendi Explorer', icon: Gavel },
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
            limit: 100,
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
    displayCasesLimit.value = 20;
    loadAnalytics();
};

let searchDebounce = null;
watch(searchQuery, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        displayCasesLimit.value = 20;
        loadAnalytics();
    }, 350);
});

watch([filterCourt, filterJudge, filterYear, filterReportable], () => {
    displayCasesLimit.value = 20;
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

const allCases = computed(() => analyticsData.value?.cases ?? []);
const totalFilteredCases = computed(() => analyticsData.value?.total_filtered_cases ?? allCases.value.length);
const visibleCases = computed(() => allCases.value.slice(0, displayCasesLimit.value));

const recentCasesStream = computed(() => allCases.value.slice(0, 6));

const loadMoreCases = () => {
    displayCasesLimit.value += 20;
};

// Visualizations

// 1. Timeline & Adjudication Speed Area Chart (Dual-Axis)
const timelineChartOptions = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, background: 'transparent' },
    stroke: { curve: 'smooth', width: [3, 2], dashArray: [0, 4] },
    colors: ['#ff8800', '#8dd7da'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: [0.35, 0.08],
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
            title: { text: 'Judgments Published', style: { color: '#ff8800', fontSize: '10px' } },
            labels: { style: { colors: '#71717a' } },
        },
        {
            opposite: true,
            title: { text: 'Adjudication Speed (Days)', style: { color: '#8dd7da', fontSize: '10px' } },
            labels: { style: { colors: '#71717a' } },
        }
    ],
    grid: { borderColor: 'rgba(255,255,255,0.05)', strokeDashArray: 4 },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' },
    legend: { show: true, position: 'top', horizontalAlign: 'right', labels: { colors: '#a0a0b0' } },
}));

const timelineSeries = computed(() => [
    { name: 'Judgments Published', type: 'area', data: timelineTrend.value.counts },
    { name: 'Adjudication Speed (Days)', type: 'line', data: timelineTrend.value.avg_duration_days },
]);

// 2. Legal Subject Typology Donut Chart
const typologyData = computed(() => {
    if (analyticsData.value?.typology_distribution?.labels?.length > 0) {
        return analyticsData.value.typology_distribution;
    }
    const counts = {};
    allCases.value.forEach(c => {
        (c.keywords || []).forEach(k => {
            counts[k] = (counts[k] || 0) + 1;
        });
    });
    const topEntries = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 6);
    if (topEntries.length === 0) {
        return {
            labels: ['Constitutional Law', 'Appellate Jurisprudence', 'Competition Law', 'Bill of Rights'],
            series: [1, 1, 1, 1]
        };
    }
    return {
        labels: topEntries.map(e => e[0]),
        series: topEntries.map(e => e[1])
    };
});

const typologyChartOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent' },
    colors: ['#ff8800', '#8dd7da', '#a855f7', '#f43f5e', '#38bdf8', '#fbbf24'],
    labels: typologyData.value.labels,
    stroke: { show: false },
    legend: { show: false },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    name: { show: true, color: '#fff', fontSize: '11px' },
                    value: { show: true, color: '#a0a0b0', fontSize: '18px', fontWeight: 'bold' },
                    total: {
                        show: true,
                        label: 'Top Subjects',
                        color: '#fff',
                        fontSize: '10px',
                        formatter: () => (typologyData.value.series || []).reduce((a, b) => a + b, 0)
                    }
                }
            }
        }
    },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' }
}));

// 3. Precedent Treatments Donut
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

// 4. Citation Density Bar Chart
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

// 5. Panel Sizes Donut
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
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-white/5 hover:bg-white/10 border border-white/10 text-white flex items-center gap-2 transition-all cursor-pointer">
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': analyticsLoading }" />
                        <span>Refresh Intelligence</span>
                    </button>
                    <button @click="resetFilters"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-admin-modern/10 hover:bg-admin-modern/20 text-admin-modern border border-admin-modern/20 transition-all cursor-pointer">
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
                                <option v-for="c in standardizedCourts" :key="c.target_name" :value="c.target_name">{{ c.vanity_name }}</option>
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
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-white/5 pb-3">
                <!-- Mobile Tab Dropdown -->
                <div class="relative lg:hidden w-full">
                    <div v-if="isTabDropdownOpen" class="fixed inset-0 z-40" @click="isTabDropdownOpen = false"></div>

                    <button @click="isTabDropdownOpen = !isTabDropdownOpen"
                        class="flex items-center justify-between w-full bg-white text-black font-black px-5 py-2.5 rounded-xl text-[10px] uppercase tracking-widest transition-all relative z-50">
                        <span class="flex items-center gap-2">
                            <component :is="tabs.find(t => t.id === activeTab)?.icon" class="w-3.5 h-3.5" />
                            {{ tabs.find(t => t.id === activeTab)?.label }}
                        </span>
                        <ChevronDown class="w-4 h-4 transition-transform duration-200"
                            :class="{ 'rotate-180': isTabDropdownOpen }" />
                    </button>

                    <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                        <div v-if="isTabDropdownOpen"
                            class="absolute left-0 right-0 mt-2 bg-zinc-955 border border-white/10 p-2 rounded-2xl shadow-2xl z-50 space-y-1 backdrop-blur-xl">
                            <button v-for="tab in tabs" :key="tab.id"
                                @click="activeTab = tab.id; isTabDropdownOpen = false"
                                :class="[activeTab === tab.id ? 'bg-white text-black font-black' : 'text-zinc-400 hover:text-white hover:bg-white/5']"
                                class="flex items-center gap-2 w-full px-5 py-3 rounded-xl text-[10px] uppercase tracking-widest transition-all">
                                <component :is="tab.icon" class="w-3.5 h-3.5" />
                                {{ tab.label }}
                            </button>
                        </div>
                    </transition>
                </div>

                <!-- Desktop Tabs Navigation -->
                <div class="hidden lg:flex items-center gap-1 bg-zinc-955 border border-white/5 p-1 rounded-2xl">
                    <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                        :class="[activeTab === tab.id ? 'bg-white text-black font-black shadow-lg' : 'text-zinc-400 hover:text-white hover:bg-white/5']"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all shrink-0 cursor-pointer">
                        <component :is="tab.icon" class="w-4 h-4" />
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Metrics Logic Info Button -->
                <button @click="isMetricsModalOpen = true"
                    class="flex items-center gap-2 bg-zinc-900 border border-white/10 hover:bg-zinc-800 text-zinc-300 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all shrink-0 cursor-pointer">
                    <Code class="w-4 h-4 text-admin-modern" />
                    <span>Metrics Logic</span>
                </button>
            </div>

            <!-- TAB 1: JURISPRUDENCE OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="space-y-8">
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

                    <!-- Charts Grid Skeleton -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                            <Skeleton width="40%" height="1.25rem" class="bg-zinc-800" />
                            <Skeleton width="100%" height="320px" class="bg-zinc-800/40 rounded-xl" />
                        </div>
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                            <Skeleton width="40%" height="1.25rem" class="bg-zinc-800" />
                            <Skeleton width="100%" height="260px" class="bg-zinc-800/40 rounded-xl" />
                        </div>
                    </div>

                    <!-- Courts Breakdown Skeleton -->
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
                </template>

                <!-- Loaded State for Tab 1 -->
                <template v-else>
                    <!-- KPI Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl relative overflow-hidden group hover:border-admin-modern/30 transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Total Judgments</span>
                                <Scale class="w-4 h-4 text-admin-modern" />
                            </div>
                            <div class="text-3xl font-black text-white mt-2">{{ totals.total_cases }}</div>
                            <p class="text-[10px] text-zinc-500 mt-1">Superior Courts Jurisprudence</p>
                        </div>

                        <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl relative overflow-hidden group hover:border-emerald-400/30 transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Reportable Rate</span>
                                <Award class="w-4 h-4 text-emerald-400" />
                            </div>
                            <div class="text-3xl font-black text-emerald-400 mt-2">{{ totals.reportable_percentage }}%</div>
                            <p class="text-[10px] text-zinc-500 mt-1">{{ totals.reportable_count }} precedent-setting judgments</p>
                        </div>

                        <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl relative overflow-hidden group hover:border-purple-400/30 transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Precedents Network</span>
                                <BookOpen class="w-4 h-4 text-purple-400" />
                            </div>
                            <div class="text-3xl font-black text-purple-400 mt-2">{{ totals.total_precedents.toLocaleString() }}</div>
                            <p class="text-[10px] text-zinc-500 mt-1">Avg {{ totals.avg_precedents_per_case }} citations per decision</p>
                        </div>

                        <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl relative overflow-hidden group hover:border-rose-400/30 transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Adjudication Velocity</span>
                                <Clock class="w-4 h-4 text-rose-400" />
                            </div>
                            <div class="text-3xl font-black text-rose-400 mt-2">{{ totals.avg_hearing_to_judgment_days }} <span class="text-xs font-normal text-zinc-400">days</span></div>
                            <p class="text-[10px] text-zinc-500 mt-1">Hearing to judgment delivery avg</p>
                        </div>
                    </div>

                    <!-- Dual Charts: Timeline & Typology -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: 30-Year Jurisprudence Timeline Area Chart -->
                        <div class="lg:col-span-2 bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-bold uppercase tracking-wider text-white">Jurisprudential Timeline & Adjudication Speed</h3>
                                    <p class="text-xs text-zinc-400 mt-0.5">Annual volume of published judgments alongside average days from hearing to decision.</p>
                                </div>
                                <span class="text-[10px] font-black text-admin-modern bg-admin-modern/10 border border-admin-modern/20 px-2.5 py-1 rounded-lg">
                                    {{ courtsBreakdown.length }} Courts
                                </span>
                            </div>
                            <div class="flex-1 min-h-[320px]">
                                <VueApexCharts type="area" height="320" :options="timelineChartOptions" :series="timelineSeries" />
                            </div>
                        </div>

                        <!-- Right: Legal Subject Typology Donut Chart -->
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4 flex flex-col justify-between">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Legal Subject Typology</h3>
                                <p class="text-xs text-zinc-400 mt-0.5">Distribution of primary legal principles & subject matters.</p>
                            </div>

                            <div class="flex-1 flex flex-col items-center justify-center my-2">
                                <VueApexCharts width="230" :options="typologyChartOptions" :series="typologyData.series" />
                            </div>

                            <div class="grid grid-cols-2 gap-2 border-t border-white/5 pt-3 max-h-[140px] overflow-y-auto custom-scrollbar">
                                <div v-for="(label, i) in typologyData.labels" :key="label" class="flex items-center justify-between text-[10px]">
                                    <div class="flex items-center gap-1.5 truncate mr-2">
                                        <div class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: typologyChartOptions.colors[i % typologyChartOptions.colors.length] }"></div>
                                        <span class="font-bold text-zinc-400 truncate">{{ label }}</span>
                                    </div>
                                    <span class="font-black text-white shrink-0">{{ typologyData.series[i] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Courts Authority Breakdown Row -->
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Court Authority & Jurisdiction Distribution</h3>
                                <p class="text-xs text-zinc-400 mt-0.5">Judgments classified by superior appellate authority.</p>
                            </div>
                            <span class="text-xs text-zinc-500 font-bold uppercase">{{ courtsBreakdown.length }} Jurisdictions</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="c in courtsBreakdown" :key="c.court" class="bg-zinc-950/60 border border-white/5 p-4 rounded-xl flex items-center justify-between hover:border-admin-modern/30 transition-all">
                                <div class="space-y-1 min-w-0 pr-3">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-admin-modern">Court Authority</span>
                                    <h4 class="text-xs font-bold text-white truncate">{{ formatCourtName(c.court) }}</h4>
                                    <p class="text-[10px] text-zinc-400">{{ c.count }} Judgments published</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xl font-black text-white">{{ c.percentage }}%</div>
                                    <span class="text-[9px] text-zinc-500 font-bold uppercase">Share</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Jurisprudence Stream -->
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Recent Jurisprudence Stream</h3>
                                <p class="text-xs text-zinc-400 mt-0.5">Latest superior court decisions with extracted legal principles & ratio decidendi.</p>
                            </div>
                            <button @click="activeTab = 'cases'" class="text-xs font-black text-admin-modern hover:underline uppercase tracking-wider cursor-pointer">
                                View Full Repository &rarr;
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div v-for="c in recentCasesStream" :key="c.id"
                                class="p-4 rounded-xl bg-white/[0.02] border border-white/5 hover:border-admin-modern/30 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4 group">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-zinc-800/80 border border-white/5 flex items-center justify-center text-admin-modern shrink-0 mt-0.5 group-hover:scale-105 transition-transform">
                                        <Gavel class="w-4 h-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-bold text-white truncate max-w-[600px]">{{ c.title }}</span>
                                            <span v-if="c.reportable" class="px-2 py-0.5 rounded text-[8px] font-black bg-emerald-400/10 text-emerald-400 border border-emerald-400/20">
                                                REPORTABLE
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-zinc-400 mt-0.5 font-medium line-clamp-1">
                                            <strong class="text-zinc-300">{{ formatCourtName(c.court) }}</strong> &bull; Case: {{ c.case_number }} &bull; {{ c.applicant }} v {{ c.respondent }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 shrink-0">
                                    <div class="text-right">
                                        <p class="text-xs font-bold text-white">{{ c.judgment_date || c.document_date }}</p>
                                        <p class="text-[9px] text-zinc-500 uppercase tracking-widest font-bold">Decision Date</p>
                                    </div>
                                    <button @click="selectedCaseDetail = c"
                                        class="px-3 py-1.5 bg-admin-modern/10 hover:bg-admin-modern hover:text-black text-admin-modern border border-admin-modern/20 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1 cursor-pointer">
                                        <Eye class="w-3 h-3" />
                                        <span>Inspect</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- TAB 2: PRECEDENTS & CITATIONS NETWORK -->
            <div v-if="activeTab === 'precedents'" class="space-y-6">
                <!-- Skeleton State for Tab 2 -->
                <template v-if="analyticsLoading || !analyticsData">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                            <Skeleton width="50%" height="1.25rem" class="bg-zinc-800" />
                            <Skeleton width="100%" height="280px" class="bg-zinc-800/40 rounded-xl" />
                        </div>
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl lg:col-span-2 space-y-4">
                            <Skeleton width="40%" height="1.25rem" class="bg-zinc-800" />
                            <Skeleton width="100%" height="280px" class="bg-zinc-800/40 rounded-xl" />
                        </div>
                    </div>

                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                        <Skeleton width="35%" height="1.25rem" class="bg-zinc-800" />
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="i in 6" :key="i" class="bg-zinc-950/60 border border-white/5 p-4 rounded-xl space-y-3">
                                <Skeleton width="80%" height="1rem" class="bg-zinc-800" />
                                <Skeleton width="50%" height="0.75rem" class="bg-zinc-800" />
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
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-5 h-5 rounded-full bg-white/5 flex items-center justify-center text-[10px] font-mono font-bold text-zinc-400 group-hover:text-admin-modern shrink-0">
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
                            <Skeleton width="45%" height="1.25rem" class="bg-zinc-800" />
                            <Skeleton width="100%" height="280px" class="bg-zinc-800/40 rounded-xl" />
                        </div>
                        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl lg:col-span-2 space-y-4">
                            <Skeleton width="40%" height="1.25rem" class="bg-zinc-800" />
                            <Skeleton width="100%" height="280px" class="bg-zinc-800/40 rounded-xl" />
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
                                    <div class="min-w-0 pr-2">
                                        <h5 class="text-xs font-bold text-white truncate">{{ j.name }}</h5>
                                        <p class="text-[10px] text-zinc-400 mt-0.5">Avg {{ j.avg_precedents }} citations cited in decisions</p>
                                    </div>
                                    <button @click="filterByJudgeQuick(j.name)"
                                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-admin-modern/10 text-admin-modern border border-admin-modern/20 hover:bg-admin-modern hover:text-black transition-all shrink-0 cursor-pointer">
                                        {{ j.cases_count }} Cases
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- TAB 4: RATIO DECIDENDI EXPLORER -->
            <div v-if="activeTab === 'cases'" class="space-y-6">
                <!-- Skeleton State for Tab 4 -->
                <div v-if="analyticsLoading || !analyticsData" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <Skeleton width="30%" height="1.5rem" class="bg-zinc-800" />
                        <Skeleton width="15%" height="1rem" class="bg-zinc-800" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="i in 4" :key="i" class="p-6 rounded-2xl bg-zinc-900/40 border border-white/5 space-y-4">
                            <div class="flex items-center justify-between">
                                <Skeleton width="25%" height="1.2rem" class="bg-zinc-800" />
                                <Skeleton width="20%" height="1rem" class="bg-zinc-800" />
                            </div>
                            <Skeleton width="85%" height="1.2rem" class="bg-zinc-800" />
                            <Skeleton width="60%" height="0.8rem" class="bg-zinc-800" />
                            <Skeleton width="100%" height="4.5rem" class="bg-zinc-800/40 rounded-xl" />
                            <div class="flex items-center justify-between pt-2 border-t border-white/5">
                                <Skeleton width="25%" height="0.9rem" class="bg-zinc-800" />
                                <Skeleton width="30%" height="1.8rem" class="bg-zinc-800 rounded-lg" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loaded State for Tab 4 -->
                <div v-else class="space-y-6">
                    <!-- Empty State Warning -->
                    <div v-if="allCases.length === 0"
                        class="bg-zinc-900/30 backdrop-blur-md border border-white/5 p-16 rounded-2xl text-center flex flex-col items-center justify-center space-y-4">
                        <AlertCircle class="w-12 h-12 text-rose-400" />
                        <div>
                            <h3 class="text-base font-black text-white uppercase tracking-tight">No Judgments Match Active Filters</h3>
                            <p class="text-zinc-500 text-xs mt-1">Try clearing your search query or broadening the selected court/judge filters.</p>
                        </div>
                        <button @click="resetFilters" class="px-6 py-2.5 bg-admin-modern text-black font-black uppercase text-xs rounded-xl hover:bg-admin-modern/90 transition-all cursor-pointer">
                            Reset Filters
                        </button>
                    </div>

                    <!-- Judgments Grid -->
                    <div v-else class="space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Case Intelligence & Ratio Decidendi Repository</h3>
                                <p class="text-xs text-zinc-400 mt-0.5">Browse extracted binding principles, obiter dicta, and court orders across superior court jurisprudence.</p>
                            </div>
                            <span class="text-xs text-zinc-500 font-bold uppercase shrink-0">
                                Showing {{ visibleCases.length }} of {{ totalFilteredCases }} Judgments
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="c in visibleCases" :key="c.id"
                                class="p-6 rounded-2xl bg-zinc-900/40 border border-white/5 hover:border-admin-modern/40 transition-all flex flex-col justify-between space-y-4 group">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <span class="px-2.5 py-0.5 rounded-lg text-[9px] font-black bg-admin-modern/10 text-admin-modern border border-admin-modern/20">
                                            {{ formatCourtName(c.court) }}
                                        </span>
                                        <div class="flex items-center gap-1.5">
                                            <span v-if="c.reportable" class="px-2 py-0.5 rounded text-[8px] font-black bg-emerald-400/10 text-emerald-400 border border-emerald-400/20">
                                                REPORTABLE
                                            </span>
                                            <span v-if="c.duration_days !== null" class="px-2 py-0.5 rounded text-[8px] font-black bg-rose-400/10 text-rose-400 border border-rose-400/20">
                                                {{ c.duration_days }}d velocity
                                            </span>
                                        </div>
                                    </div>

                                    <h4 class="text-sm font-bold text-white line-clamp-2 group-hover:text-admin-modern transition-colors">
                                        {{ c.title }}
                                    </h4>

                                    <div class="text-[10px] text-zinc-400 space-y-1">
                                        <p><strong class="text-zinc-300">Case No:</strong> {{ c.case_number }}</p>
                                        <p><strong class="text-zinc-300">Parties:</strong> {{ c.applicant }} v {{ c.respondent }}</p>
                                        <p v-if="c.judges && c.judges.length > 0"><strong class="text-zinc-300">Bench:</strong> {{ c.judges.join(', ') }}</p>
                                    </div>

                                    <!-- Ratio Decidendi highlight box -->
                                    <div v-if="c.ratio_decidendi" class="bg-zinc-950/70 p-3.5 rounded-xl border border-white/5 space-y-1">
                                        <span class="text-[8px] font-black text-admin-modern uppercase tracking-widest flex items-center gap-1">
                                            <Sparkles class="w-3 h-3 text-admin-modern" />
                                            <span>Ratio Decidendi</span>
                                        </span>
                                        <p class="text-[11px] text-zinc-300 line-clamp-3 leading-relaxed">
                                            {{ c.ratio_decidendi }}
                                        </p>
                                    </div>
                                    <div v-else-if="c.summary" class="bg-zinc-950/50 p-3 rounded-xl border border-white/5">
                                        <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest block mb-0.5">Summary</span>
                                        <p class="text-[11px] text-zinc-400 line-clamp-2 leading-relaxed">
                                            {{ c.summary }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-white/5">
                                    <span class="text-[10px] text-zinc-500 font-bold">{{ c.judgment_date || c.document_date }}</span>
                                    <button @click="selectedCaseDetail = c"
                                        class="px-3.5 py-1.5 rounded-xl bg-admin-modern/10 hover:bg-admin-modern hover:text-black text-admin-modern font-black text-[10px] uppercase tracking-wider transition-all flex items-center gap-1.5 cursor-pointer">
                                        <Eye class="w-3.5 h-3.5" />
                                        <span>Inspect Full Analysis</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Load More Button -->
                        <div v-if="visibleCases.length < allCases.length" class="flex justify-center pt-4">
                            <button @click="loadMoreCases"
                                class="px-6 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold text-xs uppercase tracking-wider transition-all cursor-pointer">
                                Load More Decisions (Showing {{ visibleCases.length }} of {{ allCases.length }})
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Case Intelligence Detail Modal -->
        <div v-if="selectedCaseDetail"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md transition-all duration-300">
            <div class="bg-zinc-955 border border-white/10 rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden shadow-2xl animate-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="p-6 border-b border-white/5 bg-zinc-900/60 flex items-start justify-between gap-4">
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-lg text-[9px] font-black bg-admin-modern/10 text-admin-modern border border-admin-modern/20">
                                {{ formatCourtName(selectedCaseDetail.court) }}
                            </span>
                            <span v-if="selectedCaseDetail.reportable" class="px-2 py-0.5 rounded text-[8px] font-black bg-emerald-400/10 text-emerald-400 border border-emerald-400/20">
                                REPORTABLE PRECEDENT
                            </span>
                            <span class="text-[10px] text-zinc-500 font-bold">Case: {{ selectedCaseDetail.case_number }}</span>
                        </div>
                        <h3 class="text-base font-black text-white uppercase tracking-tight">{{ selectedCaseDetail.title }}</h3>
                    </div>
                    <button @click="selectedCaseDetail = null" class="text-zinc-500 hover:text-white p-2 rounded-xl hover:bg-white/5 transition-colors shrink-0 cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 custom-scrollbar text-xs text-zinc-300">
                    <!-- Key Metadata Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-zinc-900/30 p-4 rounded-xl border border-white/5">
                        <div>
                            <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Decision Date</span>
                            <span class="font-bold text-white mt-0.5 block">{{ selectedCaseDetail.judgment_date || selectedCaseDetail.document_date || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Hearing Date</span>
                            <span class="font-bold text-white mt-0.5 block">{{ selectedCaseDetail.hearing_date || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Turnaround Duration</span>
                            <span class="font-bold text-admin-modern mt-0.5 block">{{ selectedCaseDetail.duration_days !== null ? selectedCaseDetail.duration_days + ' Days' : 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Location</span>
                            <span class="font-bold text-white mt-0.5 block">{{ selectedCaseDetail.court_location || 'South Africa' }}</span>
                        </div>
                    </div>

                    <!-- Judges & Litigants -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white/[0.02] border border-white/5 p-4 rounded-xl space-y-1">
                            <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Presiding Bench</span>
                            <p class="text-white font-bold">{{ selectedCaseDetail.judges && selectedCaseDetail.judges.length > 0 ? selectedCaseDetail.judges.join(', ') : 'Bench details on record' }}</p>
                        </div>
                        <div class="bg-white/[0.02] border border-white/5 p-4 rounded-xl space-y-1">
                            <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Litigants / Parties</span>
                            <p class="text-white font-bold">{{ selectedCaseDetail.applicant }} <span class="text-zinc-500">v</span> {{ selectedCaseDetail.respondent }}</p>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="space-y-2">
                        <h5 class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Executive Summary</h5>
                        <p class="bg-zinc-900/40 p-4 rounded-xl border border-white/5 leading-relaxed text-zinc-300">
                            {{ selectedCaseDetail.summary }}
                        </p>
                    </div>

                    <!-- Ratio Decidendi -->
                    <div v-if="selectedCaseDetail.ratio_decidendi" class="space-y-2">
                        <h5 class="text-[10px] font-black uppercase tracking-widest text-admin-modern flex items-center gap-1.5">
                            <Sparkles class="w-3.5 h-3.5" /> Ratio Decidendi (Binding Principle)
                        </h5>
                        <div class="bg-admin-modern/5 border border-admin-modern/20 p-5 rounded-xl leading-relaxed text-white font-medium">
                            {{ selectedCaseDetail.ratio_decidendi }}
                        </div>
                    </div>

                    <!-- Obiter Dicta -->
                    <div v-if="selectedCaseDetail.obiter_dicta" class="space-y-2">
                        <h5 class="text-[10px] font-black uppercase tracking-widest text-purple-400">Obiter Dicta (Judicial Remarks)</h5>
                        <p class="bg-purple-500/5 border border-purple-500/20 p-4 rounded-xl leading-relaxed text-zinc-300">
                            {{ selectedCaseDetail.obiter_dicta }}
                        </p>
                    </div>

                    <!-- Order -->
                    <div v-if="selectedCaseDetail.order" class="space-y-2">
                        <h5 class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Court Order & Disposition</h5>
                        <div class="bg-emerald-500/5 border border-emerald-500/20 p-4 rounded-xl leading-relaxed text-zinc-300 whitespace-pre-line font-mono text-[11px]">
                            {{ selectedCaseDetail.order }}
                        </div>
                    </div>

                    <!-- Precedents Cited -->
                    <div v-if="selectedCaseDetail.precedents_cited && selectedCaseDetail.precedents_cited.length > 0" class="space-y-2">
                        <h5 class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Precedents & Statutory Authorities Cited ({{ selectedCaseDetail.precedents_cited.length }})</h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div v-for="p in selectedCaseDetail.precedents_cited" :key="p.case_name_citation || p.raw_citation"
                                class="p-3 rounded-xl bg-zinc-900/60 border border-white/5 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-bold text-white truncate">{{ p.case_name_citation || p.raw_citation }}</p>
                                    <span class="text-[9px] text-zinc-500">Treatment: {{ p.treatment || 'Referred' }}</span>
                                </div>
                                <a v-if="p.url" :href="p.url" target="_blank" rel="noopener noreferrer"
                                    class="text-admin-modern hover:underline text-[10px] flex items-center gap-1 shrink-0">
                                    LawCite <ExternalLink class="w-3 h-3" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-5 border-t border-white/5 bg-zinc-900/60 flex items-center justify-between">
                    <a v-if="selectedCaseDetail.source_url" :href="selectedCaseDetail.source_url" target="_blank" rel="noopener noreferrer"
                        class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 text-xs font-bold flex items-center gap-1.5 transition-all">
                        View Official SAFLII Record <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                    <button @click="selectedCaseDetail = null"
                        class="px-6 py-2 rounded-xl bg-admin-modern text-black font-black text-xs uppercase tracking-wider hover:bg-admin-modern/90 transition-all ml-auto cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Metrics Documentation Modal -->
        <div v-if="isMetricsModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all duration-300">
            <div class="bg-zinc-955 border border-white/10 rounded-2xl w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden shadow-2xl">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-white/5 bg-zinc-900/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-admin-modern/10 flex items-center justify-center text-admin-modern">
                            <Code class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-black text-white uppercase tracking-tight">Jurisprudence Intelligence Logic</h2>
                            <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-0.5">Calculations, Citations Graph & Entity Extraction Rules</p>
                        </div>
                    </div>
                    <button @click="isMetricsModalOpen = false" class="text-zinc-500 hover:text-white transition-colors p-2 rounded-xl hover:bg-white/5 cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar space-y-6 text-xs text-zinc-400 leading-relaxed">
                    <!-- Section 1 -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-black text-admin-modern uppercase tracking-wider">1. Core Jurisprudence Metrics</h3>
                        <ul class="space-y-2 list-disc pl-5">
                            <li>
                                <strong class="text-white">Adjudication Velocity (Speed in Days):</strong> Calculated as the days elapsed between <code class="bg-white/10 px-1 py-0.5 rounded text-white">hearing_date</code> and <code class="bg-white/10 px-1 py-0.5 rounded text-white">judgment_date</code>. Captures court operational turnaround from oral arguments to written judgment delivery.
                            </li>
                            <li>
                                <strong class="text-white">Reportable Landmark Rate:</strong> Percentage of judgments certified by the presiding judges as precedent-setting decisions (reportable in national law reports).
                            </li>
                            <li>
                                <strong class="text-white">Citation Intensity:</strong> Number of precedents, statutory provisions, and secondary legal authorities referenced in each decision.
                            </li>
                        </ul>
                    </div>

                    <!-- Section 2 -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-black text-admin-modern uppercase tracking-wider">2. Precedent Citation Classification</h3>
                        <ul class="space-y-2 list-disc pl-5">
                            <li><strong class="text-white">Applied / Followed:</strong> The court adopted the legal rule or principle articulated in the cited judgment as binding authority.</li>
                            <li><strong class="text-white">Referred:</strong> The citation was noted or considered by the court in comparative context.</li>
                            <li><strong class="text-white">Distinguished / Overruled:</strong> The court distinguished the precedent on facts or overruled earlier jurisprudence.</li>
                        </ul>
                    </div>

                    <!-- Section 3 -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-black text-admin-modern uppercase tracking-wider">3. AI Ratio Decidendi Extraction</h3>
                        <p>
                            Every judgment is processed through high-precision neural extraction pipelines to separate the <strong class="text-white">Ratio Decidendi</strong> (the legal reason for the decision forming binding precedent) from <strong class="text-white">Obiter Dicta</strong> (judicial commentary not essential to the decision).
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-white/5 bg-zinc-900/50 flex justify-end">
                    <button @click="isMetricsModalOpen = false" class="px-5 py-2 bg-admin-modern text-black font-black uppercase text-xs rounded-xl hover:bg-admin-modern/90 transition-all cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>

    </SubscriberLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.15);
}

.text-admin-modern {
    color: #ff8800;
}

.bg-admin-modern\/10 {
    background-color: rgba(255, 136, 0, 0.1);
}

.bg-zinc-955 {
    background-color: #0b0b0d;
}
</style>
