<script setup>
import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { loadingService } from '@/Services/loadingService';

let removeStartListener = null;
let removeFinishListener = null;

onMounted(() => {
    removeStartListener = router.on('start', () => loadingService.start());
    removeFinishListener = router.on('finish', () => loadingService.finish());
});

onUnmounted(() => {
    if (typeof removeStartListener === 'function') removeStartListener();
    if (typeof removeFinishListener === 'function') removeFinishListener();
});
</script>

<template>
    <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition ease-in duration-300" leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-if="loadingService.show.value"
            class="fixed inset-0 z-[200] flex flex-col items-center justify-center bg-black/65 backdrop-blur-[2px] pointer-events-auto select-none">
            <div class="relative mb-8">
                <img src="/assets/images/8OHM_Logo.webp" alt="8OHM Loading" class="h-16 animate-pulse drop-shadow-[0_0_25px_rgba(255,255,255,0.25)]" />
                <div class="absolute -inset-4 bg-primary/10 blur-3xl rounded-full pointer-events-none"></div>
            </div>

            <div class="w-64 h-[2px] bg-zinc-900/80 rounded-full overflow-hidden relative border border-white/10 shadow-inner">
                <div class="absolute inset-y-0 left-0 bg-primary transition-all duration-200 ease-out shadow-[0_0_15px_rgba(255,136,0,0.8)]"
                    :style="{ width: loadingService.progress.value + '%' }"></div>
            </div>

            <div class="mt-4 text-[10px] font-black uppercase tracking-[0.5em] text-zinc-400 animate-pulse">
                Loading...
            </div>
        </div>
    </Transition>
</template>

