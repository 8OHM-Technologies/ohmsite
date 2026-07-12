<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import DemoLayout from '@/Layouts/DemoLayout.vue';
import VueApexCharts from "vue3-apexcharts";
import demoData from './demo_data.json';
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
    ChevronDown
} from 'lucide-vue-next';

const props = defineProps({
    cases: {
        type: Array,
        default: () => []
    }
});

// Core active navigation tab
const activeTab = ref('overview'); // overview, velocity, trends, employer-risk

const tabs = [
    { id: 'overview', label: 'Executive Overview', icon: Layers },
    { id: 'velocity', label: 'Procedural Velocity', icon: Clock },
    { id: 'trends', label: 'Labor & Spatial Trends', icon: TrendingUp },
    { id: 'employer-risk', label: 'Employer Risk Profiling', icon: Building }
];

const isTabDropdownOpen = ref(false);

const isMetricsModalOpen = ref(false);

// Interactive Filters State
const filterProvince = ref('All');
const filterCategory = ref('All');
const filterMonth = ref('All');
const filterEmployer = ref('All');

// Interactive Selected Employer for the Profiler in the Employer & Industry tab
const selectedEmployer = ref('Bidvest Protea Coin (Pty) Ltd');

// Helper to parse date strings
const parseDate = (dStr) => dStr ? new Date(dStr.split('T')[0]) : null;

// Parse cases data from prop or fallback to local JSON file
const allCases = computed(() => {
    const rawCases = props.cases && props.cases.length > 0 ? props.cases : demoData;
    return rawCases.map((item, idx) => {
        const start = parseDate(item.hearing_start);
        const end = parseDate(item.hearing_end);
        const award = parseDate(item.award_date);
        const modified = parseDate(item.date_modified);
        const scraped = parseDate(item.details_scraped_at);

        // Turnaround Time calculations (days, inclusive)
        const hearingDuration = start && end ? Math.max(1, Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1) : 1;
        const timeToAward = end && award ? Math.max(0, Math.round((award - end) / (1000 * 60 * 60 * 24))) : 0;
        const systemLag = award && modified ? Math.max(0, Math.round((modified - award) / (1000 * 60 * 60 * 24))) : 0;
        const scrapingLag = award && scraped ? Math.max(0, Math.round((scraped - award) / (1000 * 60 * 60 * 24))) : 0;

        // Parse court location (e.g. Gauteng [Johannesburg] => Province & Region)
        let province = 'Unknown';
        let region = 'Unknown';
        if (item.court_location) {
            const match = item.court_location.match(/^([^\[]+)\s*\[([^\]]+)\]/);
            if (match) {
                province = match[1].trim();
                region = match[2].trim();
            } else {
                province = item.court_location.trim();
            }
        }

        // Map Industry Benchmarking groups
        const empName = item.employer.toLowerCase();
        let industry = 'Other Services';
        if (empName.includes('woolworths') || empName.includes('pick n pay') || empName.includes('spar') || empName.includes('shoprite') || empName.includes('mr price') || empName.includes('truworths') || empName.includes('boxer') || empName.includes('clicks')) {
            industry = 'Retail & Consumer Goods';
        } else if (empName.includes('anglo american') || empName.includes('sibanye') || empName.includes('sasol') || empName.includes('impala')) {
            industry = 'Mining & Resources';
        } else if (empName.includes('bidvest') || empName.includes('g4s') || empName.includes('fidelity') || empName.includes('dhl')) {
            industry = 'Security & Logistics';
        } else if (empName.includes('transnet') || empName.includes('eskom')) {
            industry = 'State Utilities & Transport';
        } else if (empName.includes('mediclinic') || empName.includes('netcare') || empName.includes('unilever') || empName.includes('rainbow chicken')) {
            industry = 'Healthcare & FMCG';
        } else if (empName.includes('vodacom') || empName.includes('mtn') || empName.includes('standard bank') || empName.includes('capitec') || empName.includes('tsogo sun') || empName.includes('psg') || empName.includes('sun international')) {
            industry = 'Finance, Telecoms & Leisure';
        }

        // Month parsing
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const awardMonth = award ? monthNames[award.getMonth()] : 'Unknown';
        const awardMonthIdx = award ? award.getMonth() : 11;

        // Categorize reason_for_dismissal
        const reason = item.reason_for_dismissal.toLowerCase();
        let category = 'Other';
        if (reason.includes('misconduct')) {
            category = 'Misconduct';
        } else if (reason.includes('incapacity')) {
            category = 'Incapacity';
        } else if (reason.includes('unfair labour') || reason.includes('unfair labor')) {
            category = 'Unfair Labor Practice';
        } else if (reason.includes('operational requirements') || reason.includes('retrenchment')) {
            category = 'Retrenchment';
        } else if (reason.includes('constructive')) {
            category = 'Constructive Dismissal';
        } else if (reason.includes('mutual interest')) {
            category = 'Mutual Interest';
        } else if (reason.includes('unfair dismissal')) {
            category = 'Unfair Dismissal';
        }

        return {
            ...item,
            id: idx + 1,
            hearingDuration,
            timeToAward,
            systemLag,
            scrapingLag,
            province,
            region,
            industry,
            awardMonth,
            category,
            awardMonthIdx
        };
    });
});

// Filter Lists
const provincesList = computed(() => {
    const list = new Set(allCases.value.map(c => c.province));
    return ['All', ...Array.from(list).sort()];
});

const categoriesList = computed(() => {
    const list = new Set(allCases.value.map(c => c.category));
    return ['All', ...Array.from(list).sort()];
});

const monthsList = computed(() => {
    const monthOrder = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const list = new Set(allCases.value.map(c => c.awardMonth));
    return ['All', ...monthOrder.filter(m => list.has(m))];
});

const employersList = computed(() => {
    const list = new Set(allCases.value.map(c => c.employer));
    return ['All', ...Array.from(list).sort()];
});

// Reset all interactive filters
const resetFilters = () => {
    filterProvince.value = 'All';
    filterCategory.value = 'All';
    filterMonth.value = 'All';
    filterEmployer.value = 'All';
};

// Filtered subset of cases based on user selection
const filteredCases = computed(() => {
    return allCases.value.filter(item => {
        if (filterProvince.value !== 'All' && item.province !== filterProvince.value) return false;
        if (filterCategory.value !== 'All' && item.category !== filterCategory.value) return false;
        if (filterMonth.value !== 'All' && item.awardMonth !== filterMonth.value) return false;
        if (filterEmployer.value !== 'All' && item.employer !== filterEmployer.value) return false;
        return true;
    });
});

// High-level dashboard KPI statistics
const totalCasesCount = computed(() => filteredCases.value.length);

