<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import DemoLayout from '@/Layouts/DemoLayout.vue';
import VueApexCharts from 'vue3-apexcharts';
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
    Award,
    ChevronDown,
    Code,
    Sparkles,
    Eye
} from 'lucide-vue-next';

const props = defineProps({
    cases: {
        type: Array,
        default: () => []
    }
});

// Active Navigation Tab
const activeTab = ref('overview'); // overview, precedents, bench, cases

const tabs = [
    { id: 'overview', label: 'Jurisprudence Overview', icon: Layers },
    { id: 'precedents', label: 'Precedents & Citations Network', icon: BookOpen },
    { id: 'bench', label: 'Judicial Bench & Panels', icon: Users },
    { id: 'cases', label: 'Ratio Decidendi Explorer', icon: Gavel },
];

const isTabDropdownOpen = ref(false);
const isMetricsModalOpen = ref(false);
const selectedCaseDetail = ref(null);

// Interactive Filters State
const filterCourt = ref('All');
const filterJudge = ref('All');
const filterYear = ref('All');
const filterReportable = ref('All');
const searchQuery = ref('');

// Helper to parse date strings
const parseDate = (dStr) => dStr ? new Date(dStr.split('T')[0]) : null;

// Parse cases data from prop
const allCases = computed(() => {
    const rawCases = props.cases && props.cases.length > 0 ? props.cases : [];
    return rawCases.map((item, idx) => {
        const hDate = parseDate(item.hearing_date || item.hearing_start);
        const jDate = parseDate(item.judgment_date || item.document_date || item.award_date);

        // Turnaround Time / Adjudication Speed calculations (days)
        let durationDays = null;
        if (hDate && jDate) {
            durationDays = Math.max(0, Math.round((jDate - hDate) / (1000 * 60 * 60 * 24)));
        }

        const jYear = jDate ? String(jDate.getFullYear()) : (item.document_date ? item.document_date.substring(0, 4) : 'Unknown');

        // Judges list normalization
        let judges = item.judges || [];
        if (!Array.isArray(judges)) {
            judges = judges ? [judges] : [];
        }
        judges = judges.filter(j => j && !String(j).startsWith('[Not explicitly'));

        // Precedents list normalization
        let precedents = item.precedents_cited || [];
        if (!Array.isArray(precedents)) {
            precedents = [];
        }

        // Keywords normalization
        let keywords = item.keywords || item.subjects || [];
        if (!Array.isArray(keywords)) {
            keywords = typeof keywords === 'string' ? keywords.split(',').map(s => s.trim()) : [];
        }

        const courtName = item.court || item.target_name || 'Superior Court';
        const courtLocation = item.court_location || 'South Africa';
        const isReportable = typeof item.reportable === 'boolean' ? item.reportable : true;

        return {
            ...item,
            id: item.id || idx + 1,
            title: item.title || 'Superior Court Judgment',
            case_number: item.case_number || 'N/A',
            court: courtName,
            court_location: courtLocation,
            decisionYear: jYear,
            durationDays,
            reportable: isReportable,
            judges,
            precedents_cited: precedents,
            precedents_count: precedents.length,
            keywords: keywords.length > 0 ? keywords : ['Jurisprudence', 'Constitutional Law'],
            applicant: item.applicant || item.applicant_plaintiff || 'Applicant',
            respondent: item.respondent || item.respondent_defendant || 'Respondent',
            summary: item.summary || 'Superior court judgment and appellate decision.',
            ratio_decidendi: item.ratio_decidendi || null,
            obiter_dicta: item.obiter_dicta || null,
            order: item.order || null,
        };
    });
});

// Dynamic Filter Lists
const courtsList = computed(() => {
    const list = new Set(allCases.value.map(c => c.court));
    return ['All', ...Array.from(list).filter(Boolean).sort()];
});

const judgesList = computed(() => {
    const list = new Set();
    allCases.value.forEach(c => c.judges.forEach(j => list.add(j)));
    return ['All', ...Array.from(list).filter(Boolean).sort()];
});

const yearsList = computed(() => {
    const list = new Set(allCases.value.map(c => c.decisionYear).filter(y => y !== 'Unknown'));
    return ['All', ...Array.from(list).sort().reverse()];
});

// Reset all interactive filters
const resetFilters = () => {
    filterCourt.value = 'All';
    filterJudge.value = 'All';
    filterYear.value = 'All';
    filterReportable.value = 'All';
    searchQuery.value = '';
};

