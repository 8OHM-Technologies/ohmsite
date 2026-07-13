<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import {
    Search,
    ShoppingCart,
    Heart,
    Menu,
    X,
    User,
    LogOut,
    ChevronRight,
    Home,
    ShoppingBag,
    Star,
    Grid,
    Info
} from 'lucide-vue-next';
import { PhMusicNote, PhQuestion, PhToolbox } from '@phosphor-icons/vue';

const props = defineProps({
    auth: Object,
});

const searchQuery = ref('');
const isMobileMenuOpen = ref(false);
const isScrolled = ref(false);
const scrollProgress = ref(0);
const activeSection = ref('home');

const performSearch = () => {
    if (searchQuery.value.trim()) {
        router.get(route('services.index'), { search: searchQuery.value });
        isMobileMenuOpen.value = false;
    }
};

const handleScroll = () => {
    const scrollY = window.scrollY;
    isScrolled.value = scrollY > 50;
    scrollProgress.value = Math.min(scrollY / 200, 1);

    if (route().current('home')) {
        const sections = ['home', 'services', 'philosophy', 'ohmbase', 'faq', 'contact'];
        for (const section of sections) {
            const el = document.getElementById(section);
            if (el) {
                const rect = el.getBoundingClientRect();
                if (rect.top <= 150 && rect.bottom >= 150) {
                    activeSection.value = section;
                    break;
                }
            }
        }
    }
};

const navLinks = [
    { name: 'Home', id: 'home', type: 'anchor', icon: Home },
    { name: 'Services', id: 'services', type: 'anchor', icon: Grid },
    { name: 'Our Philosophy', id: 'philosophy', type: 'anchor', icon: PhMusicNote },
    { name: 'OhmBase', id: 'ohmbase', type: 'anchor', icon: PhToolbox },
    { name: 'FAQ', id: 'faq', type: 'anchor', icon: PhQuestion },
    { name: 'Contact', id: 'contact', type: 'anchor', icon: Star },
];

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

// Close mobile menu on route change
router.on('finish', () => {
    isMobileMenuOpen.value = false;
    document.body.style.overflow = '';
});

watch(isMobileMenuOpen, (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
});

const isLinkActive = (link) => {
    if (link.type === 'route') {
        return route().current(link.id);
    }
    if (link.type === 'anchor' && route().current('home')) {
        return activeSection.value === link.id;
    }
    return false;
};

const getHref = (link) => {
    if (link.type === 'route') return route(link.id);
    if (route().current('home')) return '#' + link.id;
    return route('home') + '#' + link.id;
};
</script>