const avgHearingDuration = computed(() => {
    if (filteredCases.value.length === 0) return 0;
    const sum = filteredCases.value.reduce((acc, c) => acc + c.hearingDuration, 0);
    return (sum / filteredCases.value.length).toFixed(1);
});

const avgTimeToAward = computed(() => {
    if (filteredCases.value.length === 0) return 0;
    const sum = filteredCases.value.reduce((acc, c) => acc + c.timeToAward, 0);
    return (sum / filteredCases.value.length).toFixed(1);
});

const avgDataLatency = computed(() => {
    if (filteredCases.value.length === 0) return 0;
    const sum = filteredCases.value.reduce((acc, c) => acc + c.scrapingLag, 0);
    return (sum / filteredCases.value.length).toFixed(1);
});

const topDisputeType = computed(() => {
    if (filteredCases.value.length === 0) return 'N/A';
    const counts = {};
    filteredCases.value.forEach(c => {
        counts[c.category] = (counts[c.category] || 0) + 1;
    });
    return Object.entries(counts).sort((a, b) => b[1] - a[1])[0][0];
});

const mostActiveRegion = computed(() => {
    if (filteredCases.value.length === 0) return 'N/A';
    const counts = {};
    filteredCases.value.forEach(c => {
        counts[c.court_location] = (counts[c.court_location] || 0) + 1;
    });
    return Object.entries(counts).sort((a, b) => b[1] - a[1])[0][0];
});

// Recent activity stream (latest CCMA updates based on award_date descending)
const recentCasesStream = computed(() => {
    return [...allCases.value]
        .sort((a, b) => new Date(b.award_date) - new Date(a.award_date))
        .slice(0, 5);
});

// Dispute Category Distribution Donut Chart Configuration
const disputeTypeChartOptions = computed(() => {
    const counts = {};
    filteredCases.value.forEach(c => {
        counts[c.category] = (counts[c.category] || 0) + 1;
    });
    const labels = Object.keys(counts);

    return {
        chart: { type: 'donut', background: 'transparent' },
        colors: ['#ff8800', '#8dd7da', '#a855f7', '#f43f5e', '#38bdf8', '#fbbf24', '#10b981'],
        labels: labels,
        stroke: { show: false },
        legend: { show: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        name: { show: true, color: '#fff', fontSize: '12px' },
                        value: { show: true, color: '#a0a0b0', fontSize: '18px', fontWeight: 'bold', formatter: (val) => `${val}` },
                        total: { show: true, label: 'Total Disputes', color: '#fff', fontSize: '11px', formatter: () => filteredCases.value.length }
                    }
                }
            }
        },
        dataLabels: { enabled: false },
        tooltip: { theme: 'dark' }
    };
});

const disputeTypeSeries = computed(() => {
    const counts = {};
    filteredCases.value.forEach(c => {
        counts[c.category] = (counts[c.category] || 0) + 1;
    });
    return Object.values(counts);
});

// Monthly trends line chart configuration (Macro-Economic Indicators)
const monthlyTrendData = computed(() => {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const totalCounts = Array(6).fill(0);
    const retrenchmentCounts = Array(6).fill(0);

    filteredCases.value.forEach(c => {
        const mIdx = c.awardMonthIdx;
        if (mIdx >= 0 && mIdx < 6) {
            totalCounts[mIdx]++;
            if (c.category === 'Retrenchment') {
                retrenchmentCounts[mIdx]++;
            }
        }
    });

    return { months, totalCounts, retrenchmentCounts };
});

const monthlyTrendOptions = {
    chart: {
        type: 'area',
        toolbar: { show: false },
        background: 'transparent',
    },
    stroke: { curve: 'smooth', width: [3, 2], dashArray: [0, 5] },
    colors: ['#ff8800', '#f43f5e'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: [0.3, 0.05],
            opacityTo: [0.01, 0.01],
            stops: [20, 100]
        }
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#71717a', fontSize: '11px' } }
    },
    yaxis: {
        labels: { style: { colors: '#71717a' } },
        grid: { show: true }
    },
    grid: { borderColor: 'rgba(255,255,255,0.05)', strokeDashArray: 4 },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' },
    legend: { show: true, position: 'top', horizontalAlign: 'right', labels: { colors: '#a0a0b0' } }
};

const monthlyTrendSeries = computed(() => {
    return [
        { name: 'Total Labor Disputes', data: monthlyTrendData.value.totalCounts },
        { name: 'Retrenchment Spikes', data: monthlyTrendData.value.retrenchmentCounts }
    ];
});

// Operational Velocity: average lags by location
const velocityByRegionData = computed(() => {
    const regions = {};
    filteredCases.value.forEach(c => {
        if (!regions[c.court_location]) {
            regions[c.court_location] = { count: 0, hearingSum: 0, awardSum: 0, lagSum: 0 };
        }
        regions[c.court_location].count++;
        regions[c.court_location].hearingSum += c.hearingDuration;
        regions[c.court_location].awardSum += c.timeToAward;
        regions[c.court_location].lagSum += c.scrapingLag;
    });

    const topRegions = Object.entries(regions)
        .sort((a, b) => b[1].count - a[1].count)
        .slice(0, 5);

    const categories = topRegions.map(r => r[0]);
    const hearingAvg = topRegions.map(r => (r[1].hearingSum / r[1].count).toFixed(1));
    const awardAvg = topRegions.map(r => (r[1].awardSum / r[1].count).toFixed(1));
    const lagAvg = topRegions.map(r => (r[1].lagSum / r[1].count).toFixed(1));

    return { categories, hearingAvg, awardAvg, lagAvg };
});

const velocityChartOptions = computed(() => ({
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '45%',
            borderRadius: 6
        },
    },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 2, colors: ['transparent'] },
    xaxis: {
        categories: velocityByRegionData.value.categories,
        labels: { style: { colors: '#71717a', fontSize: '9px' } }
    },
    yaxis: {
        title: { text: 'Days', style: { color: '#71717a' } },
        labels: { style: { colors: '#71717a' } }
    },
    colors: ['#8dd7da', '#ff8800', '#f43f5e'],
    tooltip: { theme: 'dark' },
    grid: { borderColor: 'rgba(255,255,255,0.05)' },
    legend: { show: true, position: 'top', horizontalAlign: 'right', labels: { colors: '#a0a0b0' } }
}));

const velocitySeries = computed(() => [
    { name: 'Avg Hearing (Days)', data: velocityByRegionData.value.hearingAvg.map(Number) },
    { name: 'Time to Award (Days)', data: velocityByRegionData.value.awardAvg.map(Number) },
    { name: 'Publishing Lag (Days)', data: velocityByRegionData.value.lagAvg.map(Number) }
]);