// Filtered subset of cases based on user selection
const filteredCases = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    return allCases.value.filter(item => {
        if (filterCourt.value !== 'All' && item.court !== filterCourt.value) return false;
        if (filterJudge.value !== 'All' && !item.judges.includes(filterJudge.value)) return false;
        if (filterYear.value !== 'All' && item.decisionYear !== filterYear.value) return false;
        if (filterReportable.value !== 'All') {
            const isRep = filterReportable.value === 'Yes';
            if (item.reportable !== isRep) return false;
        }
        if (q) {
            const haystack = `${item.title} ${item.case_number} ${item.court} ${item.applicant} ${item.respondent} ${item.summary || ''} ${item.ratio_decidendi || ''} ${item.judges.join(' ')} ${item.keywords.join(' ')}`.toLowerCase();
            if (!haystack.includes(q)) return false;
        }
        return true;
    });
});

// High-level dashboard KPI statistics
const totalCasesCount = computed(() => filteredCases.value.length);

const reportableCount = computed(() => filteredCases.value.filter(c => c.reportable).length);
const reportablePercentage = computed(() => {
    if (filteredCases.value.length === 0) return 0;
    return Math.round((reportableCount.value / filteredCases.value.length) * 100);
});

const totalPrecedentsCount = computed(() => {
    return filteredCases.value.reduce((acc, c) => acc + c.precedents_count, 0);
});

const avgPrecedentsPerCase = computed(() => {
    if (filteredCases.value.length === 0) return 0;
    return (totalPrecedentsCount.value / filteredCases.value.length).toFixed(1);
});

const avgAdjudicationSpeed = computed(() => {
    const valid = filteredCases.value.filter(c => c.durationDays !== null);
    if (valid.length === 0) return 0;
    const sum = valid.reduce((acc, c) => acc + c.durationDays, 0);
    return (sum / valid.length).toFixed(1);
});

// Recent Jurisprudence Decisions Stream (top 5 by date)
const recentCasesStream = computed(() => {
    return [...filteredCases.value]
        .sort((a, b) => new Date(b.judgment_date || b.document_date || 0) - new Date(a.judgment_date || a.document_date || 0))
        .slice(0, 6);
});

// 1. Timeline & Adjudication Speed Area Chart Configuration
const timelineData = computed(() => {
    const yearsMap = {};
    filteredCases.value.forEach(c => {
        const yr = c.decisionYear;
        if (yr && yr !== 'Unknown') {
            if (!yearsMap[yr]) {
                yearsMap[yr] = { count: 0, durationSum: 0, durationCount: 0 };
            }
            yearsMap[yr].count++;
            if (c.durationDays !== null) {
                yearsMap[yr].durationSum += c.durationDays;
                yearsMap[yr].durationCount++;
            }
        }
    });

    const sortedYears = Object.keys(yearsMap).sort();
    const counts = sortedYears.map(y => yearsMap[y].count);
    const avgSpeeds = sortedYears.map(y => yearsMap[y].durationCount > 0 ? Math.round(yearsMap[y].durationSum / yearsMap[y].durationCount) : 0);

    return { years: sortedYears, counts, avgSpeeds };
});

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
        categories: timelineData.value.years,
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
    { name: 'Judgments Published', type: 'area', data: timelineData.value.counts },
    { name: 'Adjudication Speed (Days)', type: 'line', data: timelineData.value.avgSpeeds },
]);

// 2. Court Authorities Breakdown
const courtsBreakdown = computed(() => {
    const map = {};
    filteredCases.value.forEach(c => {
        map[c.court] = (map[c.court] || 0) + 1;
    });
    const total = filteredCases.value.length || 1;
    return Object.entries(map)
        .map(([court, count]) => ({
            court,
            count,
            percentage: ((count / total) * 100).toFixed(0)
        }))
        .sort((a, b) => b.count - a.count);
});

// 3. Typology / Subject Matters Donut Chart
const typologyData = computed(() => {
    const counts = {};
    filteredCases.value.forEach(c => {
        c.keywords.forEach(k => {
            counts[k] = (counts[k] || 0) + 1;
        });
    });
    const topEntries = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 6);
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
                    total: { show: true, label: 'Top Subjects', color: '#fff', fontSize: '10px', formatter: () => typologyData.value.series.reduce((a, b) => a + b, 0) }
                }
            }
        }
    },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' }
}));

