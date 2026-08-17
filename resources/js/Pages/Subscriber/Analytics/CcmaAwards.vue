<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SubscriberLayout from '@/Layouts/SubscriberLayout.vue';
import VueApexCharts from 'vue3-apexcharts';
import axios from 'axios';
import {
    Calendar,
    Clock,
    MapPin,
    TrendingUp,
    Building,
    AlertTriangle,
    FileText,
    RefreshCw,
    SlidersHorizontal,
    Users,
    Layers,
    Code,
    X,
    ChevronDown,
    Scale,
    Briefcase,
    Gavel,
    Search,
    ExternalLink
} from 'lucide-vue-next';

const props = defineProps({
    filters: { type: Array, default: () => [] },
});

const analyticsData = ref(null);
const analyticsLoading = ref(false);

// CCMA Filters
const filterProvince = ref('All');
const filterCategory = ref('All');
const filterMonth = ref('All');
const filterEmployer = ref('All');

const activeTab = ref('overview');
const isMetricsModalOpen = ref(false);
const selectedCase = ref(null);

const tabs = [
    { id: 'overview', label: 'Executive Overview', icon: Layers },
    { id: 'velocity', label: 'Procedural Velocity', icon: Clock },
    { id: 'trends', label: 'Labor & Spatial Trends', icon: TrendingUp },
    { id: 'employer-risk', label: 'Employer Risk Profiling', icon: Building },
];

const loadAnalytics = async () => {
    analyticsLoading.value = true;
    try {
        const params = {
            target_name: 'sabinet_ccma',
            province: filterProvince.value,
            category: filterCategory.value,
            month: filterMonth.value,
            employer: filterEmployer.value,
        };
        const { data } = await axios.get(route('subscriber.analytics.data'), { params });
        analyticsData.value = data;
    } catch (e) {
        console.error('CCMA Analytics load failed', e);
    } finally {
        analyticsLoading.value = false;
    }
};

const resetFilters = () => {
    filterProvince.value = 'All';
    filterCategory.value = 'All';
    filterMonth.value = 'All';
    filterEmployer.value = 'All';
    loadAnalytics();
};

watch([filterProvince, filterCategory, filterMonth, filterEmployer], () => {
    loadAnalytics();
});

onMounted(() => loadAnalytics());

// CCMA Data Processors
const allCases = computed(() => analyticsData.value?.cases ?? []);
const filterOptions = computed(() => analyticsData.value?.filter_options ?? { provinces: [], employers: [], months: [] });

const parseDate = (dStr) => dStr ? new Date(dStr.split('T')[0]) : null;

const enrichedCases = computed(() => {
    return allCases.value.map((item, idx) => {
        const start = parseDate(item.hearing_start);
        const end = parseDate(item.hearing_end);
        const award = parseDate(item.award_date);
        const modified = parseDate(item.date_modified);
        const scraped = parseDate(item.details_scraped_at);

        const hearingDuration = start && end ? Math.max(1, Math.round((end - start) / 86400000) + 1) : 1;
        const timeToAward = end && award ? Math.max(0, Math.round((award - end) / 86400000)) : 0;
        const scrapingLag = award && scraped ? Math.max(0, Math.round((scraped - award) / 86400000)) : 0;

        let province = 'Unknown';
        let region = 'Unknown';
        if (item.court_location) {
            const match = item.court_location.match(/^([^\[]+)\s*\[([^\]]+)\]/);
            if (match) { province = match[1].trim(); region = match[2].trim(); }
            else { province = item.court_location.trim(); }
        }

        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const awardMonth = award ? monthNames[award.getMonth()] : 'Unknown';
        const awardMonthIdx = award ? award.getMonth() : 11;

        return { ...item, id: idx + 1, hearingDuration, timeToAward, scrapingLag, province, region, awardMonth, awardMonthIdx };
    });
});

const totalCasesCount = computed(() => enrichedCases.value.length);
const avgHearingDuration = computed(() => {
    if (!enrichedCases.value.length) return 0;
    return (enrichedCases.value.reduce((a, c) => a + c.hearingDuration, 0) / enrichedCases.value.length).toFixed(1);
});
const avgTimeToAward = computed(() => {
    if (!enrichedCases.value.length) return 0;
    return (enrichedCases.value.reduce((a, c) => a + c.timeToAward, 0) / enrichedCases.value.length).toFixed(1);
});
const avgDataLatency = computed(() => {
    if (!enrichedCases.value.length) return 0;
    return (enrichedCases.value.reduce((a, c) => a + c.scrapingLag, 0) / enrichedCases.value.length).toFixed(1);
});

