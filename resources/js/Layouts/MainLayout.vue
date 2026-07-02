<script setup>
import { Head } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import LoadingScreen from '@/Components/LoadingScreen.vue';
import Toast from '@/Components/Toast.vue';

defineProps({
    auth: Object,
    title: String,
});
</script>

<template>
    <div class="min-h-screen bg-black text-white font-sans overflow-x-hidden scroll-smooth selection:bg-white selection:text-black">
        <Head :title="title" />
        
        <LoadingScreen />
        <Toast />

        <Navbar :auth="auth" />

        <main class="pt-80 pb-20 px-4 sm:px-6 lg:px-10 max-w-[1600px] mx-auto">
            <slot />
        </main>

        <footer class="pt-20 pb-10 border-t border-white/5 text-center">
            <img src="/assets/images/logo-white.png" class="h-8 mx-auto opacity-20 mb-8" />
            <div class="text-zinc-700 text-[10px] font-black uppercase tracking-[0.5em]">
                © 2026 And Murturi. All rights reserved.
            </div>
        </footer>
    </div>
</template>

<style>
.font-sans {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
</style>
