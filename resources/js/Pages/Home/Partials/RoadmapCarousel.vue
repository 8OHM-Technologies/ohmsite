<script setup>
import { computed, ref } from 'vue'
import Carousel from 'primevue/carousel'

const props = defineProps({
  roadmapItems: {
    type: Array,
    default: () => []
  }
})

const roadmap = computed(() => {
  if (props.roadmapItems && props.roadmapItems.length > 0) {
    return props.roadmapItems;
  }
  return [
    {
      status: 'Live',
      date: 'Current',
      title: 'SA Labour & High Courts Case Law',
      description: 'Comprehensive case law from the Labour and Labour Appeal Courts as well as the High Courts and Supreme Court of Appeal.',
      icon: 'ph-check-circle',
      iconClass: 'text-green-500'
    },
    {
      status: 'Planned',
      date: 'Q4 2026',
      title: 'Tribunals and Other Courts Case Law',
      description: 'Comprehensive case law from the various SA Tribunals, as well as smaller courts including the Equality, Electoral and Tax Courts.',
      icon: 'ph-rocket-launch',
      iconClass: 'text-yellow-500'
    },
    {
      status: 'Planned',
      date: 'Q1 2027',
      title: 'Industry Expansion',
      description: 'Expansion of our datasets covering other industries like mining, logistics, energy and health & safety.',
      icon: 'ph-rocket-launch',
      iconClass: 'text-yellow-500'
    },
    {
      status: 'Future',
      date: '2027',
      title: 'LaybaLaw AI',
      description: 'South African Labour Law & CCMA AI Assistant - Help average South Africans navigate disputes with their employers.',
      icon: 'ph-robot',
      iconClass: 'text-red-500'
    },
    {
      status: 'Future',
      date: '2027',
      title: 'International Mining Landscape Dashboard',
      description: 'Analytics dashboard providing insights into the global mining industry, including mineral ',
      icon: 'ph-robot',
      iconClass: 'text-red-500'
    },
  ];
});

const responsiveOptions = ref([
  {
    breakpoint: '1024px',
    numVisible: 3,
    numScroll: 1
  },
  {
    breakpoint: '768px',
    numVisible: 2,
    numScroll: 1
  },
  {
    breakpoint: '560px',
    numVisible: 1,
    numScroll: 1
  }
]);
</script>

<template>
  <!-- Roadmap Carousel Wrapper -->
  <div class="w-full max-w-5xl mx-auto mt-14 sm:mt-auto pb-1 relative z-25">
    <Carousel :value="roadmap" :numVisible="3" :numScroll="1" :responsiveOptions="responsiveOptions"
      :showIndicators="false" class="roadmap-carousel">
      <template #item="slotProps">
        <div class="roadmap-card mx-1.5 h-full">
          <!-- Status Badge -->
          <div class="status-badge">
            <i class="ph-fill" :class="[slotProps.data.icon, slotProps.data.iconClass]"></i>
            <span>{{ slotProps.data.status }} &bull; {{ slotProps.data.date }}</span>
          </div>

          <h4 class="card-title-small">
            {{ slotProps.data.title }}
          </h4>

          <p class="card-desc-small">
            {{ slotProps.data.description }}
          </p>
        </div>
      </template>
    </Carousel>
  </div>
</template>

<style scoped>
/* Fallbacks */
.w-full {
  width: 100%;
}

.status-badge {
  display: inline-flex;
  align-self: flex-start;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 4px 10px;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.roadmap-card {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  height: 100%;
  text-align: left;
  transition: all 0.3s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1));
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.01);
}

.roadmap-card:hover {
  background: rgba(255, 255, 255, 0.035);
  border-color: rgba(255, 136, 0, 0.15);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3), inset 0 1px 1px rgba(255, 255, 255, 0.02);
  transform: translateY(-1px);
}

.roadmap-card .status-badge {
  font-size: 0.625rem;
  padding: 2px 6px;
  margin-bottom: 2px;
  gap: 4px;
}

.card-title-small {
  font-family: var(--font-display, inherit);
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--color-text-primary, #f0f0f5);
  margin-bottom: 12px;
  display: flex;
  gap: 10px;
  align-items: center;
}

.roadmap-card .card-title-small {
  font-size: 0.95rem;
  margin-bottom: 2px;
  gap: 8px;
}

.card-desc-small {
  font-size: 0.9rem;
  color: var(--color-text-secondary, #94a3b8);
  line-height: 1.6;
}

.roadmap-card .card-desc-small {
  font-size: 0.8rem;
  line-height: 1.4;
}

/* Scoped styles to style PrimeVue Carousel custom elements */
:deep(.p-carousel-content) {
  position: relative;
  display: flex;
  align-items: center;
  gap: 1rem;
}

:deep(.p-carousel-container) {
  width: 100%;
}

:deep(.p-carousel-prev),
:deep(.p-carousel-next) {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-color);
  border-radius: 9999px;
  width: 2.5rem;
  height: 2.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-primary);
  transition: all 0.3s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1));
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  cursor: pointer;
  z-index: 10;
}

:deep(.p-carousel-prev:hover),
:deep(.p-carousel-next:hover) {
  background: rgba(255, 255, 255, 0.08);
  color: var(--color-accent-primary);
  border-color: var(--color-accent-primary);
}

:deep(.p-carousel-prev:disabled),
:deep(.p-carousel-next:disabled) {
  opacity: 0.3;
  cursor: not-allowed;
}

:deep(.p-carousel-items-container) {
  gap: 0;
}

:deep(.p-carousel-item) {
  padding-top: 1.25rem;
  padding-bottom: 1.25rem;
}
</style>
