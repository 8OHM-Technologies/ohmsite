<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SubscriberLayout from '@/Layouts/SubscriberLayout.vue';
import VueApexCharts from 'vue3-apexcharts';
import axios from 'axios';
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
const selectedCase = ref(null);
const activePrecedentTab = ref('all');

const tabs = [
    { id: 'overview', label: 'Jurisprudence Overview', icon: Layers },
    { id: 'precedents', label: 'Precedents & Citations Network', icon: BookOpen },
    { id: 'bench', label: 'Judicial Bench & Panels', icon: Users },
    { id: 'cases', label: 'Case Intelligence & Ratio Decidendi', icon: Scale },
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
const casesList = computed(() => analyticsData.value?.cases ?? []);
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

const openCaseDossier = (caseItem) => {
    selectedCase.value = caseItem;
};

const filterByJudgeQuick = (judgeName) => {
    filterJudge.value = judgeName;
    activeTab.value = 'cases';
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
            </div>

            <!-- TAB 2: PRECEDENTS & CITATIONS NETWORK -->
            <div v-if="activeTab === 'precedents'" class="space-y-6">
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
            </div>

            <!-- TAB 3: JUDICIAL BENCH & PANELS -->
            <div v-if="activeTab === 'bench'" class="space-y-6">
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
            </div>

            <!-- TAB 4: CASE INTELLIGENCE & RATIO DECIDENDI EXPLORER -->
            <div v-if="activeTab === 'cases' || activeTab === 'overview'" class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/5 pb-4">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">Judicial Decision Explorer & Dossier Index</h3>
                        <p class="text-xs text-zinc-400">Searchable repository of {{ casesList.length }} detailed court judgments with legal summaries and rulings.</p>
                    </div>
                </div>

                <div v-if="casesList.length === 0" class="py-16 text-center text-zinc-500 text-xs">
                    No court cases found matching the selected filters or search query.
                </div>

                <div v-else class="space-y-3">
                    <div v-for="c in casesList.slice(0, 20)" :key="c.id"
                        class="bg-zinc-950/70 border border-white/5 rounded-2xl p-5 hover:border-admin-modern/30 transition-all space-y-3">
                        <!-- Top Row: Badges & Dates -->
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-admin-modern/10 text-admin-modern border border-admin-modern/20">
                                    {{ c.case_number }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/5 text-zinc-300 border border-white/10">
                                    {{ c.court }}
                                </span>
                                <span v-if="c.reportable" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    Reportable Precedent
                                </span>
                                <span v-if="c.duration_days !== null" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                    {{ c.duration_days }}d to Judgment
                                </span>
                            </div>

                            <div class="flex items-center gap-3 text-xs text-zinc-400">
                                <span v-if="c.judgment_date">{{ c.judgment_date }}</span>
                                <button @click="openCaseDossier(c)"
                                    class="btn btn-primary px-3.5 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-xl flex items-center gap-1.5 shadow-md shadow-primary/20 cursor-pointer">
                                    <span>View Dossier</span>
                                    <Scale class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Case Title -->
                        <h4 class="text-sm font-bold text-white hover:text-admin-modern cursor-pointer" @click="openCaseDossier(c)">
                            {{ c.title }}
                        </h4>

                        <!-- Ratio Decidendi / Summary Excerpt -->
                        <div v-if="c.ratio_decidendi || c.summary" class="bg-zinc-900/60 p-3.5 rounded-xl border border-white/5 text-xs text-zinc-300">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 block mb-1">
                                Ratio Decidendi / Core Principle
                            </span>
                            <p class="line-clamp-2 leading-relaxed">
                                {{ c.ratio_decidendi || c.summary }}
                            </p>
                        </div>

                        <!-- Judges & Citations Footer -->
                        <div class="flex flex-wrap items-center justify-between text-[11px] text-zinc-400 pt-2 border-t border-white/5">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-zinc-500">Bench:</span>
                                <span v-if="c.judges && c.judges.length" class="text-zinc-300">{{ c.judges.join(', ') }}</span>
                                <span v-else class="text-zinc-500">Appellate Court Panel</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span><strong class="text-admin-modern">{{ c.precedents_count }}</strong> Precedents Cited</span>
                                <a v-if="c.source_url" :href="c.source_url" target="_blank" rel="noopener noreferrer"
                                    class="text-zinc-400 hover:text-white flex items-center gap-1">
                                    <span>SAFLII</span>
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comprehensive Case Dossier Modal -->
        <div v-if="selectedCase" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-in fade-in duration-200">
            <div class="bg-zinc-950 border border-white/10 rounded-3xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 sm:p-8 space-y-6 custom-scrollbar shadow-2xl">
                <!-- Modal Header -->
                <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-admin-modern/10 text-admin-modern border border-admin-modern/20">
                                {{ selectedCase.case_number }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/5 text-zinc-300 border border-white/10">
                                {{ selectedCase.court }}
                            </span>
                            <span v-if="selectedCase.reportable" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                Reportable Precedent
                            </span>
                        </div>
                        <h2 class="text-base sm:text-lg font-black text-white mt-2 leading-snug">
                            {{ selectedCase.title }}
                        </h2>
                    </div>
                    <button @click="selectedCase = null" class="p-2 text-zinc-400 hover:text-white rounded-xl bg-white/5 hover:bg-white/10">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Case Metadata Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="bg-zinc-900/50 p-3 rounded-2xl border border-white/5">
                        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Judgment Date</span>
                        <span class="font-bold text-white mt-1 block">{{ selectedCase.judgment_date || 'N/A' }}</span>
                    </div>
                    <div class="bg-zinc-900/50 p-3 rounded-2xl border border-white/5">
                        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Hearing Date</span>
                        <span class="font-bold text-white mt-1 block">{{ selectedCase.hearing_date || 'N/A' }}</span>
                    </div>
                    <div class="bg-zinc-900/50 p-3 rounded-2xl border border-white/5">
                        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Adjudication Duration</span>
                        <span class="font-bold text-admin-modern mt-1 block">{{ selectedCase.duration_days !== null ? selectedCase.duration_days + ' days' : 'N/A' }}</span>
                    </div>
                    <div class="bg-zinc-900/50 p-3 rounded-2xl border border-white/5">
                        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Location</span>
                        <span class="font-bold text-white mt-1 block truncate">{{ selectedCase.court_location }}</span>
                    </div>
                </div>

                <!-- Judicial Bench & Litigants -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-zinc-900/40 p-4 rounded-2xl border border-white/5 space-y-1">
                        <span class="text-[10px] text-admin-modern font-bold uppercase tracking-wider">Judicial Bench</span>
                        <p class="font-medium text-white">
                            {{ selectedCase.judges && selectedCase.judges.length ? selectedCase.judges.join(', ') : 'Superior Court Appellate Bench' }}
                        </p>
                    </div>
                    <div class="bg-zinc-900/40 p-4 rounded-2xl border border-white/5 space-y-1">
                        <span class="text-[10px] text-admin-modern font-bold uppercase tracking-wider">Litigant Parties</span>
                        <p class="font-medium text-white truncate"><strong>Applicant:</strong> {{ selectedCase.applicant }}</p>
                        <p class="font-medium text-zinc-300 truncate"><strong>Respondent:</strong> {{ selectedCase.respondent }}</p>
                    </div>
                </div>

                <!-- Core Legal Intelligence Sections -->
                <div class="space-y-4">
                    <!-- Ratio Decidendi -->
                    <div v-if="selectedCase.ratio_decidendi" class="bg-amber-500/[0.04] border border-amber-500/20 p-5 rounded-2xl space-y-2">
                        <div class="flex items-center gap-2">
                            <Bookmark class="w-4 h-4 text-amber-400" />
                            <span class="text-xs font-black uppercase tracking-wider text-amber-400">Ratio Decidendi (Binding Legal Principle)</span>
                        </div>
                        <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line">
                            {{ selectedCase.ratio_decidendi }}
                        </p>
                    </div>

                    <!-- Executive Summary -->
                    <div v-if="selectedCase.summary" class="bg-zinc-900/60 border border-white/5 p-5 rounded-2xl space-y-2">
                        <div class="flex items-center gap-2">
                            <FileText class="w-4 h-4 text-admin-modern" />
                            <span class="text-xs font-black uppercase tracking-wider text-admin-modern">Executive Case Summary</span>
                        </div>
                        <p class="text-xs text-zinc-300 leading-relaxed whitespace-pre-line">
                            {{ selectedCase.summary }}
                        </p>
                    </div>

                    <!-- Obiter Dicta -->
                    <div v-if="selectedCase.obiter_dicta" class="bg-purple-500/[0.04] border border-purple-500/20 p-5 rounded-2xl space-y-2">
                        <div class="flex items-center gap-2">
                            <Compass class="w-4 h-4 text-purple-400" />
                            <span class="text-xs font-black uppercase tracking-wider text-purple-400">Obiter Dicta (Judicial Observations)</span>
                        </div>
                        <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line">
                            {{ selectedCase.obiter_dicta }}
                        </p>
                    </div>

                    <!-- Order / Relief -->
                    <div v-if="selectedCase.order" class="bg-emerald-500/[0.04] border border-emerald-500/20 p-5 rounded-2xl space-y-2">
                        <div class="flex items-center gap-2">
                            <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                            <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Formal Judicial Order & Relief Granted</span>
                        </div>
                        <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line font-mono text-[11px]">
                            {{ selectedCase.order }}
                        </p>
                    </div>
                </div>

                <!-- Precedents Cited Table -->
                <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-white">
                            Cited Legal Precedents & Authorities ({{ selectedCase.precedents_count }})
                        </span>
                    </div>

                    <div v-if="!selectedCase.precedents_cited || !selectedCase.precedents_cited.length" class="text-xs text-zinc-500">
                        No external precedent citations extracted for this decision.
                    </div>

                    <div v-else class="max-h-60 overflow-y-auto custom-scrollbar">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-white/10 text-zinc-400 font-bold uppercase text-[9px]">
                                    <th class="py-2 px-2">Authority / Citation</th>
                                    <th class="py-2 px-2">Treatment</th>
                                    <th class="py-2 px-2 text-right">Reference</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-zinc-300">
                                <tr v-for="p in selectedCase.precedents_cited" :key="p.case_name_citation" class="hover:bg-white/[0.02]">
                                    <td class="py-2 px-2 font-medium text-white">{{ p.case_name_citation }}</td>
                                    <td class="py-2 px-2">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold"
                                            :class="p.treatment === 'Applied/Followed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-admin-modern/10 text-admin-modern border border-admin-modern/20'">
                                            {{ p.treatment || 'Referred' }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-2 text-right">
                                        <a v-if="p.url" :href="p.url" target="_blank" rel="noopener noreferrer"
                                            class="text-admin-modern hover:underline inline-flex items-center gap-1 text-[10px]">
                                            LawCite <ExternalLink class="w-3 h-3" />
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="border-t border-white/10 pt-4 flex items-center justify-between">
                    <a v-if="selectedCase.source_url" :href="selectedCase.source_url" target="_blank" rel="noopener noreferrer"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/15 text-white flex items-center gap-2 transition-all">
                        <span>Open Source on SAFLII</span>
                        <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                    <button @click="selectedCase = null" class="btn btn-primary px-5 py-2.5 rounded-xl text-xs font-black cursor-pointer">
                        Close Dossier
                    </button>
                </div>
            </div>
        </div>
    </SubscriberLayout>
</template>
