<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center px-8 py-4 bg-zinc-800 border border-white/5 rounded-2xl font-black text-xs text-white uppercase tracking-widest hover:bg-zinc-700 active:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-white/10 transition ease-in-out duration-150 active:scale-95"
    >
        <slot />
    </button>
</template>