// 4. Precedents Intelligence: Treatments Donut
const precedentTreatments = computed(() => {
    const treatments = {
        'Applied/Followed': 0,
        'Referred': 0,
        'Distinguished/Overruled': 0,
        'Other': 0,
    };
    filteredCases.value.forEach(c => {
        c.precedents_cited.forEach(p => {
            const t = p.treatment || 'Referred';
            if (t.includes('Applied') || t.includes('Followed')) {
                treatments['Applied/Followed']++;
            } else if (t.includes('Distinguished') || t.includes('Overruled')) {
                treatments['Distinguished/Overruled']++;
            } else if (t.includes('Referred') || t.includes('cited') || t.includes('Cited')) {
                treatments['Referred']++;
            } else {
                treatments['Other']++;
            }
        });
    });
    return treatments;
});

const treatmentsChartOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent' },
    colors: ['#8dd7da', '#ff8800', '#f43f5e', '#a855f7'],
    labels: Object.keys(precedentTreatments.value),
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
                        formatter: () => totalPrecedentsCount.value.toLocaleString()
                    }
                }
            }
        }
    },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' }
}));

const treatmentsSeries = computed(() => Object.values(precedentTreatments.value));

// 5. Precedent Density Distribution Bar Chart
const precedentDensity = computed(() => {
    const buckets = {
        '0 Citations': 0,
        '1–5 Citations': 0,
        '6–15 Citations': 0,
        '16–30 Citations': 0,
        '30+ Citations': 0,
    };
    filteredCases.value.forEach(c => {
        const count = c.precedents_count;
        if (count === 0) buckets['0 Citations']++;
        else if (count <= 5) buckets['1–5 Citations']++;
        else if (count <= 15) buckets['6–15 Citations']++;
        else if (count <= 30) buckets['16–30 Citations']++;
        else buckets['30+ Citations']++;
    });
    return buckets;
});

const densityChartOptions = computed(() => ({
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
    colors: ['#ff8800'],
    dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: 'bold' } },
    xaxis: {
        categories: Object.keys(precedentDensity.value),
        labels: { style: { colors: '#71717a', fontSize: '9px' } }
    },
    yaxis: {
        title: { text: 'Judgments Count', style: { color: '#71717a', fontSize: '10px' } },
        labels: { style: { colors: '#71717a' } }
    },
    grid: { borderColor: 'rgba(255,255,255,0.05)' },
    tooltip: { theme: 'dark' }
}));

const densitySeries = computed(() => [{ name: 'Judgments', data: Object.values(precedentDensity.value) }]);

// 6. Top Cited Authorities Leaderboard
const topCitedAuthorities = computed(() => {
    const freq = {};
    filteredCases.value.forEach(c => {
        c.precedents_cited.forEach(p => {
            const cit = p.case_name_citation ? p.case_name_citation.trim() : null;
            if (cit) {
                if (!freq[cit]) {
                    freq[cit] = {
                        citation: cit,
                        count: 0,
                        treatment: p.treatment || 'Referred',
                        url: p.url || null
                    };
                }
                freq[cit].count++;
            }
        });
    });
    return Object.values(freq).sort((a, b) => b.count - a.count).slice(0, 12);
});

// 7. Bench Composition & Panel Sizes Donut
const panelSizes = computed(() => {
    const panels = {
        'Single Judge': 0,
        'Bench (2–3 Judges)': 0,
        'Full Bench (4+ Judges)': 0,
    };
    filteredCases.value.forEach(c => {
        const jCount = c.judges.length;
        if (jCount <= 1) panels['Single Judge']++;
        else if (jCount <= 3) panels['Bench (2–3 Judges)']++;
        else panels['Full Bench (4+ Judges)']++;
    });
    return panels;
});

const panelChartOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent' },
    colors: ['#8dd7da', '#ff8800', '#a855f7'],
    labels: Object.keys(panelSizes.value),
    stroke: { show: false },
    legend: { show: true, position: 'bottom', labels: { colors: '#a0a0b0' } },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' }
}));

const panelSeries = computed(() => Object.values(panelSizes.value));

