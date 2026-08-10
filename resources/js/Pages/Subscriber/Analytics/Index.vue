<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
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
    BarChart2,
    BookOpen,
    Search,
    Database,
    ExternalLink,
} from 'lucide-vue-next';

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    filters: { type: Array, default: () => [] },
});

// ─── Dataset Selector State ───────────────────────────────────────────────────
const selectedDataset = ref('sabinet_ccma');
const activeGroup = ref('ccma'); // 'ccma' | 'courts' | 'gazettes_journals' | 'other'
const analyticsData = ref(null);
const analyticsLoading = ref(false);

const datasetMode = computed(() => selectedDataset.value === 'sabinet_ccma' ? 'ccma' : 'legal');

const selectedVanityName = computed(() => {
    const f = props.filters.find(f => f.target_name === selectedDataset.value);
    return f?.vanity_name ?? selectedDataset.value;
});

const getFiltersForGroup = (group) => {
    if (group === 'courts') return props.filters.filter(f => f.target_type === 'cases' && f.target_name !== 'sabinet_ccma');
    if (group === 'gazettes_journals') return props.filters.filter(f => f.target_type === 'gaz' || f.target_type === 'journals');
    if (group === 'other') return props.filters.filter(f => f.target_type === 'other');
    return [];
};

const setActiveGroup = (group) => {
    activeGroup.value = group;
    if (group === 'ccma') {
        selectDataset('sabinet_ccma');
    } else {
        const groupFilters = getFiltersForGroup(group);
        if (groupFilters.length > 0) selectDataset(groupFilters[0].target_name);
    }
};

const selectDataset = (targetName) => {
    selectedDataset.value = targetName;
    // Reset tab to overview on dataset change
    activeTab.value = 'overview';
    // Reset CCMA filters
    filterProvince.value = 'All';
    filterCategory.value = 'All';
    filterMonth.value = 'All';
    filterEmployer.value = 'All';
    loadAnalytics();
};

// ─── Analytics Data Loading ───────────────────────────────────────────────────
const loadAnalytics = async () => {
    analyticsLoading.value = true;
    analyticsData.value = null;
    try {
        const params = { target_name: selectedDataset.value };
        if (selectedDataset.value === 'sabinet_ccma') {
            params.province = filterProvince.value;
            params.category = filterCategory.value;
            params.month = filterMonth.value;
            params.employer = filterEmployer.value;
        }
        const { data } = await axios.get('/subscriber/analytics/data', { params });
        analyticsData.value = data;
    } catch (e) {
        console.error('Analytics load failed', e);
    } finally {
        analyticsLoading.value = false;
    }
};

onMounted(() => loadAnalytics());

// ─── Tab Navigation ───────────────────────────────────────────────────────────
const activeTab = ref('overview');
const isTabDropdownOpen = ref(false);

const ccmaTabs = [
    { id: 'overview', label: 'Executive Overview', icon: Layers },
    { id: 'velocity', label: 'Procedural Velocity', icon: Clock },
    { id: 'trends', label: 'Labor & Spatial Trends', icon: TrendingUp },
    { id: 'employer-risk', label: 'Employer Risk Profiling', icon: Building },
];

const legalTabs = [
    { id: 'overview', label: 'Overview', icon: Layers },
    { id: 'temporal', label: 'Temporal Trends', icon: TrendingUp },
    { id: 'courts', label: 'Court Analysis', icon: Scale },
    { id: 'documents', label: 'Document Intelligence', icon: BookOpen },
];

const tabs = computed(() => datasetMode.value === 'ccma' ? ccmaTabs : legalTabs);

// ─── CCMA Filters ─────────────────────────────────────────────────────────────
const filterProvince = ref('All');
const filterCategory = ref('All');
const filterMonth = ref('All');
const filterEmployer = ref('All');

const isMetricsModalOpen = ref(false);

const resetFilters = () => {
    filterProvince.value = 'All';
    filterCategory.value = 'All';
    filterMonth.value = 'All';
    filterEmployer.value = 'All';
    loadAnalytics();
};

