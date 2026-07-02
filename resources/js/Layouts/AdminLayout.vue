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
const user = page.props.auth.user;
const notifications = computed(() => page.props.auth.notifications || []);
const unreadCount = computed(() => page.props.auth.unread_notifications_count || 0);

const markAsRead = (id) => {
    router.post(route('notifications.read', id), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const markAllAsRead = () => {
    router.post(route('notifications.read-all'), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const isSidebarOpen = ref(false);
const searchQuery = ref('');

const searchItems = [
    { name: 'Dashboard', href: route('dashboard'), keywords: ['home', 'overview', 'main', 'stats'] },
    { name: 'Inventory', href: route('admin.inventory.index'), keywords: ['stock', 'items', 'warehouse', 'products'] },
    { name: 'Products', href: route('admin.products.index'), keywords: ['catalog', 'drops', 'sneakers'] },
    { name: 'Orders', href: route('admin.orders.index'), keywords: ['sales', 'history', 'shipments', 'cash'] },
    { name: 'Customers', href: route('admin.customers.index'), keywords: ['users', 'clients', 'members'] },
    { name: 'Analytics', href: route('admin.analytics.index'), keywords: ['charts', 'revenue', 'performance', 'reports', 'trend'] },
    { name: 'Live Stream', href: route('admin.analytics.index'), keywords: ['live', 'active', 'visitors', 'real-time', 'realtime'] },
    { name: 'Settings', href: route('admin.settings.index'), keywords: ['config', 'profile', 'security', 'password', 'store'] },
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
    { name: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard },
    { name: 'Home Page', href: route('admin.home.edit'), icon: Home },
    { name: 'Products', href: route('admin.products.index'), icon: Package },
    { name: 'Orders', href: route('admin.orders.index'), icon: ShoppingCart },
    { name: 'Customers', href: route('admin.customers.index'), icon: Users },
    { name: 'Analytics', href: route('admin.analytics.index'), icon: BarChart3 },
    { name: 'Inventory', href: route('admin.inventory.index'), icon: Boxes },
    { name: 'Settings', href: route('admin.settings.index'), icon: Settings },
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
        <div 
            v-if="isSidebarOpen" 
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden transition-opacity duration-300"
            @click="isSidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside 
            :class="[
                'fixed inset-y-0 left-0 z-50 w-72 bg-admin-sidebar border-r border-white/5 transform transition-transform duration-300 ease-in-out lg:translate-x-0',
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <div class="flex flex-col h-full">
                <!-- Sidebar Header -->
                <div class="h-24 flex items-center px-8 border-b border-white/5">
                    <Link href="/" class="flex items-center gap-3 group">
                        <img src="/assets/images/logo-white.png" alt="Store" class="h-8 transition-transform group-hover:scale-105" />
                    </Link>
                    <button @click="isSidebarOpen = false" class="ml-auto p-2 text-zinc-500 hover:text-white lg:hidden">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 custom-scrollbar">
                    <div v-for="item in navigation" :key="item.name">
                        <Link
                            :href="item.href"
                            class="group flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200"
                            :class="[
                                isUrl(item.href)
                                    ? 'bg-white/10 text-white shadow-sm ring-1 ring-white/10'
                                    : 'text-zinc-500 hover:text-zinc-200 hover:bg-white/5'
                            ]"
                        >
                            <component 
                                :is="item.icon" 
                                class="mr-3.5 h-5 w-5 transition-colors duration-200"
                                :class="[isUrl(item.href) ? 'text-admin-modern' : 'text-zinc-500 group-hover:text-zinc-300']"
                            />
                            {{ item.name }}
                        </Link>
                    </div>
                </nav>

                <!-- User Profile -->
                <div class="p-4 border-t border-white/5">
                    <div class="bg-zinc-900/40 rounded-2xl p-3 flex items-center gap-3 border border-white/5 hover:bg-zinc-900/60 transition-colors group cursor-pointer">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-admin-modern to-emerald-500 flex items-center justify-center font-bold text-black shadow-lg shadow-admin-modern/10">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-black uppercase tracking-widest text-white truncate">{{ user.name }}</p>
                            <p class="text-[10px] font-bold text-zinc-500 truncate uppercase">{{ user.role }}</p>
                        </div>
                        <LogOut class="w-4 h-4 text-zinc-600 group-hover:text-rose-500 transition-colors" />
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="lg:ml-72 flex flex-col min-h-screen">
            <!-- Topbar -->
            <header class="h-20 lg:h-24 flex items-center justify-between px-4 sm:px-8 lg:px-10 sticky top-0 bg-admin-main/80 backdrop-blur-xl z-30 border-b border-white/5">
                <div class="flex items-center gap-4 lg:hidden">
                    <button @click="isSidebarOpen = true" class="p-2 text-zinc-400 hover:text-white transition-colors">
                        <Menu class="w-6 h-6" />
                    </button>
                    <img src="/assets/images/logo-white.png" alt="Logo" class="h-6" />
                </div>

                <div class="hidden md:flex flex-1 max-w-xl mx-4 lg:mx-0">
                    <div class="relative w-full group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-500 group-focus-within:text-admin-modern transition-colors">
                            <Search class="h-4 w-4" />
                        </div>
                        <input
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text"
                            placeholder="Type 'Inventory' or 'Live Stream'..."
                            class="block w-full pl-11 pr-4 py-2.5 bg-zinc-900/50 border border-white/5 text-sm text-white placeholder:text-zinc-600 rounded-xl focus:ring-1 focus:ring-admin-modern/50 focus:border-admin-modern/50 transition-all outline-none"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-3 sm:gap-5">
                    <!-- Action Buttons - Hidden on mobile, icons only on tablet -->
                    <div class="hidden sm:flex items-center gap-2">
                        <Dropdown align="right" width="96">
                            <template #trigger>
                                <button class="p-2.5 text-zinc-500 hover:text-white hover:bg-white/5 rounded-xl transition-all relative group border border-white/5">
                                    <Bell class="h-4.5 w-4.5 group-hover:rotate-12 transition-transform" />
                                    <span v-if="unreadCount > 0" class="absolute top-2.5 right-2.5 w-1.5 h-1.5 bg-admin-modern rounded-full ring-2 ring-admin-main shadow-[0_0_10px_rgba(229,255,69,0.5)]"></span>
                                </button>
                            </template>
                            <template #content>
                                <div class="bg-black/90 backdrop-blur-2xl border border-white/10 rounded-2xl overflow-hidden shadow-[0_50px_100px_-20px_rgba(0,0,0,0.5)]">
                                    <div class="p-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                                        <div>
                                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-white">Alert Center</h4>
                                            <p class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest mt-0.5">{{ unreadCount }} Unread Messages</p>
                                        </div>
                                        <button v-if="unreadCount > 0" @click="markAllAsRead" class="text-[9px] font-black text-admin-modern uppercase tracking-widest hover:text-white transition-colors">Mark Read</button>
                                    </div>

                                    <div class="max-h-[450px] overflow-y-auto custom-scrollbar">
                                        <div v-if="notifications.length === 0" class="py-20 text-center">
                                            <div class="w-12 h-12 bg-zinc-900 rounded-full flex items-center justify-center mx-auto mb-4 border border-white/5">
                                                <Bell class="w-5 h-5 text-zinc-700" />
                                            </div>
                                            <p class="text-[10px] font-black text-zinc-600 uppercase tracking-widest">System Clear</p>
                                        </div>

                                        <div 
                                            v-for="notification in notifications" 
                                            :key="notification.id" 
                                            @click="markAsRead(notification.id)"
                                            class="p-5 border-b border-white/5 hover:bg-white/[0.03] transition-all cursor-pointer group relative"
                                            :class="{'bg-white/[0.01]': !notification.read_at}"
                                        >
                                            <div v-if="!notification.read_at" class="absolute left-0 top-0 bottom-0 w-0.5 bg-admin-modern shadow-[0_0_10px_rgba(229,255,69,0.5)]"></div>
                                            
                                            <div class="flex gap-4">
                                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110"
                                                     :class="notification.data.type === 'order' ? 'bg-emerald-500/10 border border-emerald-500/20' : 'bg-admin-modern/10 border border-admin-modern/20'">
                                                    <ShoppingCart v-if="notification.data.type === 'order'" class="w-4.5 h-4.5 text-emerald-400" />
                                                    <Bell v-else class="w-4.5 h-4.5 text-admin-modern" />
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-[11px] font-black text-zinc-300 group-hover:text-white transition-colors leading-relaxed">
                                                        {{ notification.data.message }}
                                                    </p>
                                                    <div class="flex items-center gap-2 mt-1.5">
                                                        <span class="text-[9px] font-bold text-zinc-600 uppercase tracking-widest">{{ notification.created_at_human || 'Just now' }}</span>
                                                        <template v-if="notification.data.amount">
                                                            <span class="text-[8px] text-zinc-800">•</span>
                                                            <span class="text-[9px] font-black text-emerald-400">€{{ notification.data.amount }}</span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <Link :href="route('admin.orders.index')" class="block p-5 text-center text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 hover:text-admin-modern hover:bg-white/[0.02] transition-all border-t border-white/5">
                                        View System Logs
                                    </Link>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <Link
                        :href="route('admin.products.create')"
                        class="bg-white text-black h-10 sm:h-11 px-4 sm:px-6 rounded-xl font-bold text-xs uppercase tracking-widest flex items-center gap-2 hover:bg-zinc-200 transition-all shadow-xl active:scale-95"
                    >
                        <Plus class="h-4 w-4" />
                        <span class="hidden sm:inline">Add Product</span>
                    </Link>

                    <div class="h-8 w-px bg-white/10 mx-1"></div>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center group">
                                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-zinc-800 border border-white/10 overflow-hidden group-hover:border-admin-modern transition-all duration-300">
                                    <img v-if="user.profile_photo_url" :src="user.profile_photo_url" :alt="user.name" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-zinc-800 text-zinc-400">
                                        <UserCircle class="w-6 h-6" />
                                    </div>
                                </div>
                            </button>
                        </template>

                        <template #content>
                            <div class="px-4 py-3 border-b border-white/5 lg:hidden">
                                <p class="text-xs font-bold text-white">{{ user.name }}</p>
                                <p class="text-[10px] text-zinc-500 truncate">{{ user.email }}</p>
                            </div>
                            <DropdownLink :href="route('profile.edit')"> Profile Settings </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button" class="text-rose-500">
                                Sign Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
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
.bg-admin-main { background: #09090B; }
.bg-admin-sidebar { background: #0F0F10; }

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
