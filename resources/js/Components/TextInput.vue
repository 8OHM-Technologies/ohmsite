<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="bg-black border-white/5 rounded-2xl text-sm font-bold uppercase tracking-widest text-white focus:border-white/20 focus:ring-1 focus:ring-white/20 transition-all placeholder:text-zinc-800 p-4"
        v-model="model"
        ref="input"
    />
</template>
