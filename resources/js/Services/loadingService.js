import { ref } from 'vue';

const activeCount = ref(0);
const show = ref(false);
const progress = ref(0);
let progressInterval = null;
let finishTimeout = null;

const start = () => {
    activeCount.value++;
    if (activeCount.value === 1) {
        if (finishTimeout) {
            clearTimeout(finishTimeout);
            finishTimeout = null;
        }
        show.value = true;
        progress.value = 0;
        clearInterval(progressInterval);
        progressInterval = setInterval(() => {
            if (progress.value < 85) {
                progress.value += Math.random() * 12 + 2;
            }
        }, 150);
    }
};

const finish = () => {
    activeCount.value = Math.max(0, activeCount.value - 1);
    if (activeCount.value === 0) {
        progress.value = 100;
        clearInterval(progressInterval);
        finishTimeout = setTimeout(() => {
            show.value = false;
            progress.value = 0;
            finishTimeout = null;
        }, 300);
    }
};

const forceFinish = () => {
    activeCount.value = 0;
    progress.value = 100;
    clearInterval(progressInterval);
    if (finishTimeout) clearTimeout(finishTimeout);
    finishTimeout = setTimeout(() => {
        show.value = false;
        progress.value = 0;
        finishTimeout = null;
    }, 300);
};

export const loadingService = {
    activeCount,
    show,
    progress,
    start,
    finish,
    forceFinish,
};

export default loadingService;