// Seasonality & Monthly categories breakdown stacked bar chart configuration
const monthlyCategoryData = computed(() => {
    const categories = ['Misconduct', 'Retrenchment', 'Incapacity', 'Unfair Labor Practice', 'Other'];
    const matrix = {};
    categories.forEach(cat => {
        matrix[cat] = Array(6).fill(0);
    });

    filteredCases.value.forEach(c => {
        const mIdx = c.awardMonthIdx;
        if (mIdx >= 0 && mIdx < 6) {
            let cat = c.category;
            if (!categories.includes(cat)) {
                cat = 'Other';
            }
            matrix[cat][mIdx]++;
        }
    });

    return { categories, matrix };
});

const monthlyCategoryOptions = {
    chart: { type: 'bar', stacked: true, background: 'transparent', toolbar: { show: false } },
    stroke: { width: 1, colors: ['#09090b'] },
    colors: ['#ff8800', '#f43f5e', '#8dd7da', '#a855f7', '#a1a1aa'],
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        labels: { style: { colors: '#71717a' } }
    },
    yaxis: {
        labels: { style: { colors: '#71717a' } }
    },
    legend: { position: 'top', horizontalAlign: 'right', labels: { colors: '#a0a0b0' } },
    fill: { opacity: 0.95 },
    tooltip: { theme: 'dark' },
    grid: { borderColor: 'rgba(255,255,255,0.05)' }
};

const monthlyCategorySeries = computed(() => {
    return monthlyCategoryData.value.categories.map(cat => ({
        name: cat,
        data: monthlyCategoryData.value.matrix[cat]
    }));
});

// Provincial case density horizontal bar chart configuration
const provincialDensityData = computed(() => {
    const provinces = {};
    filteredCases.value.forEach(c => {
        provinces[c.province] = (provinces[c.province] || 0) + 1;
    });

    const sorted = Object.entries(provinces).sort((a, b) => b[1] - a[1]);
    const categories = sorted.map(p => p[0]);
    const counts = sorted.map(p => p[1]);

    return { categories, counts };
});

const provincialDensityOptions = computed(() => ({
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
    plotOptions: {
        bar: {
            horizontal: true,
            barHeight: '50%',
            borderRadius: 6
        }
    },
    colors: ['#ff8800'],
    dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: 'bold' } },
    xaxis: {
        categories: provincialDensityData.value.categories,
        labels: { style: { colors: '#71717a' } }
    },
    yaxis: {
        labels: { style: { colors: '#71717a' } }
    },
    grid: { borderColor: 'rgba(255,255,255,0.05)' },
    tooltip: { theme: 'dark' }
}));

const provincialDensitySeries = computed(() => [
    { name: 'Disputes Count', data: provincialDensityData.value.counts }
]);

// Repeat Appellants leaderboard list
const repeatAppellants = computed(() => {
    const counts = {};
    allCases.value.forEach(c => {
        counts[c.employer] = (counts[c.employer] || 0) + 1;
    });
    return Object.entries(counts)
        .map(([name, count]) => {
            const caseObj = allCases.value.find(c => c.employer === name);
            return {
                name,
                count,
                industry: caseObj ? caseObj.industry : 'Other Services',
                location: caseObj ? caseObj.court_location : 'N/A'
            };
        })
        .sort((a, b) => b.count - a.count)
        .slice(0, 10);
});

// Industry sector benchmarking
const industryBenchmarking = computed(() => {
    const industries = {};
    allCases.value.forEach(c => {
        if (!industries[c.industry]) {
            industries[c.industry] = { count: 0, hearingSum: 0, awardSum: 0 };
        }
        industries[c.industry].count++;
        industries[c.industry].hearingSum += c.hearingDuration;
        industries[c.industry].awardSum += c.timeToAward;
    });

    return Object.entries(industries).map(([name, data]) => {
        return {
            name,
            count: data.count,
            avgHearing: (data.hearingSum / data.count).toFixed(1),
            avgAward: (data.awardSum / data.count).toFixed(1),
            share: ((data.count / allCases.value.length) * 100).toFixed(0)
        };
    }).sort((a, b) => b.count - a.count);
});

// Selector list for the Employer Profiler
const allEmployersListUnique = computed(() => {
    return Array.from(new Set(allCases.value.map(c => c.employer))).sort();
});

// Employer Profiler detailed statistics
const profileEmployerCases = computed(() => {
    return allCases.value.filter(c => c.employer === selectedEmployer.value);
});

const profileEmployerStats = computed(() => {
    const cases = profileEmployerCases.value;
    if (cases.length === 0) return null;

    const hearingSum = cases.reduce((acc, c) => acc + c.hearingDuration, 0);
    const awardSum = cases.reduce((acc, c) => acc + c.timeToAward, 0);
    const scrapingSum = cases.reduce((acc, c) => acc + c.scrapingLag, 0);

    const categoryCounts = {};
    cases.forEach(c => {
        categoryCounts[c.category] = (categoryCounts[c.category] || 0) + 1;
    });

    return {
        count: cases.length,
        avgHearing: (hearingSum / cases.length).toFixed(1),
        avgAward: (awardSum / cases.length).toFixed(1),
        avgScraping: (scrapingSum / cases.length).toFixed(1),
        signature: Object.entries(categoryCounts).map(([cat, count]) => ({
            category: cat,
            count,
            percentage: ((count / cases.length) * 100).toFixed(0)
        })).sort((a, b) => b.count - a.count),
        primaryDispute: Object.entries(categoryCounts).sort((a, b) => b[1] - a[1])[0][0]
    };
});

// Selected employer signature donut chart options
const employerSignatureChartOptions = computed(() => {
    if (!profileEmployerStats.value) return {};
    const labels = profileEmployerStats.value.signature.map(s => s.category);
    return {
        chart: { type: 'donut', background: 'transparent' },
        colors: ['#ff8800', '#8dd7da', '#a855f7', '#f43f5e', '#38bdf8', '#fbbf24', '#10b981'],
        labels: labels,
        stroke: { show: false },
        legend: { show: true, position: 'bottom', labels: { colors: '#a0a0b0' } },
        dataLabels: { enabled: false },
        tooltip: { theme: 'dark' }
    };
});

const employerSignatureSeries = computed(() => {
    if (!profileEmployerStats.value) return [];
    return profileEmployerStats.value.signature.map(s => s.count);
});
</script>

