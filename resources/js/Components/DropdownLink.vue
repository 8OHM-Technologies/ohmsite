<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-5 py-3 text-start text-[11px] font-black uppercase tracking-widest text-zinc-400 hover:text-white hover:bg-white/[0.05] focus:bg-white/[0.05] focus:outline-none transition-all duration-300"
    >
        <slot />
    </Link>
</template>
