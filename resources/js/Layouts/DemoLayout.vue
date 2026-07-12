<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import LoadingScreen from '@/Components/LoadingScreen.vue';
import Toast from '@/Components/Toast.vue';
import demoData from '@/Pages/Demo/Analytics/demo_data.json';
import {
    LayoutDashboard,
    Home,
    Package,
    ShoppingCart,
    Users,
    BarChart3,
    Boxes,
    Settings,
    Menu,
    X,
    Search,
    Bell,
    Plus,
    LogOut,
    UserCircle,
    Layers,
    Clock,
    TrendingUp,
    Building,
    Database,
    Briefcase,
    Calendar
} from 'lucide-vue-next';

const page = usePage();

// Calculate dataset breakdown from cases prop or fallback to demoData
const datasetStats = computed(() => {
    const rawCases = page.props.cases && page.props.cases.length > 0
        ? page.props.cases
        : demoData;

    if (!rawCases || rawCases.length === 0) {
        return {
            total: 0,
            employers: 0,
            dateRange: 'N/A'
        };
    }

    const total = rawCases.length;

    // Unique employers count
    const employersSet = new Set();
    rawCases.forEach(c => {
        if (c.employer) {
            employersSet.add(c.employer.trim().toLowerCase());
        }
    });
    const employers = employersSet.size;

    // Get min & max years from award_date or hearing dates
    let minYear = null;
    let maxYear = null;
    rawCases.forEach(c => {
        const dateStr = c.award_date || c.hearing_start;
        if (dateStr) {
            const year = new Date(dateStr.split('T')[0]).getFullYear();
            if (!isNaN(year)) {
                if (minYear === null || year < minYear) minYear = year;
                if (maxYear === null || year > maxYear) maxYear = year;
            }
        }
    });

    let dateRange = 'Unknown';
    if (minYear !== null && maxYear !== null) {
        dateRange = minYear === maxYear ? `${minYear}` : `${minYear} - ${maxYear}`;
    }

    return {
        total,
        employers,
        dateRange
    };
});

const orb1 = ref(null);
const orb2 = ref(null);
const orb3 = ref(null);

const handleMouseMove = (e) => {
    const x = e.clientX / window.innerWidth;
    const y = e.clientY / window.innerHeight;

    const list = [orb1.value, orb2.value, orb3.value];
    list.forEach((orb, index) => {
        if (!orb) return;
        const speed = (index + 1) * 20;
        const dx = (x - 0.5) * speed;
        const dy = (y - 0.5) * speed;
        orb.style.transform = `translate(${dx}px, ${dy}px)`;
    });
};

onMounted(() => {
    window.addEventListener('mousemove', handleMouseMove);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
});
</script>

<template>
    <div class="min-h-screen bg-admin-main text-white font-sans selection:bg-admin-modern selection:text-black relative">
        <!-- Background Visuals -->
        <div class="background-visuals">
            <div class="glow-orb orb-1" ref="orb1"></div>
            <div class="glow-orb orb-2" ref="orb2"></div>
            <div class="glow-orb orb-3" ref="orb3"></div>
            <div class="grid-overlay"></div>
            <div class="noise-overlay"></div>
        </div>
        <LoadingScreen />
        <Toast />

        <!-- Main Content Area -->
        <div class="flex flex-col min-h-screen">
            <!-- Topbar -->
            <header
                class="min-h-20 lg:min-h-24 py-4 px-4 sm:px-8 lg:px-10 sticky top-0 bg-admin-main/80 backdrop-blur-xl z-30 border-b border-white/5 gap-6 xl:gap-8 dashboard-header">

                <!-- Logo & User Profile -->
                <div class="flex items-center gap-3 sm:gap-6 shrink-0 dashboard-header-logo">
                    <Link href="/" class="shrink-0">
                        <img src="/assets/images/8OHM_Logo.webp" alt="Logo"
                            class="h-8 w-auto object-contain transition-transform hover:scale-105" />
                    </Link>

                    <div class="h-8 w-px bg-white/10"></div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-tighter uppercase">
                            <span class="text-admin-modern">Analytics - Legal Dataset</span>
                        </h1>
                        <p class="text-zinc-500 mt-1 sm:mt-2 font-bold uppercase tracking-widest text-[8px] sm:text-[10px]">
                            South African CCMA Arbitration & Dispute Intelligence
                        </p>
                    </div>
                </div>

                <!-- Dataset Breakdown -->
                <div
                    class="flex items-center justify-between sm:justify-start gap-4 sm:gap-6 bg-white/[0.02] border border-white/5 rounded-2xl p-3 sm:px-5 sm:py-2.5 backdrop-blur-md shadow-inner shadow-white/[0.01] w-full xl:w-auto shrink-0 dashboard-header-stats">
                    <!-- Total Records -->
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-admin-modern/10 flex items-center justify-center text-admin-modern shrink-0">
                            <Database class="w-4 h-4" />
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-zinc-500 uppercase tracking-widest text-[8px] sm:text-[9px] font-bold leading-none">Total
                                Records</span>
                            <span class="text-white font-extrabold text-xs sm:text-sm mt-1 leading-none">{{
                                datasetStats.total.toLocaleString() }}</span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="h-8 w-px bg-white/10 shrink-0"></div>

                    <!-- Unique Employers -->
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-admin-modern/10 flex items-center justify-center text-admin-modern shrink-0">
                            <Briefcase class="w-4 h-4" />
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-zinc-500 uppercase tracking-widest text-[8px] sm:text-[9px] font-bold leading-none">Employers</span>
                            <span class="text-white font-extrabold text-xs sm:text-sm mt-1 leading-none">{{
                                datasetStats.employers.toLocaleString() }}</span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="h-8 w-px bg-white/10 shrink-0"></div>

                    <!-- Date Range -->
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-admin-modern/10 flex items-center justify-center text-admin-modern shrink-0">
                            <Calendar class="w-4 h-4" />
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-zinc-500 uppercase tracking-widest text-[8px] sm:text-[9px] font-bold leading-none">Date
                                Range</span>
                            <span class="text-white font-extrabold text-xs sm:text-sm mt-1 leading-none">{{
                                datasetStats.dateRange }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 dashboard-header-user">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-admin-modern to-emerald-500 flex items-center justify-center font-bold text-black shadow-lg shadow-admin-modern/10">
                        D
                    </div>
                    <div class="min-w-0 flex flex-col justify-center">
                        <p class="text-xs font-black uppercase tracking-widest text-white truncate leading-none">Demo
                            User
                        </p>
                        <p class="text-[9px] font-bold text-zinc-500 truncate uppercase leading-none mt-1">Guest</p>
                    </div>

                    <Link href="/">
                        <LogOut class="w-4 h-4 text-zinc-500 hover:text-rose-500 transition-colors ml-2" />
                    </Link>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-8 lg:p-10 max-w-[1920px] mx-auto w-full">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.dashboard-header {
    display: grid;
    grid-template-areas:
        "logo user"
        "stats stats";
    grid-template-columns: 1fr auto;
    align-items: center;
}

.dashboard-header-logo {
    grid-area: logo;
}

.dashboard-header-stats {
    grid-area: stats;
}

.dashboard-header-user {
    grid-area: user;
}

@media (min-width: 1280px) {
    .dashboard-header {
        grid-template-areas: "logo stats user";
        grid-template-columns: auto auto auto;
        justify-content: space-between;
    }
}

.bg-admin-main {
    background: #09090B;
}

.bg-admin-sidebar {
    background: #0F0F10;
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