<template>

    <Head title="8OHM | End-to-end Data Solutions">
        <link rel="canonical" href="https://8ohm.co.za/demo" />
    </Head>

    <DemoLayout>
        <div class="space-y-8 animate-in fade-in duration-700">

            <!-- Tabs Navigation -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Mobile Tab Dropdown -->
                <div class="relative lg:hidden w-full">
                    <!-- Click Away Overlay -->
                    <div v-if="isTabDropdownOpen" class="fixed inset-0 z-40" @click="isTabDropdownOpen = false"></div>
                    
                    <!-- Selected Tab Button -->
                    <button 
                        @click="isTabDropdownOpen = !isTabDropdownOpen"
                        class="flex items-center justify-between w-full bg-white text-black font-black px-5 py-2.5 rounded-xl text-[10px] uppercase tracking-widest transition-all relative z-50">
                        <span class="flex items-center gap-2">
                            <component :is="tabs.find(t => t.id === activeTab)?.icon" class="w-3.5 h-3.5" />
                            {{ tabs.find(t => t.id === activeTab)?.label }}
                        </span>
                        <ChevronDown class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': isTabDropdownOpen }" />
                    </button>

                    <!-- Dropdown Options Menu -->
                    <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                    >
                        <div v-if="isTabDropdownOpen" 
                            class="absolute left-0 right-0 mt-2 bg-zinc-955 border border-white/10 p-2 rounded-2xl shadow-2xl z-50 space-y-1 backdrop-blur-xl">
                            <button 
                                v-for="tab in tabs" 
                                :key="tab.id"
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
                <div
                    class="hidden lg:flex items-center gap-1 bg-zinc-955 border border-white/5 p-1 rounded-2xl">
                    <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                        :class="[activeTab === tab.id ? 'bg-white text-black font-black' : 'text-zinc-500 hover:text-white hover:bg-white/[0.02]']"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shrink-0">
                        <component :is="tab.icon" class="w-3.5 h-3.5" />
                        {{ tab.label }}
                    </button>
                </div>

                <button @click="isMetricsModalOpen = true"
                    class="flex items-center gap-2 bg-zinc-900 border border-white/10 hover:bg-zinc-800 text-zinc-300 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shrink-0">
                    <Code class="w-4 h-4 text-admin-modern" />
                    Metrics Logic
                </button>
            </div>

            <!-- Global Collapsible / Sticky Interactive Filter Panel -->
            <div class="bg-zinc-900/30 backdrop-blur-md border border-white/5 p-6 rounded-[2rem] space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <SlidersHorizontal class="w-4 h-4 text-admin-modern" />
                        <h4 class="text-xs font-black uppercase tracking-widest text-zinc-300">Active Analytics Filters
                        </h4>
                    </div>
                    <button @click="resetFilters"
                        class="text-[9px] font-black text-admin-modern/70 hover:text-admin-modern uppercase tracking-widest flex items-center gap-1.5 transition-colors">
                        <RefreshCw class="w-3 h-3" />
                        Reset Filters
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Province Filter -->
                    <div class="flex flex-col space-y-1.5">
                        <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Province /
                            Forum</label>
                        <select v-model="filterProvince"
                            class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                            <option value="All">All Provinces (South Africa)</option>
                            <option v-for="p in provincesList.filter(x => x !== 'All')" :key="p" :value="p">{{ p }}
                            </option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="flex flex-col space-y-1.5">
                        <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Dispute
                            Nature</label>
                        <select v-model="filterCategory"
                            class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                            <option value="All">All Dispute Types</option>
                            <option v-for="c in categoriesList.filter(x => x !== 'All')" :key="c" :value="c">{{ c }}
                            </option>
                        </select>
                    </div>

                    <!-- Month Filter -->
                    <div class="flex flex-col space-y-1.5">
                        <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Award
                            Timeframe</label>
                        <select v-model="filterMonth"
                            class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                            <option value="All">All Months (2026)</option>
                            <option v-for="m in monthsList.filter(x => x !== 'All')" :key="m" :value="m">{{ m }}
                            </option>
                        </select>
                    </div>

                    <!-- Employer Filter -->
                    <div class="flex flex-col space-y-1.5">
                        <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Litigant /
                            Employer</label>
                        <select v-model="filterEmployer"
                            class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                            <option value="All">All Employers</option>
                            <option v-for="e in employersList.filter(x => x !== 'All')" :key="e" :value="e">{{ e }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- High-Level KPI Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Cases Monitored -->
                <div
                    class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <div
                            class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors">
                            <FileText class="w-5 h-5" />
                        </div>
                        <span
                            class="text-[9px] font-black text-admin-modern bg-admin-modern/10 px-2 py-1 rounded-lg">LIVE
                            FEED</span>
                    </div>
                    <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Disputes Audited</p>
                    <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ totalCasesCount }} Cases</p>
                </div>

                <!-- Avg Hearing Duration -->
                <div
                    class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <div
                            class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors">
                            <Clock class="w-5 h-5" />
                        </div>
                        <span
                            class="text-[9px] font-black text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-lg">VELOCITY</span>
                    </div>
                    <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Avg Hearing Duration</p>
                    <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ avgHearingDuration }} Day(s)</p>
                </div>

                <!-- Time to Resolution -->
                <div
                    class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <div
                            class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors">
                            <TrendingUp class="w-5 h-5" />
                        </div>
                        <span
                            class="text-[9px] font-black text-sky-400 bg-sky-400/10 px-2 py-1 rounded-lg">AWARDS</span>
                    </div>
                    <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Avg Time-to-Award</p>
                    <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ avgTimeToAward }} Days</p>
                </div>

                <!-- Publishing Latency -->
                <div
                    class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <div
                            class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors">
                            <AlertTriangle class="w-5 h-5" />
                        </div>
                        <span
                            class="text-[9px] font-black text-rose-400 bg-rose-400/10 px-2 py-1 rounded-lg">LATENCY</span>
                    </div>
                    <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Avg Publishing Lag</p>
                    <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ avgDataLatency }} Days</p>
                </div>
            </div>

            <!-- Empty State Warning when filteredCases are empty -->
            <div v-if="filteredCases.length === 0"
                class="bg-zinc-900/30 backdrop-blur-md border border-white/5 p-16 rounded-[3rem] text-center flex flex-col items-center justify-center space-y-4">
                <AlertTriangle class="w-12 h-12 text-rose-400" />
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-tight">No Cases Found Match Filters</h3>
                    <p class="text-zinc-500 text-xs mt-1">Try resetting or broadening your active analytics filters at
                        the top.</p>
                </div>
                <button @click="resetFilters" class="btn btn-primary btn-sm rounded-xl px-6">Reset Filters</button>
            </div>

            <!-- Tab 1: Executive Overview -->
            <div v-else-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cases Trend & Retrenchments Chart -->
                <div
                    class="lg:col-span-2 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-black text-white uppercase tracking-tighter">Case Volumes & Macro
                                Trends</h3>
                            <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Monthly dispute
                                frequency and economic risk indicators</p>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Top
                                Dispute</span>
                            <p class="text-md font-black text-admin-modern tracking-tighter">{{ topDisputeType }}</p>
                        </div>
                    </div>
                    <div class="flex-1 min-h-[320px]">
                        <VueApexCharts width="100%" height="320" :options="monthlyTrendOptions"
                            :series="monthlyTrendSeries" />
                    </div>
                </div>

                <!-- Dispute Breakdown (Donut) -->
                <div
                    class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-tighter">Dispute Typology</h3>
                        <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Breakdown of case
                            categories</p>
                    </div>

                    <div class="flex-1 flex flex-col items-center justify-center my-6">
                        <VueApexCharts width="230" :options="disputeTypeChartOptions" :series="disputeTypeSeries" />
                    </div>

                    <!-- Custom Legends with Counts -->
                    <div
                        class="grid grid-cols-2 gap-3 border-t border-white/5 pt-6 max-h-[140px] overflow-y-auto custom-scrollbar">
                        <div v-for="(label, i) in disputeTypeChartOptions.labels" :key="label"
                            class="flex items-center justify-between text-[10px]">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full"
                                    :style="{ backgroundColor: disputeTypeChartOptions.colors[i] }"></div>
                                <span class="font-black text-zinc-500 uppercase tracking-wider truncate max-w-[80px]">{{
                                    label }}</span>
                            </div>
                            <span class="font-black text-zinc-300">{{ disputeTypeSeries[i] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Latest Case Log (Bottom Left) -->
                <div
                    class="lg:col-span-2 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] space-y-6">
                    <h3 class="text-lg font-black text-white uppercase tracking-tighter">Active CCMA Case Stream</h3>
                    <div class="space-y-4">
                        <div v-for="c in recentCasesStream" :key="c.id"
                            class="flex items-center justify-between p-4 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-admin-modern/30 transition-all duration-300">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-zinc-800 flex items-center justify-center text-admin-modern border border-white/5">
                                    <FileText class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-xs font-black text-white uppercase tracking-tight truncate max-w-[320px]">
                                        {{ c.employee }} v {{ c.employer }}</p>
                                    <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest mt-1">
                                        {{ c.court_location }} | {{ c.category }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-black text-white">{{ c.award_date }}</p>
                                <p class="text-[9px] font-bold text-zinc-600 uppercase tracking-widest">Award Date</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fast Facts Stats Widget (Bottom Right) -->
                <div
                    class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-tighter">Regional Hotspots</h3>
                        <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Primary source of
                            dispute volume</p>
                    </div>

                    <div class="py-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-white/5 pb-3">
                            <div class="flex items-center gap-2">
                                <MapPin class="w-4 h-4 text-admin-modern" />
                                <span class="text-xs font-black uppercase text-zinc-400">Peak Region</span>
                            </div>
                            <span class="text-xs font-black text-white text-right max-w-[120px] truncate">{{
                                mostActiveRegion }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-white/5 pb-3">
                            <div class="flex items-center gap-2">
                                <Users class="w-4 h-4 text-sky-400" />
                                <span class="text-xs font-black uppercase text-zinc-400">Total Litigants</span>
                            </div>
                            <span class="text-xs font-black text-white">{{ filteredCases.length * 2 }} Parties</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-emerald-400" />
                                <span class="text-xs font-black uppercase text-zinc-400">Average Hearing</span>
                            </div>
                            <span class="text-xs font-black text-white">{{ avgHearingDuration }} Day(s)</span>
                        </div>
                    </div>

                    <div class="bg-admin-modern/5 border border-admin-modern/10 p-4 rounded-2xl flex items-start gap-3">
                        <AlertTriangle class="w-5 h-5 text-admin-modern shrink-0 mt-0.5" />
                        <div>
                            <h5 class="text-[10px] font-black uppercase tracking-wider text-admin-modern">Operational
                                Indicator</h5>
                            <p class="text-[10px] text-zinc-400 mt-1 leading-relaxed">
                                Misconduct cases dominate workplaces. Note retrenchment spikes around April and May
                                representing operational restructuring cycles.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Procedural Velocity -->
            <div v-else-if="activeTab === 'velocity'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Turnaround Times Breakdown by Court Location -->
                <div
                    class="lg:col-span-2 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col">
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-tighter">Velocity breakdown by Court
                        </h3>
                        <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Procedural steps
                            comparing hearing length, resolution times, and scraping lag</p>
                    </div>
                    <div class="flex-1 min-h-[350px] mt-6">
                        <VueApexCharts width="100%" height="350" :options="velocityChartOptions"
                            :series="velocitySeries" />
                    </div>
                </div>

                <!-- Publishing Lag Analysis Card -->
                <div
                    class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-tighter">Lag & Latency</h3>
                        <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Tracking lag
                            between award date, registration, and system updates</p>
                    </div>

                    <div class="space-y-6 my-6">
                        <!-- Stat 1 -->
                        <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                            <div class="flex items-center justify-between text-xs font-black uppercase text-zinc-500">
                                <span>Award Generation Speed</span>
                                <span class="text-white">{{ avgTimeToAward }} Days</span>
                            </div>
                            <div class="w-full bg-zinc-800 h-1.5 rounded-full mt-2.5 overflow-hidden">
                                <div class="bg-admin-modern h-full rounded-full"
                                    :style="{ width: Math.min(100, (avgTimeToAward / 15) * 100) + '%' }"></div>
                            </div>
                            <p class="text-[9px] text-zinc-500 mt-1.5">Days from hearing end until arbitrator signs off
                                award.</p>
                        </div>

                        <!-- Stat 2 -->
                        <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                            <div class="flex items-center justify-between text-xs font-black uppercase text-zinc-500">
                                <span>System Upload Latency</span>
                                <span class="text-emerald-400">
                                    {{(filteredCases.reduce((acc, c) => acc + c.systemLag, 0) / (filteredCases.length
                                        || 1)).toFixed(1)}} Days
                                </span>
                            </div>
                            <div class="w-full bg-zinc-800 h-1.5 rounded-full mt-2.5 overflow-hidden">
                                <div class="bg-emerald-400 h-full rounded-full"
                                    :style="{ width: Math.min(100, ((filteredCases.reduce((acc, c) => acc + c.systemLag, 0) / (filteredCases.length || 1)) / 15) * 100) + '%' }">
                                </div>
                            </div>
                            <p class="text-[9px] text-zinc-500 mt-1.5">Time to register award modifications locally in
                                database logs.</p>
                        </div>

                        <!-- Stat 3 -->
                        <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                            <div class="flex items-center justify-between text-xs font-black uppercase text-zinc-500">
                                <span>Publishing Lag</span>
                                <span class="text-rose-400">{{ avgDataLatency }} Days</span>
                            </div>
                            <div class="w-full bg-zinc-800 h-1.5 rounded-full mt-2.5 overflow-hidden">
                                <div class="bg-rose-400 h-full rounded-full"
                                    :style="{ width: Math.min(100, (avgDataLatency / 150) * 100) + '%' }"></div>
                            </div>
                            <p class="text-[9px] text-zinc-500 mt-1.5">Elapsed time before case modifications are fully
                                scraped or indexed publicly.</p>
                        </div>
                    </div>

                    <div
                        class="text-[10px] text-zinc-500 leading-relaxed bg-zinc-950/40 p-4 rounded-2xl border border-white/5">
                        <span class="font-black uppercase text-zinc-400">Data Bottleneck Summary:</span> Resolution
                        speed remains relatively efficient (under 12 days average), but systemic data publishing delay
                        spans multiple weeks.
                    </div>
                </div>
            </div>

            <!-- Tab 3: Labor & Spatial Trends -->
            <div v-else-if="activeTab === 'trends'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Stacked dispute categories over time -->
                <div
                    class="lg:col-span-2 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col">
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-tighter">Dispute Seasonality &
                            Cycles</h3>
                        <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Monthly category
                            breakdown showing shifts in workplace conflicts</p>
                    </div>
                    <div class="flex-1 min-h-[350px] mt-6">
                        <VueApexCharts width="100%" height="350" :options="monthlyCategoryOptions"
                            :series="monthlyCategorySeries" />
                    </div>
                </div>

                <!-- Provincial Density Bar Chart -->
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col">
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-tighter">Provincial Density</h3>
                        <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Dispute
                            concentrations per South African province</p>
                    </div>
                    <div class="flex-1 min-h-[300px] mt-6">
                        <VueApexCharts width="100%" height="300" :options="provincialDensityOptions"
                            :series="provincialDensitySeries" />
                    </div>
                </div>

                <!-- Hotspots & Seasonality Insights -->
                <div class="lg:col-span-3 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem]">
                    <h3 class="text-lg font-black text-white uppercase tracking-tighter mb-6">Critical Labor Hotspot
                        Indicators</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Spot 1 -->
                        <div class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl">
                            <h5 class="text-xs font-black uppercase text-admin-modern mb-2">North West Mining Hotspot
                            </h5>
                            <p class="text-xs text-zinc-400 leading-relaxed">
                                North West (Rustenburg & Sun City) shows high volumes of **Misconduct / Strike Action**
                                and **Safety Rule Breaches**, representing major mining sector risks.
                            </p>
                        </div>

                        <!-- Spot 2 -->
                        <div class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl">
                            <h5 class="text-xs font-black uppercase text-sky-400 mb-2">Urban Hub Retail Disputes</h5>
                            <p class="text-xs text-zinc-400 leading-relaxed">
                                Gauteng (Johannesburg & Pretoria) and KwaZulu-Natal (Durban) are dense retail
                                environments representing recurrent cases from Boxer, Pick n Pay, Woolworths, and Spar.
                            </p>
                        </div>

                        <!-- Spot 3 -->
                        <div class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl">
                            <h5 class="text-xs font-black uppercase text-emerald-400 mb-2">Seasonality Trends</h5>
                            <p class="text-xs text-zinc-400 leading-relaxed">
                                Misconduct claims spike in early winter (June) and late summer (January), while
                                operational requirements / retrenchments concentrate in Q2 (April & May).
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Employer Risk Profiling & Interactive Audit -->
            <div v-else-if="activeTab === 'employer-risk'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Industry Sector Benchmarking (Left) -->
                <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] lg:col-span-1">
                    <h3 class="text-lg font-black text-white uppercase tracking-tighter mb-2">Sector Risk Benchmarks
                    </h3>
                    <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mb-6">Aggregated corporate
                        dispute profiles by sector</p>

                    <div class="space-y-4 max-h-[450px] overflow-y-auto pr-1 custom-scrollbar">
                        <div v-for="ind in industryBenchmarking" :key="ind.name"
                            class="p-4 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-admin-modern/30 transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-white uppercase tracking-tight">{{ ind.name
                                    }}</span>
                                <span
                                    class="text-[9px] font-black text-admin-modern bg-admin-modern/10 px-2 py-0.5 rounded-lg">{{
                                        ind.share }}% share</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-3 pt-3 border-t border-white/5 text-[10px]">
                                <div>
                                    <p class="text-zinc-500 font-bold uppercase tracking-wider">Avg Hearing</p>
                                    <p class="text-white font-black mt-0.5">{{ ind.avgHearing }} Days</p>
                                </div>
                                <div>
                                    <p class="text-zinc-500 font-bold uppercase tracking-wider">Avg Resolution</p>
                                    <p class="text-white font-black mt-0.5">{{ ind.avgAward }} Days</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Repeat Appellants Leaderboard (Middle/Right top) -->
                <div
                    class="lg:col-span-2 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-tighter">Repeat Appellants
                            Leaderboard</h3>
                        <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Companies appearing
                            most frequently before bargaining councils</p>
                    </div>

                    <div class="my-6 space-y-3">
                        <div v-for="(app, index) in repeatAppellants" :key="app.name"
                            class="flex items-center justify-between p-3.5 rounded-2xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] transition-all">
                            <div class="flex items-center gap-3">
                                <span
                                    class="w-6 h-6 rounded-lg bg-zinc-800 text-[10px] font-black flex items-center justify-center text-admin-modern border border-white/5">
                                    {{ index + 1 }}
                                </span>
                                <div>
                                    <span class="text-xs font-black text-white uppercase tracking-tight">{{ app.name
                                        }}</span>
                                    <p class="text-[9px] text-zinc-500 font-bold uppercase mt-0.5">{{ app.industry }} |
                                        {{ app.location }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <span class="text-xs font-black text-white">{{ app.count }} Cases</span>
                                <button @click="selectedEmployer = app.name"
                                    class="text-[9px] font-black text-admin-modern hover:underline uppercase tracking-widest transition-all">
                                    Audit Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interactive Employer Profiler (Full width at bottom of risk tab) -->
                <div
                    class="lg:col-span-3 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] space-y-6">
                    <div
                        class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/5 pb-6">
                        <div>
                            <h3 class="text-lg font-black text-white uppercase tracking-tighter">Interactive Corporate
                                Audit</h3>
                            <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Deep-dive into
                                specific employer case history & procedural risk signature</p>
                        </div>

                        <!-- Employer Profiler Dropdown -->
                        <div class="flex items-center gap-2 max-w-md w-full">
                            <Building class="w-4 h-4 text-admin-modern shrink-0" />
                            <select v-model="selectedEmployer"
                                class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-4 py-3 outline-none focus:border-admin-modern/30 transition-all font-black uppercase tracking-wider">
                                <option v-for="e in allEmployersListUnique" :key="e" :value="e">{{ e }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Profiler Body -->
                    <div v-if="profileEmployerStats" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left column: Employer Stats and Summary -->
                        <div class="space-y-6 flex flex-col justify-between">
                            <div class="space-y-4">
                                <h4 class="text-sm font-black text-white uppercase tracking-wider">{{ selectedEmployer
                                    }}</h4>

                                <div
                                    class="grid grid-cols-2 gap-4 text-xs font-bold uppercase tracking-wider text-zinc-500">
                                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                                        <p>Audited Cases</p>
                                        <p class="text-2xl font-black text-white mt-1">{{ profileEmployerStats.count }}
                                        </p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                                        <p>Primary Threat</p>
                                        <p class="text-xs font-black text-admin-modern mt-1 truncate">{{
                                            profileEmployerStats.primaryDispute }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-zinc-955 p-5 rounded-2xl border border-white/5 space-y-3.5">
                                <h5 class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Procedural
                                    Benchmarks</h5>

                                <div class="flex items-center justify-between text-xs border-b border-white/5 pb-2.5">
                                    <span class="text-zinc-500 font-bold uppercase text-[10px]">Avg Hearing
                                        Length</span>
                                    <span class="font-black text-white">{{ profileEmployerStats.avgHearing }}
                                        Day(s)</span>
                                </div>
                                <div class="flex items-center justify-between text-xs border-b border-white/5 pb-2.5">
                                    <span class="text-zinc-500 font-bold uppercase text-[10px]">Time-To-Award
                                        Speed</span>
                                    <span class="font-black text-white">{{ profileEmployerStats.avgAward }} Days</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-zinc-500 font-bold uppercase text-[10px]">Sys Scraping Lag</span>
                                    <span class="font-black text-rose-400">{{ profileEmployerStats.avgScraping }}
                                        Days</span>
                                </div>
                            </div>
                        </div>

                        <!-- Middle column: Signature Donut Chart -->
                        <div
                            class="bg-white/[0.01] border border-white/5 p-6 rounded-[2rem] flex flex-col items-center justify-center">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-6 text-center">
                                Employer Dispute Signature</h5>
                            <VueApexCharts width="250" :options="employerSignatureChartOptions"
                                :series="employerSignatureSeries" />
                        </div>

                        <!-- Right column: Case list history for selected employer -->
                        <div
                            class="bg-white/[0.01] border border-white/5 p-6 rounded-[2rem] flex flex-col justify-between">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-4">Litigation
                                History</h5>

                            <div class="space-y-3.5 max-h-[220px] overflow-y-auto pr-1 custom-scrollbar">
                                <div v-for="c in profileEmployerCases" :key="c.id"
                                    class="p-3 rounded-xl bg-zinc-950/50 border border-white/5 hover:border-admin-modern/30 transition-all text-xs">
                                    <div
                                        class="flex items-center justify-between font-black uppercase text-[10px] text-zinc-500 mb-1">
                                        <span>Case: {{ c.award_number }}</span>
                                        <span>{{ c.award_date }}</span>
                                    </div>
                                    <p class="font-black text-white uppercase text-[11px]">{{ c.employee }}</p>
                                    <div
                                        class="flex justify-between items-center mt-2 text-[9px] font-bold text-zinc-500 uppercase">
                                        <span>{{ c.court_location }}</span>
                                        <span class="text-admin-modern bg-admin-modern/10 px-1.5 py-0.5 rounded">{{
                                            c.category }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Metrics Documentation Modal -->
        <div v-if="isMetricsModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all duration-300">
            <div
                class="bg-zinc-950 border border-white/10 rounded-[2rem] w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden shadow-2xl">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-white/5 bg-zinc-900/50">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-admin-modern/10 flex items-center justify-center text-admin-modern">
                            <Code class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-white uppercase tracking-tighter">Dashboard Metrics Logic
                            </h2>
                            <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-0.5">Calculations
                                & Variable Mapping Rules</p>
                        </div>
                    </div>
                    <button @click="isMetricsModalOpen = false"
                        class="text-zinc-500 hover:text-white transition-colors p-2 rounded-xl hover:bg-white/5">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                    <div class="space-y-8 text-sm text-zinc-400 font-medium">

                        <!-- Section 1 -->
                        <div class="space-y-4">
                            <h3 class="text-base font-black text-admin-modern uppercase tracking-tighter">1. Base Case
                                Metrics (Computed per record)</h3>
                            <p>When raw case data is loaded (either via props or from the fallback JSON), it is mapped
                                over to calculate foundational metrics for each case in the <code
                                    class="bg-white/10 px-1.5 py-0.5 rounded text-white">allCases</code> computed
                                property.</p>

                            <h4 class="text-xs font-black text-white uppercase tracking-widest mt-6 mb-2">Time-based
                                Metrics (Calculated in Days):</h4>
                            <ul class="space-y-3 list-disc pl-5">
                                <li>
                                    <strong class="text-white">hearingDuration (Hearing Duration):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    (hearing_end - hearing_start) + 1 day<br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Fallback:</span>
                                    Defaults to 1 if the start or end dates are missing.
                                </li>
                                <li>
                                    <strong class="text-white">timeToAward (Time to Award):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    (award_date - hearing_end)<br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Fallback:</span>
                                    Defaults to 0 if either date is missing. Minimum value is clamped to 0.
                                </li>
                                <li>
                                    <strong class="text-white">systemLag (System Processing Lag):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    (date_modified - award_date)<br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Fallback:</span>
                                    Defaults to 0 if dates are missing. Minimum value is 0.
                                </li>
                                <li>
                                    <strong class="text-white">scrapingLag (Publishing / Scraping Lag):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    (details_scraped_at - award_date)<br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Fallback:</span>
                                    Defaults to 0 if dates are missing. Minimum value is 0.
                                </li>
                            </ul>

                            <h4 class="text-xs font-black text-white uppercase tracking-widest mt-6 mb-2">Categorization
                                Metrics:</h4>
                            <ul class="space-y-3 list-disc pl-5">
                                <li>
                                    <strong class="text-white">province & region:</strong> Extracted from the
                                    court_location field using a Regex <code
                                        class="bg-white/10 px-1 py-0.5 rounded text-white text-[10px]">(^([^\[]+)\s*\[([^\]]+)\])</code>.
                                    E.g., "Gauteng [Johannesburg]" splits into Province: "Gauteng", Region:
                                    "Johannesburg".
                                </li>
                                <li>
                                    <strong class="text-white">industry:</strong> Determined by checking the employer
                                    string for specific keywords.<br>
                                    E.g., Contains "woolworths", "shoprite" &rarr; Retail & Consumer Goods<br>
                                    Contains "eskom", "transnet" &rarr; State Utilities & Transport<br>
                                    If no keyword matches, defaults to Other Services.
                                </li>
                                <li>
                                    <strong class="text-white">category (Dispute Nature):</strong> Determined by
                                    checking the reason_for_dismissal string for keywords (e.g., "misconduct" &rarr;
                                    Misconduct, "retrenchment" or "operational requirements" &rarr; Retrenchment).
                                </li>
                            </ul>
                        </div>

                        <!-- Section 2 -->
                        <div class="space-y-4">
                            <h3 class="text-base font-black text-admin-modern uppercase tracking-tighter">2. High-Level
                                KPI Statistics (Global Filters)</h3>
                            <p>These metrics are displayed at the top of the dashboard and recalculate dynamically based
                                on the active filters (Province, Dispute Nature, Timeframe, Employer) via the <code
                                    class="bg-white/10 px-1.5 py-0.5 rounded text-white">filteredCases</code> computed
                                property.</p>

                            <ul class="space-y-3 list-disc pl-5">
                                <li>
                                    <strong class="text-white">Disputes Audited (totalCasesCount):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    filteredCases.length (Total count of cases matching current filters).
                                </li>
                                <li>
                                    <strong class="text-white">Avg Hearing Duration (avgHearingDuration):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    Sum of hearingDuration for all filtered cases &divide; total filtered cases
                                    (formatted to 1 decimal place).
                                </li>
                                <li>
                                    <strong class="text-white">Avg Time-to-Award (avgTimeToAward):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    Sum of timeToAward for all filtered cases &divide; total filtered cases.
                                </li>
                                <li>
                                    <strong class="text-white">Avg Publishing Lag (avgDataLatency):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    Sum of scrapingLag for all filtered cases &divide; total filtered cases.
                                </li>
                                <li>
                                    <strong class="text-white">Top Dispute Type (topDisputeType):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    The category (e.g., Misconduct, Retrenchment) with the highest frequency count among
                                    the filtered cases.
                                </li>
                                <li>
                                    <strong class="text-white">Most Active Region (mostActiveRegion):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    The court_location with the highest frequency count among the filtered cases.
                                </li>
                            </ul>
                        </div>

                        <!-- Section 3 -->
                        <div class="space-y-4">
                            <h3 class="text-base font-black text-admin-modern uppercase tracking-tighter">3. Executive
                                Overview (Tab 1)</h3>
                            <ul class="space-y-3 list-disc pl-5">
                                <li>
                                    <strong class="text-white">Case Volumes & Macro Trends
                                        (monthlyTrendData):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    Groups cases by their awardMonthIdx. Tracks two lines:<br>
                                    - Total number of labor disputes for that month.<br>
                                    - Number of cases specifically categorized as Retrenchment (used as an economic risk
                                    indicator).
                                </li>
                                <li>
                                    <strong class="text-white">Dispute Typology (disputeTypeSeries):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    A frequency count of cases mapped to their category string, visualized as a Donut
                                    chart.
                                </li>
                            </ul>
                        </div>

                        <!-- Section 4 -->
                        <div class="space-y-4">
                            <h3 class="text-base font-black text-admin-modern uppercase tracking-tighter">4. Procedural
                                Velocity (Tab 2)</h3>
                            <ul class="space-y-3 list-disc pl-5">
                                <li>
                                    <strong class="text-white">Velocity by Region (velocityByRegionData):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    Groups all filtered cases by court_location. Sorts these locations by the highest
                                    total case count and takes the Top 5.<br>
                                    For these 5 regions, it calculates the Average Hearing Duration, Average Time to
                                    Award, and Average Publishing Lag.
                                </li>
                            </ul>
                        </div>

                        <!-- Section 5 -->
                        <div class="space-y-4">
                            <h3 class="text-base font-black text-admin-modern uppercase tracking-tighter">5. Labor &
                                Spatial Trends (Tab 3)</h3>
                            <ul class="space-y-3 list-disc pl-5">
                                <li>
                                    <strong class="text-white">Seasonality & Monthly Breakdown
                                        (monthlyCategoryData):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    Groups cases by month and splits each month's total into specific categories:
                                    Misconduct, Retrenchment, Incapacity, Unfair Labor Practice, and Other.
                                </li>
                                <li>
                                    <strong class="text-white">Provincial Case Density
                                        (provincialDensityData):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    Groups cases by their parsed province attribute, counts the totals, and sorts them
                                    from highest to lowest volume.
                                </li>
                            </ul>
                        </div>

                        <!-- Section 6 -->
                        <div class="space-y-4">
                            <h3 class="text-base font-black text-admin-modern uppercase tracking-tighter">6. Employer
                                Risk Profiling (Tab 4)</h3>
                            <div class="bg-white/5 border border-white/10 rounded-xl p-4 text-xs">
                                <span class="text-admin-modern font-black uppercase tracking-widest">Note:</span> Some
                                of these metrics bypass the global filters and run on <code
                                    class="bg-black/30 px-1 py-0.5 rounded text-white">allCases</code> to provide
                                macro-level benchmarks.
                            </div>

                            <ul class="space-y-3 list-disc pl-5">
                                <li>
                                    <strong class="text-white">Repeat Appellants Leaderboard
                                        (repeatAppellants):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    Looks at allCases, groups by employer, counts total disputes per employer, and
                                    returns the Top 10 employers with the highest frequency.
                                </li>
                                <li>
                                    <strong class="text-white">Industry Sector Benchmarking
                                        (industryBenchmarking):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    Groups allCases by industry. For each industry, it calculates:<br>
                                    - Total cases<br>
                                    - Share percentage: (industry cases / total cases) * 100<br>
                                    - Average Hearing Duration<br>
                                    - Average Time to Award
                                </li>
                                <li>
                                    <strong class="text-white">Employer Profiler (profileEmployerStats):</strong><br>
                                    <span
                                        class="text-zinc-500 uppercase text-[10px] tracking-wider font-bold">Logic:</span>
                                    When an employer is selected from the dropdown (selectedEmployer), it filters the
                                    dataset exactly for that employer and calculates:<br>
                                    - Total case count<br>
                                    - Average Hearing, Award, and Scraping lag metrics for that specific employer.<br>
                                    - Employer Signature: A percentage breakdown of the types of disputes (category)
                                    this employer specifically faces.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DemoLayout>
</template>

<style scoped>
.text-admin-modern {
    color: #ff8800;
}

.bg-admin-modern\/10 {
    background-color: rgba(255, 136, 0, 0.1);
}

.bg-zinc-955 {
    background-color: #0b0b0d;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.1);
}
</style>
