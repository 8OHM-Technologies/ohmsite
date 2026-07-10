<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
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
    UserCircle,
    Layers,
    Clock,
    TrendingUp,
    Building
} from 'lucide-vue-next';

</script>

<template>
    <div class="min-h-screen bg-admin-main text-white font-sans selection:bg-admin-modern selection:text-black">
        <LoadingScreen />
        <Toast />

        <!-- Main Content Area -->
        <div class="flex flex-col min-h-screen">
            <!-- Topbar -->
            <header
                class="min-h-20 lg:min-h-24 py-4 flex flex-col xl:flex-row xl:items-center justify-between px-4 sm:px-8 lg:px-10 sticky top-0 bg-admin-main/80 backdrop-blur-xl z-30 border-b border-white/5 gap-6 xl:gap-8">

                <!-- Logo & User Profile -->
                <div class="flex items-center gap-6 shrink-0">
                    <Link href="/">
                        <img src="/assets/images/8OHM_Logo.webp" alt="Logo"
                            class="h-8 transition-transform hover:scale-105" />
                    </Link>

                    <div class="h-8 w-px bg-white/10"></div>
                    <div>
                        <h1 class="text-4xl font-black text-white tracking-tighter uppercase">
                            <span class="text-admin-modern">Analytics - Legal Dataset</span>
                        </h1>
                        <p class="text-zinc-500 mt-2 font-bold uppercase tracking-widest text-[10px]">
                            South African CCMA Arbitration & Dispute Intelligence
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
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