<template>
    <nav :class="[
        'fixed top-0 w-full z-50 transition-all duration-500 flex flex-col items-center',
        isScrolled ? 'py-3 bg-black/60 backdrop-blur-xl border-b border-white/5' : 'py-6 nav:py-10'
    ]">
        <div
            class="w-full px-4 sm:px-8 nav:px-12 flex nav:grid nav:grid-cols-3 items-center justify-between max-w-[1920px] mx-auto relative">

            <!-- Mobile Menu Toggle -->
            <button @click="isMobileMenuOpen = true"
                class="nav:hidden p-2 text-zinc-400 hover:text-white transition-colors">
                <Menu class="w-6 h-6" />
            </button>

            <!-- Logo Section - Center on mobile/tablet, Left on desktop when scrolled -->
            <div class="absolute left-1/2 -translate-x-1/2 nav:static nav:translate-x-0 nav:justify-self-start transition-all duration-500 flex items-center"
                :class="[isScrolled ? 'nav:w-40' : 'nav:w-auto']">
                <Link :href="route('home')" class="block">
                    <img src="/assets/images/8OHM_Logo.webp" alt="8OHM | End-to-end Data Solutions | Logo"
                        class="transition-all duration-500" :style="{
                            height: isScrolled ? '24px' : '48px',
                            maxHeight: '128px'
                        }" />
                </Link>
            </div>

            <!-- Desktop Links Section -->
            <div class="hidden nav:flex nav:justify-self-center items-center space-x-1 bg-zinc-900/40 rounded-full p-1 border border-white/5 backdrop-blur-sm transition-opacity duration-300"
                :class="[isScrolled ? 'opacity-100' : 'opacity-100']">
                <template v-for="link in navLinks" :key="link.name">
                    <Link v-if="link.type === 'route' || !route().current('home')" :href="getHref(link)" :class="[
                        'px-6 py-2.5 rounded-full text-[11px] font-black uppercase tracking-widest transition-all duration-300 relative z-10 whitespace-nowrap',
                        isLinkActive(link) ? 'text-black' : 'text-zinc-500 hover:text-white'
                    ]">
                        <span class="relative z-10">{{ link.name }}</span>
                        <div v-if="isLinkActive(link)"
                            class="absolute inset-0 bg-primary rounded-full z-0 shadow-[0_0_20px_rgba(255,136,0,0.4)]">
                        </div>
                    </Link>
                    <a v-else :href="'#' + link.id" :class="[
                        'px-6 py-2.5 rounded-full text-[11px] font-black uppercase tracking-widest transition-all duration-300 relative z-10 whitespace-nowrap',
                        isLinkActive(link) ? 'text-black' : 'text-zinc-500 hover:text-white'
                    ]">
                        <span class="relative z-10">{{ link.name }}</span>
                        <div v-if="isLinkActive(link)"
                            class="absolute inset-0 bg-primary rounded-full z-0 shadow-[0_0_20px_rgba(255,136,0,0.4)]">
                        </div>
                    </a>
                </template>
                <!-- Search (Desktop) -->
                <!-- <div class="hidden md:relative md:flex items-center group">
                    <Search
                        class="w-4 h-4 absolute left-3.5 text-zinc-500 group-focus-within:text-white transition-colors" />
                    <input type="text" v-model="searchQuery" @keyup.enter="performSearch" placeholder="Search..."
                        class="bg-zinc-900/50 border border-white/5 rounded-full py-2 pl-10 pr-4 text-[11px] w-32 focus:w-48 transition-all duration-500 focus:ring-1 focus:ring-white/20 placeholder:text-zinc-600 text-white uppercase font-bold tracking-widest" />
                </div> -->

            </div>

            <!-- Right Side: Search, Cart, Profile -->
            <div class="hidden md:flex nav:justify-self-end items-center space-x-2 sm:space-x-4">
                <!-- Wishlist -->
                <!-- <Link :href="route('favorites.index')"
                    class="p-2.5 hover:bg-zinc-800 rounded-full transition-all duration-300 group border border-transparent hover:border-white/5">
                    <Heart class="w-5 h-5 text-zinc-500 group-hover:text-rose-500 transition-colors"
                        :class="{ 'fill-rose-500 text-rose-500': route().current('favorites.index') }" />
                </Link> -->

                <!-- Cart -->
                <Link :href="route('cart.index')"
                    class="p-2.5 hover:bg-zinc-800 rounded-full transition-all duration-300 relative group border border-transparent hover:border-white/5">
                    <ShoppingCart class="w-5 h-5 text-zinc-500 group-hover:text-white" />
                    <span v-if="$page.props.cart_count > 0"
                        class="absolute top-1 right-1 bg-primary text-black text-[11px] font-black w-4 h-4 rounded-full flex items-center justify-center shadow-lg">{{
                            $page.props.cart_count }}</span>
                </Link>

                <!-- Profile -->
                <div class="flex items-center">
                    <template v-if="auth.user">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    class="flex items-center space-x-3 bg-zinc-900/50 border border-white/5 hover:bg-zinc-800 p-1.5 pr-4 rounded-full transition-all duration-300">
                                    <div
                                        class="w-8 h-8 bg-zinc-800 border border-white/10 rounded-full flex items-center justify-center overflow-hidden">
                                        <img v-if="auth.user.profile_photo_url" :src="auth.user.profile_photo_url"
                                            class="w-full h-full object-cover" />
                                        <span v-else class="text-[11px] font-black">{{ auth.user.name.charAt(0)
                                        }}</span>
                                    </div>
                                    <span
                                        class="hidden sm:inline-block text-[11px] font-black tracking-widest uppercase text-zinc-300">{{
                                            auth.user.name }}</span>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink v-if="auth.user.role === 'admin'" :href="route('dashboard')"> Admin
                                    Dashboard </DropdownLink>
                                <DropdownLink :href="route('profile.edit')"> My Profile </DropdownLink>
                                <DropdownLink :href="route('orders.index')"> Order History </DropdownLink>
                                <div class="border-t border-white/5 my-1"></div>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="text-rose-500">
                                    Log Out </DropdownLink>
                            </template>
                        </Dropdown>
                    </template>
                    <template v-else>
                        <Link :href="route('login')"
                            class="text-[11px] font-black tracking-[0.2em] uppercase bg-primary text-black px-6 py-3 rounded-full hover:bg-primary-600 transition-all shadow-xl active:scale-95">
                            Login</Link>
                    </template>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Drawer -->
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="-translate-x-full"
        enter-to-class="translate-x-0" leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-x-0" leave-to-class="-translate-x-full">
        <div v-if="isMobileMenuOpen" class="fixed inset-0 z-[60] nav:hidden">
            <!-- Overlay -->
            <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" @click="isMobileMenuOpen = false"></div>

            <!-- Drawer -->
            <div
                class="absolute inset-y-0 left-0 w-[85%] max-w-sm bg-zinc-900 border-r border-white/5 shadow-2xl flex flex-col">
                <div class="p-8 flex items-center justify-between border-b border-white/5">
                    <img src="/assets/images/8OHM_Logo.webp" alt="Logo" class="h-6" />
                    <button @click="isMobileMenuOpen = false" class="p-2 text-zinc-500 hover:text-white">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 space-y-8">
                    <!-- Mobile Search -->
                    <div class="relative group">
                        <Search class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-zinc-600" />
                        <input type="text" v-model="searchQuery" @keyup.enter="performSearch"
                            placeholder="Search products..."
                            class="w-full bg-black border border-white/5 rounded-2xl py-4 pl-12 pr-4 text-xs font-bold uppercase tracking-widest text-white focus:ring-1 focus:ring-white/20" />
                    </div>

                    <!-- Main Links -->
                    <nav class="space-y-2">
                        <template v-for="link in navLinks" :key="link.name">
                            <Link v-if="link.type === 'route' || !route().current('home')" :href="getHref(link)"
                                class="flex items-center justify-between p-4 rounded-2xl transition-all"
                                :class="[isLinkActive(link) ? 'bg-primary text-black' : 'text-zinc-500 hover:text-white hover:bg-white/5']">
                                <div class="flex items-center gap-4">
                                    <component :is="link.icon" class="w-5 h-5" />
                                    <span class="text-xs font-black uppercase tracking-widest">{{ link.name
                                        }}</span>
                                </div>
                                <ChevronRight class="w-4 h-4 opacity-50" />
                            </Link>
                            <a v-else :href="'#' + link.id" @click="isMobileMenuOpen = false"
                                class="flex items-center justify-between p-4 rounded-2xl transition-all"
                                :class="[isLinkActive(link) ? 'bg-primary text-black' : 'text-zinc-500 hover:text-white hover:bg-white/5']">
                                <div class="flex items-center gap-4">
                                    <component :is="link.icon" class="w-5 h-5" />
                                    <span class="text-xs font-black uppercase tracking-widest">{{ link.name
                                        }}</span>
                                </div>
                                <ChevronRight class="w-4 h-4 opacity-50" />
                            </a>
                        </template>

                        <div class="h-px bg-white/5 my-2"></div>

                        <!-- Favorites -->
                        <Link :href="route('favorites.index')"
                            class="flex items-center justify-between p-4 rounded-2xl transition-all"
                            :class="[route().current('favorites.index') ? 'bg-primary text-black' : 'text-zinc-500 hover:text-white hover:bg-white/5']">
                            <div class="flex items-center gap-4">
                                <Heart class="w-5 h-5"
                                    :class="{ 'fill-rose-500 text-rose-500': route().current('favorites.index') }" />
                                <span class="text-xs font-black uppercase tracking-widest">Favorites</span>
                            </div>
                            <ChevronRight class="w-4 h-4 opacity-50" />
                        </Link>

                        <!-- Cart -->
                        <Link :href="route('cart.index')"
                            class="flex items-center justify-between p-4 rounded-2xl transition-all"
                            :class="[route().current('cart.index') ? 'bg-primary text-black' : 'text-zinc-500 hover:text-white hover:bg-white/5']">
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <ShoppingCart class="w-5 h-5" />
                                    <span v-if="$page.props.cart_count > 0"
                                        class="absolute -top-1.5 -right-1.5 bg-black text-white shadow-[0_0_10px_rgba(0,0,0,0.3)] text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center">{{
                                            $page.props.cart_count }}</span>
                                </div>
                                <span class="text-xs font-black uppercase tracking-widest">Cart</span>
                            </div>
                            <ChevronRight class="w-4 h-4 opacity-50" />
                        </Link>
                    </nav>
                </div>

                <!-- Footer / Account -->
                <div class="p-8 border-t border-white/5 bg-black/50">
                    <div v-if="auth.user" class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-white text-black rounded-xl flex items-center justify-center font-black">
                                {{ auth.user.name.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-widest text-white">{{
                                    auth.user.name }}
                                </p>
                                <p class="text-[11px] font-bold uppercase tracking-widest text-zinc-600 mt-0.5">My
                                    Account
                                </p>
                            </div>
                        </div>
                        <Link :href="route('logout')" method="post" as="button"
                            class="p-2 text-zinc-500 hover:text-rose-500 transition-colors">
                            <LogOut class="w-5 h-5" />
                        </Link>
                    </div>
                    <Link v-else :href="route('login')"
                        class="w-full flex items-center justify-center py-4 bg-white text-black rounded-2xl font-black uppercase tracking-widest text-xs">
                        Sign In
                    </Link>
                </div>
            </div>
        </div>
    </Transition>
</template>
