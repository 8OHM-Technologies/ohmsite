<script setup>
import { computed } from 'vue'

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
      title: 'CCMA Arbitration Awards',
      description: 'Comprehensive CCMA Awards & Judgment records.',
      icon: 'ph-check-circle',
      iconClass: 'text-green-500'
    },
    {
      status: 'Upcoming',
      date: 'Q3 2026',
      title: 'SA Labour Court Case Law',
      description: 'Comprehensive case law from the Labour and Labour Appeal Courts.',
      icon: 'ph-spinner',
      iconClass: 'text-blue-500'
    },
    {
      status: 'Upcoming',
      date: 'Q4 2026',
      title: 'SA High Court Case Law',
      description: 'Comprehensive case law from the High Court, Supreme Court of Appeal, and the Constitutional Court.',
      icon: 'ph-calendar-blank',
      iconClass: 'text-orange-500'
    },
    {
      status: 'Planned',
      date: 'Q1 2027',
      title: 'Tribunals and Other Courts Case Law',
      description: 'Comprehensive case law from the various SA Tribunals, as well as smaller courts including the Equality, Electoral and Tax Courts.',
      icon: 'ph-rocket-launch',
      iconClass: 'text-yellow-500'
    },
    {
      status: 'Planned',
      date: '2027',
      title: 'Legislative Expansion',
      description: 'Expansion of our Legal dataset to include acts, bills, government gazettes and other legislative documents.',
      icon: 'ph-rocket-launch',
      iconClass: 'text-yellow-500'
    },
    {
      status: 'Ultimate Goal',
      date: '2027',
      title: 'LaybaLaw AI',
      description: 'South African Labour Law & CCMA AI Assistant - Help average South Africans navigate disputes with their employers.',
      icon: 'ph-robot',
      iconClass: 'text-red-500'
    },
  ];
});
</script>

<template>
  <!-- Roadmap Carousel Wrapper -->
  <div class="w-full max-w-5xl mx-auto mt-14 sm:mt-auto pb-1 relative z-25">
    <!-- Roadmap Carousel -->
    <div id="multi-slide" class="relative px-8 sm:px-10"
      data-carousel='{ "loadingClasses": "opacity-0", "slidesQty": { "xs": 1, "lg": 3 }, "isDraggable": true }'>
      <div class="carousel">
        <div
          class="carousel-body h-full carousel-dragging:transition-none carousel-dragging:cursor-grabbing cursor-grab opacity-0">
          <div v-for="item in roadmap" :key="item.title" class="carousel-slide"
            style="padding-top: 1px !important">
            <div class="roadmap-card mx-1.5">

              <!-- Status Badge -->
              <div class="status-badge">
                <i class="ph-fill" :class="[item.icon, item.iconClass]"></i>
                <span>{{ item.status }} &bull; {{ item.date }}</span>
              </div>

              <h4 class="card-title-small">
                {{ item.title }}
              </h4>

              <p class="card-desc-small">
                {{ item.description }}
              </p>
            </div>
          </div>
        </div>
      </div>
      <!-- Previous Slide -->
      <button type="button"
        class="carousel-prev inset-s-3 max-sm:inset-s-3 carousel-disabled:opacity-50 size-9.5 bg-base-100 flex items-center justify-center rounded-full shadow-base-300/20 shadow-sm"
        style="background:var(--bg-tertiary);border:1px solid var(--border-color);">
        <i class="carousel-arrow ph-caret-left"></i>
        <span class="sr-only">Previous</span>
      </button>
      <!-- Next Slide -->
      <button type="button"
        class="carousel-next inset-e-5 max-sm:inset-e-3 carousel-disabled:opacity-50 size-9.5 bg-base-100 flex items-center justify-center rounded-full shadow-base-300/20 shadow-sm"
        style="background:var(--bg-tertiary);border:1px solid var(--border-color);">
        <i class="carousel-arrow ph-caret-right"></i>
        <span class="sr-only">Next</span>
      </button>
    </div>
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
</style>