// Watch CCMA filter changes and reload (debounced via watcher flush)
watch([filterProvince, filterCategory, filterMonth, filterEmployer], () => {
    if (datasetMode.value === 'ccma') loadAnalytics();
});

// ─── CCMA Computed (derived from analyticsData.cases) ─────────────────────────
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
const topDisputeType = computed(() => {
    if (!enrichedCases.value.length) return 'N/A';
    const counts = {};
    enrichedCases.value.forEach(c => { counts[c.category] = (counts[c.category] || 0) + 1; });
    return Object.entries(counts).sort((a, b) => b[1] - a[1])[0][0];
});
const mostActiveRegion = computed(() => {
    if (!enrichedCases.value.length) return 'N/A';
    const counts = {};
    enrichedCases.value.forEach(c => { counts[c.court_location] = (counts[c.court_location] || 0) + 1; });
    return Object.entries(counts).sort((a, b) => b[1] - a[1])[0][0];
});
const recentCasesStream = computed(() => [...enrichedCases.value].sort((a, b) => new Date(b.award_date) - new Date(a.award_date)).slice(0, 5));

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
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const totalCounts = Array(6).fill(0);
    const retrenchmentCounts = Array(6).fill(0);
    enrichedCases.value.forEach(c => {
        if (c.awardMonthIdx >= 0 && c.awardMonthIdx < 6) {
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
    xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#71717a', fontSize: '11px' } } },
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
    const topRegions = Object.entries(regions).sort((a, b) => b[1].count - a[1].count).slice(0, 5);
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
        share: ((d.count / enrichedCases.value.length) * 100).toFixed(0),
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

// ─── SAFLII Computed (derived from analyticsData) ─────────────────────────────
const legalTotals = computed(() => analyticsData.value?.totals ?? { total: 0, with_case_number: 0, with_date: 0 });
const legalByYear = computed(() => analyticsData.value?.by_year ?? {});
const legalByMonth = computed(() => analyticsData.value?.by_month ?? {});
const legalByDocumentType = computed(() => analyticsData.value?.by_document_type ?? {});
const legalTopCourts = computed(() => analyticsData.value?.top_courts ?? []);
const legalRecent = computed(() => analyticsData.value?.recent ?? []);

// Document Type donut chart
const legalDocTypeChartOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent' },
    colors: ['#ff8800', '#8dd7da', '#a855f7', '#f43f5e', '#38bdf8', '#fbbf24', '#10b981'],
    labels: Object.keys(legalByDocumentType.value),
    stroke: { show: false },
    legend: { show: true, position: 'bottom', labels: { colors: '#a0a0b0' } },
    plotOptions: {
        pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Total', color: '#fff', fontSize: '11px', formatter: () => legalTotals.value.total } } } }
    },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' },
}));
const legalDocTypeSeries = computed(() => Object.values(legalByDocumentType.value));

// Volume by year area chart
const legalYearChartOptions = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, background: 'transparent' },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#ff8800'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.01, stops: [20, 100] } },
    xaxis: { categories: Object.keys(legalByYear.value), axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#71717a', fontSize: '11px' } } },
    yaxis: { labels: { style: { colors: '#71717a' } } },
    grid: { borderColor: 'rgba(255,255,255,0.05)', strokeDashArray: 4 },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' },
}));
const legalYearSeries = computed(() => [{ name: 'Records Published', data: Object.values(legalByYear.value) }]);

// Volume by month bar chart
const legalMonthChartOptions = computed(() => ({
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
    colors: ['#8dd7da'],
    dataLabels: { enabled: false },
    xaxis: { categories: Object.keys(legalByMonth.value), labels: { style: { colors: '#71717a', fontSize: '10px' } } },
    yaxis: { labels: { style: { colors: '#71717a' } } },
    grid: { borderColor: 'rgba(255,255,255,0.05)' },
    tooltip: { theme: 'dark' },
}));
const legalMonthSeries = computed(() => [{ name: 'Records', data: Object.values(legalByMonth.value) }]);