const disputeTypeChartOptions = computed(() => {
    const counts = {};
    enrichedCases.value.forEach(c => { counts[c.category] = (counts[c.category] || 0) + 1; });
    return {
        chart: { type: 'donut', background: 'transparent' },
        colors: ['#ff8800', '#8dd7da', '#a855f7', '#f43f5e', '#38bdf8', '#fbbf24', '#10b981'],
        labels: Object.keys(counts),
        stroke: { show: false },
        legend: { show: false },
        plotOptions: {
            pie: { donut: { size: '75%', labels: { show: true, name: { show: true, color: '#fff', fontSize: '12px' }, value: { show: true, color: '#a0a0b0', fontSize: '18px', fontWeight: 'bold' }, total: { show: true, label: 'Total', color: '#fff', fontSize: '11px', formatter: () => enrichedCases.value.length } } } }
        },
        dataLabels: { enabled: false },
        tooltip: { theme: 'dark' },
    };
});
const disputeTypeSeries = computed(() => {
    const counts = {};
    enrichedCases.value.forEach(c => { counts[c.category] = (counts[c.category] || 0) + 1; });
    return Object.values(counts);
});

const monthlyTrendSeries = computed(() => {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const totalCounts = Array(12).fill(0);
    const retrenchmentCounts = Array(12).fill(0);
    enrichedCases.value.forEach(c => {
        if (c.awardMonthIdx >= 0 && c.awardMonthIdx < 12) {
            totalCounts[c.awardMonthIdx]++;
            if (c.category === 'Retrenchment') retrenchmentCounts[c.awardMonthIdx]++;
        }
    });
    return [{ name: 'Total Labor Disputes', data: totalCounts }, { name: 'Retrenchment Spikes', data: retrenchmentCounts }];
});
const monthlyTrendOptions = {
    chart: { type: 'area', toolbar: { show: false }, background: 'transparent' },
    stroke: { curve: 'smooth', width: [3, 2], dashArray: [0, 5] },
    colors: ['#ff8800', '#f43f5e'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: [0.3, 0.05], opacityTo: [0.01, 0.01], stops: [20, 100] } },
    xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#71717a', fontSize: '11px' } } },
    yaxis: { labels: { style: { colors: '#71717a' } } },
    grid: { borderColor: 'rgba(255,255,255,0.05)', strokeDashArray: 4 },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' },
    legend: { show: true, position: 'top', horizontalAlign: 'right', labels: { colors: '#a0a0b0' } },
};

const velocityByRegionData = computed(() => {
    const regions = {};
    enrichedCases.value.forEach(c => {
        if (!regions[c.court_location]) regions[c.court_location] = { count: 0, hearingSum: 0, awardSum: 0, lagSum: 0 };
        regions[c.court_location].count++;
        regions[c.court_location].hearingSum += c.hearingDuration;
        regions[c.court_location].awardSum += c.timeToAward;
        regions[c.court_location].lagSum += c.scrapingLag;
    });
    const topRegions = Object.entries(regions).sort((a, b) => b[1].count - a[1].count).slice(0, 6);
    return {
        categories: topRegions.map(r => r[0]),
        hearingAvg: topRegions.map(r => (r[1].hearingSum / r[1].count).toFixed(1)),
        awardAvg: topRegions.map(r => (r[1].awardSum / r[1].count).toFixed(1)),
        lagAvg: topRegions.map(r => (r[1].lagSum / r[1].count).toFixed(1)),
    };
});
const velocityChartOptions = computed(() => ({
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: false, columnWidth: '45%', borderRadius: 6 } },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 2, colors: ['transparent'] },
    xaxis: { categories: velocityByRegionData.value.categories, labels: { style: { colors: '#71717a', fontSize: '9px' } } },
    yaxis: { title: { text: 'Days', style: { color: '#71717a' } }, labels: { style: { colors: '#71717a' } } },
    colors: ['#8dd7da', '#ff8800', '#f43f5e'],
    tooltip: { theme: 'dark' },
    grid: { borderColor: 'rgba(255,255,255,0.05)' },
    legend: { show: true, position: 'top', horizontalAlign: 'right', labels: { colors: '#a0a0b0' } },
}));
const velocitySeries = computed(() => [
    { name: 'Avg Hearing (Days)', data: velocityByRegionData.value.hearingAvg.map(Number) },
    { name: 'Time to Award (Days)', data: velocityByRegionData.value.awardAvg.map(Number) },
    { name: 'Publishing Lag (Days)', data: velocityByRegionData.value.lagAvg.map(Number) },
]);