// 8. Active Presiding Judges & Justices Leaderboard
const topJudges = computed(() => {
    const judgeMap = {};
    filteredCases.value.forEach(c => {
        c.judges.forEach(j => {
            if (!judgeMap[j]) {
                judgeMap[j] = { name: j, count: 0, precedentSum: 0 };
            }
            judgeMap[j].count++;
            judgeMap[j].precedentSum += c.precedents_count;
        });
    });
    return Object.values(judgeMap)
        .map(j => ({
            name: j.name,
            count: j.count,
            avgPrecedents: (j.precedentSum / j.count).toFixed(1)
        }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 10);
});

const quickFilterJudge = (judgeName) => {
    filterJudge.value = judgeName;
    activeTab.value = 'overview';
};
</script>

<template>
    <div>
        <Head title="8OHM | Legal Analytics & Jurisprudence Intelligence">
            <link rel="canonical" href="https://8ohm.co.za/demo" />
        </Head>

        <DemoLayout>
            <div class="space-y-8 animate-in fade-in duration-700">

                <!-- Tabs Navigation -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
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

                <!-- Global Interactive Filter Panel -->
                <div class="bg-zinc-900/30 backdrop-blur-md border border-white/5 p-6 rounded-[2rem] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <SlidersHorizontal class="w-4 h-4 text-admin-modern" />
                            <h4 class="text-xs font-black uppercase tracking-widest text-zinc-300">Jurisprudence Intelligence Filters</h4>
                        </div>
                        <button @click="resetFilters"
                            class="text-[9px] font-black text-admin-modern/70 hover:text-admin-modern uppercase tracking-widest flex items-center gap-1.5 transition-colors">
                            <RefreshCw class="w-3 h-3" />
                            Reset Filters
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Search Query Input -->
                        <div class="flex flex-col space-y-1.5 lg:col-span-2">
                            <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Search Principles & Parties</label>
                            <div class="relative">
                                <Search class="w-3.5 h-3.5 text-zinc-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input v-model="searchQuery" type="text"
                                    placeholder="Search ratio decidendi, judges, case number, parties..."
                                    class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl pl-9 pr-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all placeholder-zinc-600" />
                            </div>
                        </div>

                        <!-- Court Filter -->
                        <div class="flex flex-col space-y-1.5">
                            <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Court / Authority</label>
                            <select v-model="filterCourt"
                                class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                                <option value="All">All Superior Courts</option>
                                <option v-for="c in courtsList.filter(x => x !== 'All')" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>

                        <!-- Judge Filter -->
                        <div class="flex flex-col space-y-1.5">
                            <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Presiding Judge</label>
                            <select v-model="filterJudge"
                                class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                                <option value="All">All Judges & Justices</option>
                                <option v-for="j in judgesList.filter(x => x !== 'All')" :key="j" :value="j">{{ j }}</option>
                            </select>
                        </div>

                        <!-- Year Filter -->
                        <div class="flex flex-col space-y-1.5">
                            <label class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Decision Year</label>
                            <select v-model="filterYear"
                                class="w-full bg-zinc-950/80 border border-white/5 text-zinc-300 text-xs rounded-xl px-3.5 py-2.5 outline-none focus:border-admin-modern/30 transition-all">
                                <option value="All">All Decision Years</option>
                                <option v-for="y in yearsList.filter(x => x !== 'All')" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- High-Level KPI Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Judgments -->
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-admin-modern transition-colors">
                                <Scale class="w-5 h-5" />
                            </div>
                            <span class="text-[9px] font-black text-admin-modern bg-admin-modern/10 px-2 py-1 rounded-lg">COURTS</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Judgments Audited</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ totalCasesCount }} Cases</p>
                    </div>

                    <!-- Reportable Rate -->
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-emerald-400 transition-colors">
                                <Award class="w-5 h-5" />
                            </div>
                            <span class="text-[9px] font-black text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-lg">LANDMARK</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Reportable Precedents</p>
                        <p class="text-3xl font-black text-emerald-400 mt-1 tracking-tighter">{{ reportablePercentage }}% <span class="text-xs text-zinc-400 font-bold">({{ reportableCount }} Decisions)</span></p>
                    </div>

                    <!-- Precedents Network -->
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-purple-400 transition-colors">
                                <BookOpen class="w-5 h-5" />
                            </div>
                            <span class="text-[9px] font-black text-purple-400 bg-purple-400/10 px-2 py-1 rounded-lg">NETWORK</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Precedent Citations</p>
                        <p class="text-3xl font-black text-purple-400 mt-1 tracking-tighter">{{ totalPrecedentsCount.toLocaleString() }} <span class="text-xs text-zinc-400 font-bold">({{ avgPrecedentsPerCase }} avg/case)</span></p>
                    </div>

                    <!-- Adjudication Speed -->
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[2rem] group hover:bg-zinc-900/60 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-rose-400 transition-colors">
                                <Clock class="w-5 h-5" />
                            </div>
                            <span class="text-[9px] font-black text-rose-400 bg-rose-400/10 px-2 py-1 rounded-lg">VELOCITY</span>
                        </div>
                        <p class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Avg Hearing to Decision</p>
                        <p class="text-3xl font-black text-white mt-1 tracking-tighter">{{ avgAdjudicationSpeed }} Days</p>
                    </div>
                </div>

                <!-- Empty State Warning -->
                <div v-if="filteredCases.length === 0"
                    class="bg-zinc-900/30 backdrop-blur-md border border-white/5 p-16 rounded-[3rem] text-center flex flex-col items-center justify-center space-y-4">
                    <AlertCircle class="w-12 h-12 text-rose-400" />
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-tight">No Judgments Match Active Filters</h3>
                        <p class="text-zinc-500 text-xs mt-1">Try clearing your search query or broadening the selected court/judge filters.</p>
                    </div>
                    <button @click="resetFilters" class="px-6 py-2.5 bg-admin-modern text-black font-black uppercase text-xs rounded-xl hover:bg-admin-modern/90 transition-all">
                        Reset Filters
                    </button>
                </div>

                <!-- TAB 1: JURISPRUDENCE OVERVIEW -->
                <div v-else-if="activeTab === 'overview'" class="space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Timeline Volume & Speed Area Chart -->
                        <div class="lg:col-span-2 bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-black text-white uppercase tracking-tighter">Jurisprudence Timeline & Velocity</h3>
                                    <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Annual decision volume & average days from hearing to judgment</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest">Active Benches</span>
                                    <p class="text-md font-black text-admin-modern tracking-tighter">{{ courtsBreakdown.length }} Courts</p>
                                </div>
                            </div>
                            <div class="flex-1 min-h-[320px]">
                                <VueApexCharts width="100%" height="320" :options="timelineChartOptions" :series="timelineSeries" />
                            </div>
                        </div>

                        <!-- Subject Matter Typology (Donut) -->
                        <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] flex flex-col justify-between">
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tighter">Legal Subject Typology</h3>
                                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Distribution of primary legal principles & subject matters</p>
                            </div>

                            <div class="flex-1 flex flex-col items-center justify-center my-4">
                                <VueApexCharts width="230" :options="typologyChartOptions" :series="typologyData.series" />
                            </div>

                            <div class="grid grid-cols-2 gap-2 border-t border-white/5 pt-4 max-h-[140px] overflow-y-auto custom-scrollbar">
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

                    <!-- Court Authorities Breakdown Row -->
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tighter">Court Authority & Jurisdiction Distribution</h3>
                                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Decisions classified by Superior Court authority</p>
                            </div>
                            <span class="text-xs text-zinc-500 font-bold uppercase">{{ courtsBreakdown.length }} Jurisdictions</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="c in courtsBreakdown" :key="c.court"
                                class="p-5 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-admin-modern/30 transition-all flex items-center justify-between">
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-admin-modern">Court Authority</span>
                                    <h4 class="text-xs font-bold text-white line-clamp-1">{{ c.court }}</h4>
                                    <p class="text-[10px] text-zinc-400">{{ c.count }} Judgments Published</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-black text-white">{{ c.percentage }}%</span>
                                    <p class="text-[8px] text-zinc-500 font-bold uppercase tracking-widest">Share</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Stream of Recent Cases -->
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tighter">Recent Jurisprudence Stream</h3>
                                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Latest decisions with extracted legal principles & ratio decidendi</p>
                            </div>
                            <button @click="activeTab = 'cases'" class="text-xs font-black text-admin-modern hover:underline uppercase tracking-wider">
                                View Full Repository &rarr;
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div v-for="c in recentCasesStream" :key="c.id"
                                class="p-4 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-admin-modern/30 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4 group">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-zinc-800/80 border border-white/5 flex items-center justify-center text-admin-modern shrink-0 mt-0.5 group-hover:scale-105 transition-transform">
                                        <Gavel class="w-4 h-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-black text-white truncate max-w-[650px]">{{ c.title }}</span>
                                            <span v-if="c.reportable" class="px-2 py-0.5 rounded text-[8px] font-black bg-emerald-400/10 text-emerald-400 border border-emerald-400/20">
                                                REPORTABLE
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-zinc-400 mt-1 font-medium line-clamp-1">
                                            <strong class="text-zinc-300">{{ c.court }}</strong> &bull; Case: {{ c.case_number }} &bull; {{ c.applicant }} v {{ c.respondent }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 shrink-0">
                                    <div class="text-right">
                                        <p class="text-xs font-bold text-white">{{ c.judgment_date || c.document_date }}</p>
                                        <p class="text-[9px] text-zinc-500 uppercase tracking-widest font-bold">Decision Date</p>
                                    </div>
                                    <button @click="selectedCaseDetail = c"
                                        class="px-3 py-1.5 bg-admin-modern/10 hover:bg-admin-modern hover:text-black text-admin-modern border border-admin-modern/20 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1">
                                        <Eye class="w-3 h-3" />
                                        Inspect
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: PRECEDENTS & CITATIONS NETWORK -->
                <div v-else-if="activeTab === 'precedents'" class="space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Treatment Breakdown Donut -->
                        <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] space-y-4">
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tighter">Citation Treatments</h3>
                                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">How precedents were applied, referred to, or distinguished</p>
                            </div>
                            <div class="py-4">
                                <VueApexCharts type="donut" height="280" :options="treatmentsChartOptions" :series="treatmentsSeries" />
                            </div>
                        </div>

                        <!-- Citation Intensity Distribution Bar Chart -->
                        <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] lg:col-span-2 space-y-4">
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tighter">Precedent Citation Density</h3>
                                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Distribution of decisions by volume of authorities cited</p>
                            </div>
                            <div class="pt-2">
                                <VueApexCharts type="bar" height="280" :options="densityChartOptions" :series="densitySeries" />
                            </div>
                        </div>
                    </div>

                    <!-- Top Cited Authorities Leaderboard -->
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tighter">Most Cited Landmark Authorities & Acts</h3>
                                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Key legal precedents frequently cited across appellate jurisprudence</p>
                            </div>
                            <span class="text-xs text-zinc-500 font-bold uppercase">{{ topCitedAuthorities.length }} References</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="(p, idx) in topCitedAuthorities" :key="p.citation"
                                class="bg-zinc-950/60 border border-white/5 p-5 rounded-2xl space-y-3 hover:border-admin-modern/30 transition-all group">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-6 h-6 rounded-lg bg-white/5 flex items-center justify-center text-[10px] font-mono font-bold text-zinc-400 group-hover:text-admin-modern shrink-0">
                                            {{ idx + 1 }}
                                        </span>
                                        <span class="font-bold text-xs text-white group-hover:text-admin-modern transition-colors line-clamp-1">
                                            {{ p.citation }}
                                        </span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-black bg-admin-modern/10 text-admin-modern border border-admin-modern/20 shrink-0">
                                        {{ p.count }} citations
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-zinc-500 pt-2 border-t border-white/5">
                                    <span>Primary: <strong class="text-zinc-300">{{ p.treatment }}</strong></span>
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
                <div v-else-if="activeTab === 'bench'" class="space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Panel Sizes Donut -->
                        <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] space-y-4">
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tighter">Bench Composition</h3>
                                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Single-judge versus multi-judge appellate bench panels</p>
                            </div>
                            <div class="py-4">
                                <VueApexCharts type="donut" height="280" :options="panelChartOptions" :series="panelSeries" />
                            </div>
                        </div>

                        <!-- Active Presiding Judges Leaderboard -->
                        <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] lg:col-span-2 space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-black text-white uppercase tracking-tighter">Active Presiding Judges & Justices</h3>
                                    <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Judges ranked by decisions authored and citations referenced</p>
                                </div>
                                <span class="text-xs text-zinc-500 font-bold uppercase">{{ topJudges.length }} Key Judges</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="j in topJudges" :key="j.name"
                                    class="bg-zinc-950/60 border border-white/5 p-4 rounded-2xl flex items-center justify-between hover:border-admin-modern/30 transition-all">
                                    <div class="min-w-0 pr-3">
                                        <h5 class="text-xs font-bold text-white truncate">{{ j.name }}</h5>
                                        <p class="text-[10px] text-zinc-400 mt-0.5">Avg {{ j.avgPrecedents }} citations cited</p>
                                    </div>
                                    <button @click="quickFilterJudge(j.name)"
                                        class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-admin-modern/10 text-admin-modern border border-admin-modern/20 hover:bg-admin-modern hover:text-black transition-all shrink-0">
                                        {{ j.count }} Cases
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: CASE INTELLIGENCE & RATIO DECIDENDI EXPLORER -->
                <div v-else-if="activeTab === 'cases'" class="space-y-8">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 rounded-[3rem] space-y-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tighter">Case Intelligence & Ratio Decidendi Repository</h3>
                                <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Browse extracted ratio decidendi, obiter dicta, and court orders</p>
                            </div>
                            <span class="text-xs text-zinc-400 font-bold uppercase">{{ filteredCases.length }} Judgments Available</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="c in filteredCases" :key="c.id"
                                class="p-6 rounded-3xl bg-white/[0.02] border border-white/5 hover:border-admin-modern/40 transition-all flex flex-col justify-between space-y-4 group">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="px-2.5 py-0.5 rounded-lg text-[9px] font-black bg-admin-modern/10 text-admin-modern border border-admin-modern/20">
                                            {{ c.court }}
                                        </span>
                                        <span v-if="c.reportable" class="px-2 py-0.5 rounded text-[8px] font-black bg-emerald-400/10 text-emerald-400 border border-emerald-400/20">
                                            REPORTABLE
                                        </span>
                                    </div>

                                    <h4 class="text-sm font-bold text-white line-clamp-2 group-hover:text-admin-modern transition-colors">
                                        {{ c.title }}
                                    </h4>

                                    <div class="text-[10px] text-zinc-400 space-y-1">
                                        <p><strong class="text-zinc-300">Case No:</strong> {{ c.case_number }}</p>
                                        <p><strong class="text-zinc-300">Parties:</strong> {{ c.applicant }} v {{ c.respondent }}</p>
                                        <p v-if="c.judges && c.judges.length > 0"><strong class="text-zinc-300">Judges:</strong> {{ c.judges.join(', ') }}</p>
                                    </div>

                                    <div v-if="c.ratio_decidendi" class="bg-zinc-950/60 p-3.5 rounded-2xl border border-white/5 space-y-1">
                                        <span class="text-[8px] font-black text-admin-modern uppercase tracking-widest flex items-center gap-1">
                                            <Sparkles class="w-2.5 h-2.5" /> Ratio Decidendi
                                        </span>
                                        <p class="text-[11px] text-zinc-300 line-clamp-3 leading-relaxed">
                                            {{ c.ratio_decidendi }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-white/5">
                                    <span class="text-[10px] text-zinc-500 font-bold">{{ c.judgment_date || c.document_date }}</span>
                                    <button @click="selectedCaseDetail = c"
                                        class="px-4 py-2 rounded-xl bg-admin-modern/10 hover:bg-admin-modern hover:text-black text-admin-modern font-black text-[10px] uppercase tracking-wider transition-all flex items-center gap-1.5">
                                        <Eye class="w-3.5 h-3.5" />
                                        Inspect Full Analysis
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Case Intelligence Detail Modal -->
            <div v-if="selectedCaseDetail"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md transition-all duration-300">
                <div class="bg-zinc-950 border border-white/10 rounded-[2.5rem] w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden shadow-2xl animate-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="p-6 border-b border-white/5 bg-zinc-900/60 flex items-start justify-between gap-4">
                        <div class="space-y-1.5 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-lg text-[9px] font-black bg-admin-modern/10 text-admin-modern border border-admin-modern/20">
                                    {{ selectedCaseDetail.court }}
                                </span>
                                <span v-if="selectedCaseDetail.reportable" class="px-2 py-0.5 rounded text-[8px] font-black bg-emerald-400/10 text-emerald-400 border border-emerald-400/20">
                                    REPORTABLE PRECEDENT
                                </span>
                                <span class="text-[10px] text-zinc-500 font-bold">Case: {{ selectedCaseDetail.case_number }}</span>
                            </div>
                            <h3 class="text-base font-black text-white uppercase tracking-tight">{{ selectedCaseDetail.title }}</h3>
                        </div>
                        <button @click="selectedCaseDetail = null" class="text-zinc-500 hover:text-white p-2 rounded-xl hover:bg-white/5 transition-colors shrink-0">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 custom-scrollbar text-xs text-zinc-300">
                        <!-- Key Metadata Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-zinc-900/30 p-4 rounded-2xl border border-white/5">
                            <div>
                                <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Decision Date</span>
                                <span class="font-bold text-white mt-0.5 block">{{ selectedCaseDetail.judgment_date || selectedCaseDetail.document_date || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Hearing Date</span>
                                <span class="font-bold text-white mt-0.5 block">{{ selectedCaseDetail.hearing_date || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Adjudication Duration</span>
                                <span class="font-bold text-admin-modern mt-0.5 block">{{ selectedCaseDetail.durationDays !== null ? selectedCaseDetail.durationDays + ' Days' : 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Location</span>
                                <span class="font-bold text-white mt-0.5 block">{{ selectedCaseDetail.court_location }}</span>
                            </div>
                        </div>

                        <!-- Judges & Litigants -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-white/[0.02] border border-white/5 p-4 rounded-2xl space-y-1">
                                <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Presiding Bench</span>
                                <p class="text-white font-bold">{{ selectedCaseDetail.judges && selectedCaseDetail.judges.length > 0 ? selectedCaseDetail.judges.join(', ') : 'Bench details on record' }}</p>
                            </div>
                            <div class="bg-white/[0.02] border border-white/5 p-4 rounded-2xl space-y-1">
                                <span class="text-[9px] font-black text-zinc-500 uppercase tracking-widest block">Litigants / Parties</span>
                                <p class="text-white font-bold">{{ selectedCaseDetail.applicant }} <span class="text-zinc-500">v</span> {{ selectedCaseDetail.respondent }}</p>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="space-y-2">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Executive Summary</h5>
                            <p class="bg-zinc-900/40 p-4 rounded-2xl border border-white/5 leading-relaxed text-zinc-300">
                                {{ selectedCaseDetail.summary }}
                            </p>
                        </div>

                        <!-- Ratio Decidendi -->
                        <div v-if="selectedCaseDetail.ratio_decidendi" class="space-y-2">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-admin-modern flex items-center gap-1.5">
                                <Sparkles class="w-3.5 h-3.5" /> Ratio Decidendi (Binding Principle)
                            </h5>
                            <div class="bg-admin-modern/5 border border-admin-modern/20 p-5 rounded-2xl leading-relaxed text-white font-medium">
                                {{ selectedCaseDetail.ratio_decidendi }}
                            </div>
                        </div>

                        <!-- Obiter Dicta -->
                        <div v-if="selectedCaseDetail.obiter_dicta" class="space-y-2">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-purple-400">Obiter Dicta (Judicial Remarks)</h5>
                            <p class="bg-purple-500/5 border border-purple-500/20 p-4 rounded-2xl leading-relaxed text-zinc-300">
                                {{ selectedCaseDetail.obiter_dicta }}
                            </p>
                        </div>

                        <!-- Order -->
                        <div v-if="selectedCaseDetail.order" class="space-y-2">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Court Order & Disposition</h5>
                            <div class="bg-emerald-500/5 border border-emerald-500/20 p-4 rounded-2xl leading-relaxed text-zinc-300 whitespace-pre-line font-mono text-[11px]">
                                {{ selectedCaseDetail.order }}
                            </div>
                        </div>

                        <!-- Precedents Cited -->
                        <div v-if="selectedCaseDetail.precedents_cited && selectedCaseDetail.precedents_cited.length > 0" class="space-y-2">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Precedents & Statutory Authorities Cited ({{ selectedCaseDetail.precedents_cited.length }})</h5>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div v-for="p in selectedCaseDetail.precedents_cited" :key="p.case_name_citation"
                                    class="p-3 rounded-xl bg-zinc-900/60 border border-white/5 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-bold text-white truncate">{{ p.case_name_citation }}</p>
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
                    <div class="p-6 border-t border-white/5 bg-zinc-900/60 flex items-center justify-between">
                        <a v-if="selectedCaseDetail.source_url" :href="selectedCaseDetail.source_url" target="_blank" rel="noopener noreferrer"
                            class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 text-xs font-bold flex items-center gap-1.5 transition-all">
                            View Official SAFLII Record <ExternalLink class="w-3.5 h-3.5" />
                        </a>
                        <button @click="selectedCaseDetail = null"
                            class="px-6 py-2 rounded-xl bg-admin-modern text-black font-black text-xs uppercase tracking-wider hover:bg-admin-modern/90 transition-all ml-auto">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Metrics Documentation Modal -->
            <div v-if="isMetricsModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all duration-300">
                <div class="bg-zinc-950 border border-white/10 rounded-[2rem] w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden shadow-2xl">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-white/5 bg-zinc-900/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-admin-modern/10 flex items-center justify-center text-admin-modern">
                                <Code class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-black text-white uppercase tracking-tighter">Jurisprudence Intelligence Logic</h2>
                                <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-0.5">Calculations, Citations Graph & Entity Extraction Rules</p>
                            </div>
                        </div>
                        <button @click="isMetricsModalOpen = false" class="text-zinc-500 hover:text-white transition-colors p-2 rounded-xl hover:bg-white/5">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="flex-1 overflow-y-auto p-8 custom-scrollbar space-y-8 text-xs text-zinc-400 leading-relaxed">
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
                        <button @click="isMetricsModalOpen = false" class="px-5 py-2 bg-admin-modern text-black font-black uppercase text-xs rounded-xl hover:bg-admin-modern/90 transition-all">
                            Close
                        </button>
                    </div>
                </div>
            </div>

        </DemoLayout>
    </div>
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
