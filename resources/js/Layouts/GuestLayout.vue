<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
            <slot />
        </div>
        
        <footer class="pt-20 pb-10 border-t border-white/5 text-center relative z-10">
            <img src="/assets/images/8OHM_Logo.webp" class="h-8 mx-auto opacity-20 mb-8" />
            <div class="text-zinc-700 text-[10px] font-black uppercase tracking-[0.5em]">
                © 2026 Infinity Ohm Technologies (Pty) Ltd t/a 8OHM. All rights reserved.
            </div>
        </footer>
    </div>
</template>
