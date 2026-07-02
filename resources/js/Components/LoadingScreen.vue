<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const show = ref(false);
const progress = ref(0);
let interval = null;

const start = () => {
    show.value = true;
    progress.value = 0;
    clearInterval(interval);
    interval = setInterval(() => {
        if (progress.value < 90) {
            progress.value += Math.random() * 10;
        }
    }, 200);
};

const finish = () => {
    progress.value = 100;
    setTimeout(() => {
        show.value = false;
        clearInterval(interval);
    }, 300);
};

onMounted(() => {
    router.on('start', start);
    router.on('finish', finish);
});

onUnmounted(() => {
    // Note: Inertia listeners are global, so we don't strictly need to unregister 
    // but it's good practice if this component were scoped.
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-500"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-[200] flex flex-col items-center justify-center bg-black">
            <div class="relative mb-8">
                <img src="/assets/images/logo-white.png" class="h-16 animate-pulse" />
                <div class="absolute -inset-4 bg-white/5 blur-3xl rounded-full"></div>
            </div>
            
            <div class="w-64 h-[2px] bg-zinc-900 rounded-full overflow-hidden relative">
                <div 
                    class="absolute inset-y-0 left-0 bg-white transition-all duration-300 ease-out shadow-[0_0_15px_rgba(255,255,255,0.8)]"
                    :style="{ width: progress + '%' }"
                ></div>
            </div>
            
            <div class="mt-4 text-[10px] font-black uppercase tracking-[0.5em] text-zinc-500 animate-pulse">
                Loading...
            </div>
        </div>
    </Transition>
</template>
