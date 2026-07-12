<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import LoadingScreen from '@/Components/LoadingScreen.vue';
import Toast from '@/Components/Toast.vue';

defineProps({
    auth: Object,
    title: String,
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
    <div
        class="min-h-screen bg-black text-white font-sans overflow-x-hidden scroll-smooth selection:bg-white selection:text-black relative">

        <Head :title="title" />

        <LoadingScreen />
        <Toast />

        <!-- Background Visuals -->
        <div class="background-visuals">
            <div class="glow-orb orb-1" ref="orb1"></div>
            <div class="glow-orb orb-2" ref="orb2"></div>
            <div class="glow-orb orb-3" ref="orb3"></div>
            <div class="grid-overlay"></div>
            <div class="noise-overlay"></div>
        </div>

        <Navbar :auth="auth" class="relative z-10" />

        <main class="pt-35 pb-20 px-4 sm:px-6 lg:px-10 max-w-[1600px] mx-auto relative z-10">
            <slot />
        </main>

        <footer class="pt-20 pb-10 border-t border-white/5 text-center relative z-10">
            <img src="/assets/images/8OHM_Logo.webp" class="h-8 mx-auto opacity-20 mb-8" />
            <div class="text-zinc-700 text-[10px] font-black uppercase tracking-[0.5em]">
                © 2026 Infinity Ohm Technologies (Pty) Ltd. All rights reserved.
            </div>
        </footer>
    </div>
</template>