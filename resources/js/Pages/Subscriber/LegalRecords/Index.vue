<script setup lang="ts">
import Cases from './Cases.vue';

interface FilterItem {
  target_name: string;
  vanity_name: string;
  target_type: string;
}

defineProps<{
  filters: FilterItem[];
}>();
</script>

<template>
  <Cases :filters="filters" />
</template>
