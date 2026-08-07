<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: String,
        default: 'py-1',
    },
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (props.align === 'right') {
        return 'ltr:origin-top-right rtl:origin-top-left end-0';
    } else {
        return 'origin-top';
    }
});

const widthClasses = computed(() => {
    return {
        48: 'w-48',
        96: 'w-96',
    }[props.width.toString()] || 'w-48';
});

const open = ref(false);
</script>

<template>
    <div class="relative">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div v-show="open" class="fixed inset-0 z-40" @click="open = false"></div>

        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-show="open" class="absolute z-50 mt-2 rounded-[20px] shadow-[0_25px_50px_rgba(0,0,0,0.6)]" :class="[alignmentClasses, widthClasses]"
                style="display: none" @click="open = false">
                <div class="p-[5px] rounded-[20px] bg-white/[0.04] border border-white/[0.08] backdrop-blur-xl">
                    <div class="rounded-[15px] border border-white/[0.10] bg-zinc-950/95 shadow-inner shadow-white/[0.06] overflow-hidden flex flex-col" :class="contentClasses">
                        <slot name="content" />
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