// Top courts horizontal bar
const legalCourtsChartOptions = computed(() => ({
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, barHeight: '50%', borderRadius: 5 } },
    colors: ['#a855f7'],
    dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: 'bold', colors: ['#fff'] } },
    xaxis: { categories: legalTopCourts.value.map(c => c.court), labels: { style: { colors: '#71717a' } } },
    yaxis: { labels: { style: { colors: '#a1a1aa', fontSize: '10px' } } },
    grid: { borderColor: 'rgba(255,255,255,0.05)' },
    tooltip: { theme: 'dark' },
}));
const legalCourtsSeries = computed(() => [{ name: 'Records', data: legalTopCourts.value.map(c => c.count) }]);
</script>

<template>

    <Head title="8OHM | Subscriber Analytics Dashboard">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <SubscriberLayout>
        <div class="space-y-8 animate-in fade-in duration-700">

            <!-- ── Dataset Selector ──────────────────────────────────────────── -->
            <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-6 rounded-[2rem] space-y-4">
                <div class="flex items-center gap-2 mb-4">
                    <Database class="w-4 h-4 text-admin-modern" />
                    <h4 class="text-xs font-black uppercase tracking-widest text-zinc-300">Select Analytics Dataset</h4>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- CCMA -->
                    <button @click="setActiveGroup('ccma')"
                        class="px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all"
                        :class="activeGroup === 'ccma' ? 'btn-admin-modern' : 'btn-secondary'">
                        CCMA Awards
                    </button>
                    <!-- SAFLII Courts -->
                    <button @click="setActiveGroup('courts')"
                        class="px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all"
                        :class="activeGroup === 'courts' ? 'btn-admin-modern' : 'btn-secondary'">
                        SAFLII Courts
                    </button>
                    <!-- Gazettes & Journals -->
                    <button @click="setActiveGroup('gazettes_journals')"
                        class="px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all"
                        :class="activeGroup === 'gazettes_journals' ? 'btn-admin-modern' : 'btn-secondary'">
                        Gazettes & Journals
                    </button>
                    <!-- Court Rolls -->
                    <button @click="setActiveGroup('other')"
                        class="px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all"
                        :class="activeGroup === 'other' ? 'btn-admin-modern' : 'btn-secondary'">
                        Court Rolls & Other
                    </button>
                </div>

                <!-- Sub-selector for groups with multiple targets -->
                <div v-if="['courts', 'gazettes_journals', 'other'].includes(activeGroup)"
                    class="pt-3 border-t border-white/5 flex flex-wrap items-center gap-3">
                    <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Specific Source:</span>
                    <select :value="selectedDataset"
                        @change="selectDataset($event.target.value)"
                        class="bg-black/60 border border-white/10 rounded-xl py-2 px-3 text-xs font-bold text-white focus:ring-1 focus:ring-admin-modern/50 min-w-[240px]">
                        <option v-for="f in getFiltersForGroup(activeGroup)" :key="f.target_name" :value="f.target_name">
                            {{ f.vanity_name }}
                        </option>
                    </select>
                </div>

                <!-- Active dataset label -->
                <div class="flex items-center justify-center gap-2 pt-2 w-full">
                    <span class="w-2 h-2 rounded-full bg-admin-modern animate-pulse"></span>
                    <span class="text-sm font-black uppercase tracking-widest text-zinc-300">
                        {{ selectedVanityName }}
                    </span>
                </div>
            </div>

            <!-- ── Tab Navigation ─────────────────────────────────────────────── -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Mobile Dropdown -->
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
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                        <div v-if="isTabDropdownOpen"
                            class="absolute left-0 right-0 mt-2 bg-zinc-955 border border-white/10 p-2 rounded-2xl shadow-2xl z-50 space-y-1 backdrop-blur-xl">
                            <button v-for="tab in tabs" :key="tab.id"
                                @click="activeTab = tab.id; isTabDropdownOpen = false"
                                :class="activeTab === tab.id ? 'bg-white text-black font-black' : 'text-zinc-400 hover:text-white hover:bg-white/5'"
                                class="flex items-center gap-2 w-full px-5 py-3 rounded-xl text-[10px] uppercase tracking-widest transition-all">
                                <component :is="tab.icon" class="w-3.5 h-3.5" />
                                {{ tab.label }}
                            </button>
                        </div>
                    </transition>
                </div>

                <!-- Desktop Tabs -->
                <div class="hidden lg:flex items-center gap-1 bg-zinc-955 border border-white/5 p-1 rounded-2xl">
                    <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                        :class="activeTab === tab.id ? 'bg-white text-black font-black' : 'text-zinc-500 hover:text-white hover:bg-white/[0.02]'"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shrink-0">
                        <component :is="tab.icon" class="w-3.5 h-3.5" />
                        {{ tab.label }}
                    </button>
                </div>

                <button v-if="datasetMode === 'ccma'" @click="isMetricsModalOpen = true"
                    class="flex items-center gap-2 bg-zinc-900 border border-white/10 hover:bg-zinc-800 text-zinc-300 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shrink-0">
                    <Code class="w-4 h-4 text-admin-modern" />
                    Metrics Logic
                </button>
            </div>

            <!-- ── Loading Skeleton ───────────────────────────────────────────── -->
            <div v-if="analyticsLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="i in 4" :key="i"
                    class="bg-zinc-900/40 border border-white/5 rounded-[2rem] p-8 animate-pulse space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/5"></div>
                    <div class="h-3 bg-white/5 rounded w-2/3"></div>
                    <div class="h-8 bg-white/5 rounded w-1/2"></div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════
                 CCMA MODE
            ══════════════════════════════════════════════════════════════════ -->
            <template v-else-if="datasetMode === 'ccma' && analyticsData">

                <!-- CCMA Filter Panel -->
                <div class="bg-zinc-900/30 backdrop-blur-md border border-white/5 p-6 rounded-[2rem] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <SlidersHorizontal class="w-4 h-4 text-admin-modern" />
                            <h4 class="text-xs font-black uppercase tracking-widest text-zinc-300">Active Analytics Filters</h4>
                        </div>
                        <button @click="resetFilters"
                            class="text-[9px] font-black text-admin-modern/70 hover:text-admin-modern uppercase tracking-widest flex items-center gap-1.5 transition-colors">
                            <RefreshCw class="w-3 h-3" /> Reset Filters
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="flex flex-col space-y-1.5">
                            <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Province / Forum</label>
                            <select v-model="filterProvince"
                                class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                                <option value="All">All Provinces</option>
                                <option v-for="p in filterOptions.provinces" :key="p" :value="p">{{ p }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Dispute Nature</label>
                            <select v-model="filterCategory"
                                class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                                <option value="All">All Dispute Types</option>
                                <option v-for="c in ['Misconduct','Retrenchment','Incapacity','Unfair Labor Practice','Constructive Dismissal','Unfair Dismissal','Mutual Interest','Other']" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Award Timeframe</label>
                            <select v-model="filterMonth"
                                class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                                <option value="All">All Months</option>
                                <option v-for="m in filterOptions.months" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Litigant / Employer</label>
                            <select v-model="filterEmployer"
                                class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                                <option value="All">All Employers</option>
                                <option v-for="e in filterOptions.employers" :key="e" :value="e">{{ e }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- CCMA KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors"><FileText class="w-5 h-5" /></div>
                            <span class="text-[9px] font-black text-admin-modern bg-admin-modern/10 px-2 py-1 rounded-lg">LIVE</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Disputes Audited</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ totalCasesCount }} Cases</p>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors"><Clock class="w-5 h-5" /></div>
                            <span class="text-[9px] font-black text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-lg">VELOCITY</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Avg Hearing Duration</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ avgHearingDuration }} Day(s)</p>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors"><TrendingUp class="w-5 h-5" /></div>
                            <span class="text-[9px] font-black text-sky-400 bg-sky-400/10 px-2 py-1 rounded-lg">AWARDS</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Avg Time-to-Award</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ avgTimeToAward }} Days</p>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors"><AlertTriangle class="w-5 h-5" /></div>
                            <span class="text-[9px] font-black text-rose-400 bg-rose-400/10 px-2 py-1 rounded-lg">LATENCY</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Avg Data Latency</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ avgDataLatency }} Days</p>
                    </div>
                </div>

                <!-- CCMA Tab Content -->
                <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-6">Monthly Labor Dispute Trends</h3>
                        <VueApexCharts type="area" height="260" :options="monthlyTrendOptions" :series="monthlyTrendSeries" />
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-4">Dispute Category Mix</h3>
                        <VueApexCharts type="donut" height="260" :options="disputeTypeChartOptions" :series="disputeTypeSeries" />
                    </div>
                    <div class="lg:col-span-3 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-4">Recent Award Activity</h3>
                        <div class="space-y-3">
                            <div v-for="c in recentCasesStream" :key="c.id"
                                class="flex items-center justify-between py-3 border-b border-white/5 last:border-0">
                                <div>
                                    <p class="text-xs font-black text-white">{{ c.title }}</p>
                                    <p class="text-[10px] text-zinc-500 mt-0.5">{{ c.employer }} · {{ c.court_location }}</p>
                                </div>
                                <span class="text-[10px] font-black px-2 py-1 rounded-lg bg-admin-modern/10 text-admin-modern shrink-0 ml-4">{{ c.category }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'velocity'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-6">Procedural Velocity by Region</h3>
                        <VueApexCharts type="bar" height="300" :options="velocityChartOptions" :series="velocitySeries" />
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] space-y-3">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest">Top 5 Regions by Case Load</h3>
                        <div v-for="(region, idx) in velocityByRegionData.categories" :key="region"
                            class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-lg bg-white/5 text-[10px] font-black flex items-center justify-center text-zinc-400">{{ idx + 1 }}</span>
                                <span class="text-xs font-bold text-zinc-200">{{ region }}</span>
                            </div>
                            <span class="text-[10px] font-black text-admin-modern">{{ velocityByRegionData.hearingAvg[idx] }}d avg</span>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'trends'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-6">Provincial Case Density</h3>
                        <VueApexCharts type="bar" height="300" :options="provincialDensityOptions" :series="provincialDensitySeries" />
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-4">Industry Benchmarking</h3>
                        <div class="space-y-3 max-h-[300px] overflow-y-auto">
                            <div v-for="ind in industryBenchmarking" :key="ind.name"
                                class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                                <div>
                                    <p class="text-xs font-black text-white">{{ ind.name }}</p>
                                    <p class="text-[10px] text-zinc-500">{{ ind.count }} cases · {{ ind.share }}% share</p>
                                </div>
                                <span class="text-[10px] font-black text-zinc-400">{{ ind.avgHearing }}d / {{ ind.avgAward }}d</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'employer-risk'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-4">Top Repeat Litigants</h3>
                        <div class="space-y-2 max-h-[340px] overflow-y-auto">
                            <div v-for="(emp, idx) in repeatAppellants" :key="emp.name"
                                class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-white/5 text-[10px] font-black flex items-center justify-center text-zinc-400">{{ idx + 1 }}</span>
                                    <div>
                                        <p class="text-xs font-black text-white">{{ emp.name }}</p>
                                        <p class="text-[10px] text-zinc-500">{{ emp.industry }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-black px-2 py-1 bg-admin-modern/10 text-admin-modern rounded-lg">{{ emp.count }} cases</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] space-y-4">
                        <div class="flex items-center gap-3">
                            <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest">Employer Profiler</h3>
                        </div>
                        <select v-model="selectedEmployer"
                            class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                            <option value="">Select an employer…</option>
                            <option v-for="e in allEmployersListUnique" :key="e" :value="e">{{ e }}</option>
                        </select>
                        <template v-if="profileEmployerStats">
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="bg-black/30 rounded-xl p-3">
                                    <p class="text-[9px] text-zinc-500 uppercase tracking-widest">Cases</p>
                                    <p class="text-xl font-black text-white">{{ profileEmployerStats.count }}</p>
                                </div>
                                <div class="bg-black/30 rounded-xl p-3">
                                    <p class="text-[9px] text-zinc-500 uppercase tracking-widest">Avg Hearing</p>
                                    <p class="text-xl font-black text-white">{{ profileEmployerStats.avgHearing }}d</p>
                                </div>
                                <div class="bg-black/30 rounded-xl p-3">
                                    <p class="text-[9px] text-zinc-500 uppercase tracking-widest">Avg Award</p>
                                    <p class="text-xl font-black text-white">{{ profileEmployerStats.avgAward }}d</p>
                                </div>
                            </div>
                            <VueApexCharts type="donut" height="220" :options="employerSignatureChartOptions" :series="employerSignatureSeries" />
                        </template>
                        <div v-else class="py-10 text-center text-zinc-500 text-xs font-bold uppercase tracking-widest">Select an employer above</div>
                    </div>
                </div>

            </template>

            <!-- ══════════════════════════════════════════════════════════════════
                 SAFLII / LEGAL MODE
            ══════════════════════════════════════════════════════════════════ -->
            <template v-else-if="datasetMode === 'legal' && analyticsData">

                <!-- Legal KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors"><FileText class="w-5 h-5" /></div>
                            <span class="text-[9px] font-black text-admin-modern bg-admin-modern/10 px-2 py-1 rounded-lg">TOTAL</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Total Records</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ legalTotals.total.toLocaleString() }}</p>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors"><Scale class="w-5 h-5" /></div>
                            <span class="text-[9px] font-black text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-lg">INDEXED</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">With Case Number</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ legalTotals.with_case_number.toLocaleString() }}</p>
                        <p class="text-[10px] text-zinc-500 mt-1" v-if="legalTotals.total > 0">
                            {{ ((legalTotals.with_case_number / legalTotals.total) * 100).toFixed(0) }}% of total
                        </p>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors"><Calendar class="w-5 h-5" /></div>
                            <span class="text-[9px] font-black text-sky-400 bg-sky-400/10 px-2 py-1 rounded-lg">DATED</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Records with Date</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ legalTotals.with_date.toLocaleString() }}</p>
                        <p class="text-[10px] text-zinc-500 mt-1" v-if="legalTotals.total > 0">
                            {{ ((legalTotals.with_date / legalTotals.total) * 100).toFixed(0) }}% coverage
                        </p>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors"><BarChart2 class="w-5 h-5" /></div>
                            <span class="text-[9px] font-black text-purple-400 bg-purple-400/10 px-2 py-1 rounded-lg">TYPES</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Document Types</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ Object.keys(legalByDocumentType).length }}</p>
                    </div>
                </div>

                <!-- Legal Tab Content -->
                <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-6">Volume by Year</h3>
                        <VueApexCharts v-if="Object.keys(legalByYear).length" type="area" height="260" :options="legalYearChartOptions" :series="legalYearSeries" />
                        <div v-else class="flex items-center justify-center h-[260px] text-zinc-600 text-xs font-bold uppercase tracking-widest">No dated records available</div>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-4">Document Type Mix</h3>
                        <VueApexCharts v-if="legalDocTypeSeries.length" type="donut" height="260" :options="legalDocTypeChartOptions" :series="legalDocTypeSeries" />
                        <div v-else class="flex items-center justify-center h-[260px] text-zinc-600 text-xs font-bold uppercase tracking-widest">No type data</div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'temporal'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-6">Annual Publication Volume</h3>
                        <VueApexCharts v-if="Object.keys(legalByYear).length" type="area" height="300" :options="legalYearChartOptions" :series="legalYearSeries" />
                        <div v-else class="flex items-center justify-center h-[300px] text-zinc-600 text-xs uppercase tracking-widest">No data</div>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-6">Monthly Distribution</h3>
                        <VueApexCharts v-if="Object.keys(legalByMonth).length" type="bar" height="300" :options="legalMonthChartOptions" :series="legalMonthSeries" />
                        <div v-else class="flex items-center justify-center h-[300px] text-zinc-600 text-xs uppercase tracking-widest">No data</div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'courts'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-6">Top Courts by Record Volume</h3>
                        <VueApexCharts v-if="legalTopCourts.length" type="bar" height="340" :options="legalCourtsChartOptions" :series="legalCourtsSeries" />
                        <div v-else class="flex items-center justify-center h-[340px] text-zinc-600 text-xs uppercase tracking-widest">No court data</div>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-4">Court Breakdown</h3>
                        <div class="space-y-2 max-h-[340px] overflow-y-auto">
                            <div v-for="(court, idx) in legalTopCourts" :key="court.court"
                                class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-white/5 text-[10px] font-black flex items-center justify-center text-zinc-400">{{ idx + 1 }}</span>
                                    <span class="text-xs font-black text-white">{{ court.court }}</span>
                                </div>
                                <span class="text-[10px] font-black px-2 py-1 bg-purple-500/10 text-purple-300 rounded-lg">{{ court.count.toLocaleString() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'documents'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-4">Document Type Breakdown</h3>
                        <VueApexCharts v-if="legalDocTypeSeries.length" type="donut" height="280" :options="legalDocTypeChartOptions" :series="legalDocTypeSeries" />
                        <div class="space-y-2 mt-4">
                            <div v-for="(count, type) in legalByDocumentType" :key="type"
                                class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                                <span class="text-xs font-black text-zinc-200 capitalize">{{ type }}</span>
                                <span class="text-[10px] font-black text-admin-modern">{{ count.toLocaleString() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem]">
                        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-4">Recent Records</h3>
                        <div class="space-y-3">
                            <div v-for="record in legalRecent" :key="record.case_number ?? record.title"
                                class="py-3 border-b border-white/5 last:border-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-black text-white truncate">{{ record.title }}</p>
                                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                                            <span v-if="record.case_number" class="text-[10px] font-mono text-zinc-500">{{ record.case_number }}</span>
                                            <span v-if="record.court" class="text-[10px] font-black px-1.5 py-0.5 bg-purple-500/10 text-purple-300 rounded">{{ record.court }}</span>
                                            <span v-if="record.document_date" class="text-[10px] text-zinc-500">{{ record.document_date }}</span>
                                        </div>
                                    </div>
                                    <a v-if="record.source_url" :href="record.source_url" target="_blank"
                                        class="text-admin-modern hover:text-admin-modern/70 shrink-0 transition-colors">
                                        <ExternalLink class="w-3.5 h-3.5" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </template>

            <!-- No data state -->
            <div v-else-if="!analyticsLoading"
                class="bg-zinc-900/40 border border-white/5 rounded-[2rem] p-20 text-center">
                <Database class="w-10 h-10 text-zinc-600 mx-auto mb-4" />
                <p class="text-zinc-500 font-black uppercase tracking-widest text-xs">No analytics data available for this dataset yet.</p>
                <p class="text-zinc-600 text-[10px] mt-2">Run the populate commands to import data for this target.</p>
            </div>

        </div>

        <!-- Metrics Logic Modal (CCMA only) -->
        <div v-if="isMetricsModalOpen" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="isMetricsModalOpen = false">
            <div class="bg-zinc-950 border border-white/10 rounded-[2rem] p-8 max-w-lg w-full space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase tracking-widest text-white">Metrics Definitions</h3>
                    <button @click="isMetricsModalOpen = false" class="text-zinc-500 hover:text-white transition"><X class="w-5 h-5" /></button>
                </div>
                <div class="space-y-3 text-xs text-zinc-300">
                    <div class="border-b border-white/5 pb-3"><strong class="text-white">Hearing Duration:</strong> (hearing_end - hearing_start) + 1 day</div>
                    <div class="border-b border-white/5 pb-3"><strong class="text-white">Time-to-Award:</strong> award_date - hearing_end (calendar days)</div>
                    <div class="border-b border-white/5 pb-3"><strong class="text-white">Data Latency:</strong> details_scraped_at - award_date (days)</div>
                    <div><strong class="text-white">Dispute Category:</strong> Parsed from reason_for_dismissal field via keyword matching</div>
                </div>
            </div>
        </div>

    </SubscriberLayout>
</template>