const provincialDensityData = computed(() => {
    const provinces = {};
    enrichedCases.value.forEach(c => { provinces[c.province] = (provinces[c.province] || 0) + 1; });
    const sorted = Object.entries(provinces).sort((a, b) => b[1] - a[1]);
    return { categories: sorted.map(p => p[0]), counts: sorted.map(p => p[1]) };
});
const provincialDensityOptions = computed(() => ({
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, barHeight: '50%', borderRadius: 6 } },
    colors: ['#ff8800'],
    dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: 'bold' } },
    xaxis: { categories: provincialDensityData.value.categories, labels: { style: { colors: '#71717a' } } },
    yaxis: { labels: { style: { colors: '#71717a' } } },
    grid: { borderColor: 'rgba(255,255,255,0.05)' },
    tooltip: { theme: 'dark' },
}));
const provincialDensitySeries = computed(() => [{ name: 'Disputes', data: provincialDensityData.value.counts }]);

const repeatAppellants = computed(() => {
    const counts = {};
    enrichedCases.value.forEach(c => { counts[c.employer] = (counts[c.employer] || 0) + 1; });
    return Object.entries(counts).map(([name, count]) => {
        const caseObj = enrichedCases.value.find(c => c.employer === name);
        return { name, count, industry: caseObj?.industry ?? 'Other Services', location: caseObj?.court_location ?? 'N/A' };
    }).sort((a, b) => b.count - a.count).slice(0, 10);
});

const industryBenchmarking = computed(() => {
    const industries = {};
    enrichedCases.value.forEach(c => {
        if (!industries[c.industry]) industries[c.industry] = { count: 0, hearingSum: 0, awardSum: 0 };
        industries[c.industry].count++;
        industries[c.industry].hearingSum += c.hearingDuration;
        industries[c.industry].awardSum += c.timeToAward;
    });
    return Object.entries(industries).map(([name, d]) => ({
        name, count: d.count,
        avgHearing: (d.hearingSum / d.count).toFixed(1),
        avgAward: (d.awardSum / d.count).toFixed(1),
        share: enrichedCases.value.length ? ((d.count / enrichedCases.value.length) * 100).toFixed(0) : '0',
    })).sort((a, b) => b.count - a.count);
});

const selectedEmployer = ref('');
const allEmployersListUnique = computed(() => Array.from(new Set(enrichedCases.value.map(c => c.employer))).sort());
const profileEmployerCases = computed(() => enrichedCases.value.filter(c => c.employer === selectedEmployer.value));
const profileEmployerStats = computed(() => {
    const cases = profileEmployerCases.value;
    if (!cases.length) return null;
    const categoryCounts = {};
    cases.forEach(c => { categoryCounts[c.category] = (categoryCounts[c.category] || 0) + 1; });
    return {
        count: cases.length,
        avgHearing: (cases.reduce((a, c) => a + c.hearingDuration, 0) / cases.length).toFixed(1),
        avgAward: (cases.reduce((a, c) => a + c.timeToAward, 0) / cases.length).toFixed(1),
        avgScraping: (cases.reduce((a, c) => a + c.scrapingLag, 0) / cases.length).toFixed(1),
        signature: Object.entries(categoryCounts).map(([cat, count]) => ({ category: cat, count, percentage: ((count / cases.length) * 100).toFixed(0) })).sort((a, b) => b.count - a.count),
        primaryDispute: Object.entries(categoryCounts).sort((a, b) => b[1] - a[1])[0][0],
    };
});
const employerSignatureChartOptions = computed(() => {
    if (!profileEmployerStats.value) return {};
    return {
        chart: { type: 'donut', background: 'transparent' },
        colors: ['#ff8800', '#8dd7da', '#a855f7', '#f43f5e', '#38bdf8', '#fbbf24', '#10b981'],
        labels: profileEmployerStats.value.signature.map(s => s.category),
        stroke: { show: false },
        legend: { show: true, position: 'bottom', labels: { colors: '#a0a0b0' } },
        dataLabels: { enabled: false },
        tooltip: { theme: 'dark' },
    };
});
const employerSignatureSeries = computed(() => profileEmployerStats.value?.signature.map(s => s.count) ?? []);
</script>

