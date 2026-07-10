<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import LoadingScreen from '@/Components/LoadingScreen.vue';
import Toast from '@/Components/Toast.vue';
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
    UserCircle
} from 'lucide-vue-next';

const page = usePage();

const isSidebarOpen = ref(false);
const searchQuery = ref('');

const searchItems = [
    { name: 'Analytics', href: route('demo'), keywords: ['charts', 'revenue', 'performance', 'reports', 'trend'] },
];

const handleSearch = () => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) return;

    const match = searchItems.find(item =>
        item.name.toLowerCase().includes(query) ||
        item.keywords.some(k => k.includes(query))
    );

    if (match) {
        router.visit(match.href);
        searchQuery.value = '';
    }
};

const navigation = [
    { name: 'Analytics', href: route('demo'), icon: BarChart3 },
];

const isUrl = (url) => {
    const currentUrl = page.url.split('?')[0].substr(1);
    const targetUrl = url.replace(page.props.app_url + '/', '').replace(page.props.app_url, '');
    return currentUrl === targetUrl || currentUrl.startsWith(targetUrl + '/');
};

// Close sidebar on route change (mobile)
router.on('finish', () => {
    isSidebarOpen.value = false;
});

// Close sidebar on resize if window > md
const handleResize = () => {
    if (window.innerWidth >= 1024) {
        isSidebarOpen.value = false;
    }
};

onMounted(() => window.addEventListener('resize', handleResize));
onUnmounted(() => window.removeEventListener('resize', handleResize));
</script>

<template>
    <div class="min-h-screen bg-admin-main text-white font-sans selection:bg-admin-modern selection:text-black">
        <LoadingScreen />
        <Toast />

        <!-- Mobile Sidebar Overlay -->
        <div v-if="isSidebarOpen"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden transition-opacity duration-300"
            @click="isSidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-50 w-72 bg-admin-sidebar border-r border-white/5 transform transition-transform duration-300 ease-in-out lg:translate-x-0',
            isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]">
            <div class="flex flex-col h-full">
                <!-- Sidebar Header -->
                <div class="h-24 flex items-center px-8 border-b border-white/5">
                    <Link href="/" class="flex items-center gap-3 group">
                        <img src="/assets/images/8OHM_Logo.webp" alt="Store"
                            class="h-8 transition-transform group-hover:scale-105" />
                    </Link>
                    <button @click="isSidebarOpen = false" class="ml-auto p-2 text-zinc-500 hover:text-white lg:hidden">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 custom-scrollbar">
                    <div v-for="item in navigation" :key="item.name">
                        <Link :href="item.href"
                            class="group flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200"
                            :class="[
                                isUrl(item.href)
                                    ? 'bg-white/10 text-white shadow-sm ring-1 ring-white/10'
                                    : 'text-zinc-500 hover:text-zinc-200 hover:bg-white/5'
                            ]">
                            <component :is="item.icon" class="mr-3.5 h-5 w-5 transition-colors duration-200"
                                :class="[isUrl(item.href) ? 'text-admin-modern' : 'text-zinc-500 group-hover:text-zinc-300']" />
                            {{ item.name }}
                        </Link>
                    </div>
                </nav>

                <!-- User Profile -->
                <div class="p-4 border-t border-white/5">
                    <Link href="/">
                        <div
                            class="bg-zinc-900/40 rounded-2xl p-3 flex items-center gap-3 border border-white/5 hover:bg-zinc-900/60 transition-colors group cursor-pointer">
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-admin-modern to-emerald-500 flex items-center justify-center font-bold text-black shadow-lg shadow-admin-modern/10">
                                D
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-black uppercase tracking-widest text-white truncate">Demo User
                                </p>
                                <p class="text-[10px] font-bold text-zinc-500 truncate uppercase">Guest</p>
                            </div>

                            <LogOut class="w-4 h-4 text-zinc-600 group-hover:text-rose-500 transition-colors" />

                        </div>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="lg:ml-72 flex flex-col min-h-screen">
            <!-- Topbar -->
            <header
                class="h-20 lg:h-24 flex items-center justify-between px-4 sm:px-8 lg:px-10 sticky top-0 bg-admin-main/80 backdrop-blur-xl z-30 border-b border-white/5">
                <div class="flex items-center gap-4 lg:hidden">
                    <button @click="isSidebarOpen = true" class="p-2 text-zinc-400 hover:text-white transition-colors">
                        <Menu class="w-6 h-6" />
                    </button>
                    <img src="/assets/images/8OHM_Logo.webp" alt="Logo" class="h-6" />
                </div>

                <div class="hidden md:flex flex-1 max-w-xl mx-4 lg:mx-0">
                    <div class="relative w-full group">
                        <div
                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-500 group-focus-within:text-admin-modern transition-colors">
                            <Search class="h-4 w-4" />
                        </div>
                        <input v-model="searchQuery" @keyup.enter="handleSearch" type="text"
                            placeholder="Type 'Inventory' or 'Live Stream'..."
                            class="block w-full pl-11 pr-4 py-2.5 bg-zinc-900/50 border border-white/5 text-sm text-white placeholder:text-zinc-600 rounded-xl focus:ring-1 focus:ring-admin-modern/50 focus:border-admin-modern/50 transition-all outline-none" />
                    </div>
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
</style>
