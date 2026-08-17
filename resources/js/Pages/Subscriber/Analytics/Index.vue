<script setup>
import SafliiCourts from './SafliiCourts.vue';

defineProps({
    filters: { type: Array, default: () => [] },
});
</script>

<template>
    <SafliiCourts :filters="filters" />
</template>