<template>
    <Head title="8OHM | CCMA Awards Analytics">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <SubscriberLayout>
        <div class="space-y-8 animate-in fade-in duration-700">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/5 pb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <Briefcase class="w-5 h-5 text-admin-modern" />
                        <h1 class="text-xl sm:text-2xl font-black uppercase tracking-wider text-white">
                            CCMA Arbitration Awards Intelligence
                        </h1>
                    </div>
                    <p class="text-xs text-zinc-400 mt-1 font-medium">
                        Real-time analytics, procedural velocity, and risk profiling across South African labor arbitrations.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="loadAnalytics" :disabled="analyticsLoading"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-white/5 hover:bg-white/10 border border-white/10 text-white flex items-center gap-2 transition-all">
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': analyticsLoading }" />
                        <span>Refresh Data</span>
                    </button>
                    <button @click="resetFilters"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-admin-modern/10 hover:bg-admin-modern/20 text-admin-modern border border-admin-modern/20 transition-all">
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Global Filter Bar -->
            <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-4 rounded-2xl">
                <div class="flex items-center gap-2 mb-3">
                    <SlidersHorizontal class="w-4 h-4 text-admin-modern" />
                    <span class="text-xs font-bold uppercase tracking-widest text-zinc-300">Arbitration Filters</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Province -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 mb-1">Province</label>
                        <select v-model="filterProvince"
                            class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-admin-modern focus:outline-none">
                            <option value="All">All Provinces</option>
                            <option v-for="prov in filterOptions.provinces" :key="prov" :value="prov">{{ prov }}</option>
                        </select>
                    </div>
                    <!-- Category -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 mb-1">Dispute Category</label>
                        <select v-model="filterCategory"
                            class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-admin-modern focus:outline-none">
                            <option value="All">All Categories</option>
                            <option value="Misconduct">Misconduct</option>
                            <option value="Incapacity">Incapacity</option>
                            <option value="Unfair Labor Practice">Unfair Labor Practice</option>
                            <option value="Retrenchment">Retrenchment</option>
                            <option value="Constructive Dismissal">Constructive Dismissal</option>
                            <option value="Mutual Interest">Mutual Interest</option>
                            <option value="Unfair Dismissal">Unfair Dismissal</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <!-- Month -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 mb-1">Award Month</label>
                        <select v-model="filterMonth"
                            class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-admin-modern focus:outline-none">
                            <option value="All">All Months</option>
                            <option v-for="m in filterOptions.months" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>
                    <!-- Employer -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-400 mb-1">Key Employer</label>
                        <select v-model="filterEmployer"
                            class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-admin-modern focus:outline-none">
                            <option value="All">All Employers</option>
                            <option v-for="emp in filterOptions.employers" :key="emp" :value="emp">{{ emp }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tab Selector -->
            <div class="flex items-center gap-2 border-b border-white/5 pb-2 overflow-x-auto custom-scrollbar">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-all shrink-0"
                    :class="activeTab === tab.id ? 'bg-admin-modern text-black shadow-lg shadow-admin-modern/20' : 'text-zinc-400 hover:text-white hover:bg-white/5'">
                    <component :is="tab.icon" class="w-4 h-4" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- TAB 1: EXECUTIVE OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
                <!-- KPI Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Total Arbitrations</span>
                        <div class="text-2xl font-black text-white mt-1">{{ totalCasesCount }}</div>
                        <span class="text-[10px] text-emerald-400 font-medium">Filtered dataset sample</span>
                    </div>
                    <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Avg Hearing Duration</span>
                        <div class="text-2xl font-black text-admin-modern mt-1">{{ avgHearingDuration }} <span class="text-xs font-normal text-zinc-400">days</span></div>
                        <span class="text-[10px] text-zinc-400 font-medium">Session to conclusion</span>
                    </div>
                    <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Avg Decision Velocity</span>
                        <div class="text-2xl font-black text-purple-400 mt-1">{{ avgTimeToAward }} <span class="text-xs font-normal text-zinc-400">days</span></div>
                        <span class="text-[10px] text-zinc-400 font-medium">Hearing end to award date</span>
                    </div>
                    <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Avg Ingestion Latency</span>
                        <div class="text-2xl font-black text-rose-400 mt-1">{{ avgDataLatency }} <span class="text-xs font-normal text-zinc-400">days</span></div>
                        <span class="text-[10px] text-zinc-400 font-medium">Award issue to scrape date</span>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4">Primary Dispute Categories</h3>
                        <VueApexCharts type="donut" height="300" :options="disputeTypeChartOptions" :series="disputeTypeSeries" />
                    </div>
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4">Seasonal Arbitration Inflow & Retrenchment Spikes</h3>
                        <VueApexCharts type="area" height="300" :options="monthlyTrendOptions" :series="monthlyTrendSeries" />
                    </div>
                </div>
            </div>

            <!-- TAB 2: PROCEDURAL VELOCITY -->
            <div v-if="activeTab === 'velocity'" class="space-y-6">
                <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4">Regional Process Efficiency (Hearing, Award & Publishing Delay)</h3>
                    <VueApexCharts type="bar" height="340" :options="velocityChartOptions" :series="velocitySeries" />
                </div>
            </div>

            <!-- TAB 3: SPATIAL & LABOR TRENDS -->
            <div v-if="activeTab === 'trends'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4">Geographical Dispute Distribution by Province</h3>
                        <VueApexCharts type="bar" height="320" :options="provincialDensityOptions" :series="provincialDensitySeries" />
                    </div>
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4">Industry Sector Benchmarking</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-white/10 text-zinc-400 font-bold uppercase text-[10px]">
                                        <th class="py-2">Industry Sector</th>
                                        <th class="py-2">Cases</th>
                                        <th class="py-2">Avg Hearing</th>
                                        <th class="py-2">Avg Award</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 text-zinc-300">
                                    <tr v-for="ind in industryBenchmarking" :key="ind.name">
                                        <td class="py-2.5 font-medium text-white">{{ ind.name }}</td>
                                        <td class="py-2.5">{{ ind.count }} ({{ ind.share }}%)</td>
                                        <td class="py-2.5">{{ ind.avgHearing }}d</td>
                                        <td class="py-2.5">{{ ind.avgAward }}d</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: EMPLOYER RISK PROFILING -->
            <div v-if="activeTab === 'employer-risk'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Top Repeat Appellants -->
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl lg:col-span-1">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4">Most Frequent Respondent Employers</h3>
                        <div class="space-y-2">
                            <button v-for="emp in repeatAppellants" :key="emp.name" @click="selectedEmployer = emp.name"
                                class="w-full text-left p-3 rounded-xl border transition-all text-xs"
                                :class="selectedEmployer === emp.name ? 'bg-admin-modern/10 border-admin-modern/30 text-white' : 'bg-white/[0.02] border-white/5 text-zinc-400 hover:text-white hover:bg-white/5'">
                                <div class="font-bold truncate text-white">{{ emp.name }}</div>
                                <div class="flex items-center justify-between text-[10px] text-zinc-400 mt-1">
                                    <span>{{ emp.industry }}</span>
                                    <span class="font-bold text-admin-modern">{{ emp.count }} cases</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Right: Selected Employer Profile -->
                    <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl lg:col-span-2 space-y-6">
                        <div v-if="!profileEmployerStats" class="py-16 text-center text-zinc-500 text-xs">
                            Select an employer from the list to view their risk profile & dispute signature.
                        </div>
                        <div v-else class="space-y-6">
                            <div class="border-b border-white/5 pb-4">
                                <h4 class="text-base font-black text-white">{{ selectedEmployer }}</h4>
                                <p class="text-xs text-zinc-400">Total Arbitrations in Records: <span class="text-admin-modern font-bold">{{ profileEmployerStats.count }}</span></p>
                            </div>

                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="bg-zinc-950 p-3 rounded-xl border border-white/5">
                                    <span class="text-[9px] uppercase tracking-wider text-zinc-500 font-bold">Avg Hearing</span>
                                    <div class="text-base font-black text-white mt-1">{{ profileEmployerStats.avgHearing }}d</div>
                                </div>
                                <div class="bg-zinc-950 p-3 rounded-xl border border-white/5">
                                    <span class="text-[9px] uppercase tracking-wider text-zinc-500 font-bold">Avg Award Time</span>
                                    <div class="text-base font-black text-admin-modern mt-1">{{ profileEmployerStats.avgAward }}d</div>
                                </div>
                                <div class="bg-zinc-950 p-3 rounded-xl border border-white/5">
                                    <span class="text-[9px] uppercase tracking-wider text-zinc-500 font-bold">Primary Risk</span>
                                    <div class="text-xs font-black text-rose-400 mt-1 truncate">{{ profileEmployerStats.primaryDispute }}</div>
                                </div>
                            </div>

                            <div>
                                <h5 class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Dispute Signature Breakdown</h5>
                                <VueApexCharts type="donut" height="260" :options="employerSignatureChartOptions" :series="employerSignatureSeries" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Cases Table Section -->
            <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white">Recent Arbitration Awards Stream</h3>
                    <span class="text-xs text-zinc-500 font-medium">Showing {{ enrichedCases.length }} records</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-white/10 text-zinc-400 font-bold uppercase text-[10px]">
                                <th class="py-2.5 px-3">Award Ref</th>
                                <th class="py-2.5 px-3">Employer / Respondent</th>
                                <th class="py-2.5 px-3">Dispute Category</th>
                                <th class="py-2.5 px-3">Location</th>
                                <th class="py-2.5 px-3">Award Date</th>
                                <th class="py-2.5 px-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-zinc-300">
                            <tr v-for="c in enrichedCases.slice(0, 15)" :key="c.id" class="hover:bg-white/[0.02] transition-colors">
                                <td class="py-3 px-3 font-mono text-[11px] text-zinc-400">{{ c.award_number || ('#CCMA-' + c.id) }}</td>
                                <td class="py-3 px-3 font-medium text-white max-w-xs truncate">{{ c.employer || 'N/A' }}</td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-admin-modern/10 text-admin-modern border border-admin-modern/20">
                                        {{ c.category }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-zinc-400">{{ c.court_location || 'N/A' }}</td>
                                <td class="py-3 px-3 text-zinc-400">{{ c.award_date ? c.award_date.split('T')[0] : 'N/A' }}</td>
                                <td class="py-3 px-3 text-right">
                                    <button @click="selectedCase = c"
                                        class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded bg-white/5 hover:bg-white/10 text-white transition-all">
                                        View Case
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Case Detail Modal -->
        <div v-if="selectedCase" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-zinc-950 border border-white/10 rounded-2xl w-full max-w-2xl max-h-[85vh] overflow-y-auto p-6 space-y-5 custom-scrollbar">
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-admin-modern">CCMA Award Details</span>
                        <h3 class="text-base font-black text-white mt-1">{{ selectedCase.title || selectedCase.award_number }}</h3>
                    </div>
                    <button @click="selectedCase = null" class="p-2 text-zinc-500 hover:text-white rounded-lg">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-zinc-900/50 p-3 rounded-xl border border-white/5">
                        <span class="text-[10px] text-zinc-500 font-bold uppercase">Employer</span>
                        <p class="font-bold text-white mt-0.5">{{ selectedCase.employer || 'N/A' }}</p>
                    </div>
                    <div class="bg-zinc-900/50 p-3 rounded-xl border border-white/5">
                        <span class="text-[10px] text-zinc-500 font-bold uppercase">Employee / Applicant</span>
                        <p class="font-bold text-white mt-0.5">{{ selectedCase.employee || 'N/A' }}</p>
                    </div>
                    <div class="bg-zinc-900/50 p-3 rounded-xl border border-white/5">
                        <span class="text-[10px] text-zinc-500 font-bold uppercase">Court / Location</span>
                        <p class="font-medium text-zinc-300 mt-0.5">{{ selectedCase.court_location || 'N/A' }}</p>
                    </div>
                    <div class="bg-zinc-900/50 p-3 rounded-xl border border-white/5">
                        <span class="text-[10px] text-zinc-500 font-bold uppercase">Dismissal Reason / Subject</span>
                        <p class="font-medium text-zinc-300 mt-0.5">{{ selectedCase.reason_for_dismissal || selectedCase.category }}</p>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex justify-end">
                    <button @click="selectedCase = null" class="px-4 py-2 text-xs font-bold bg-white/10 hover:bg-white/15 text-white rounded-xl">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </SubscriberLayout>
</template>
