<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  sectionHeaders: {
    type: Object,
    default: () => ({})
  }
})

const root = ref(null)

const sectionHeader = (field) => {
  const defaults = {
    eyebrow: 'Why Infinity Ohm?',
    title: 'Our Philosophy',
    subtitle: 'Strategic engineering, absolute data ownership, and open standards.',
  };
  return props.sectionHeaders?.philosophy?.[field] || defaults[field] || '';
};

onMounted(() => {
  // Setup Reveal Intersection Observer
  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('revealed')
        }, index * 80)
        obs.unobserve(entry.target)
      }
    })
  }, { threshold: 0.1 })

  root.value?.querySelectorAll('.reveal-item').forEach((item) => {
    observer.observe(item)
  })
})
</script>

<template>
  <div ref="root">
    <section class="features-section container reveal-item" id="philosophy" aria-label="Our Philosophy">
      <div class="section-header">
        <div class="eyebrow-badge">
          <span class="eyebrow-dot"></span>
          <span class="eyebrow-text">{{ sectionHeader('eyebrow') }}</span>
        </div>
        <h2 class="section-title">{{ sectionHeader('title') }}</h2>
        <p class="section-subtitle">
          {{ sectionHeader('subtitle') }}
        </p>
      </div>
      <div class="bento-grid">

        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-cloud-check"></i>
              Local-First Resilience
            </h4>
            <p class="card-desc-small">
              Intelligence belongs at the edge. Our solutions prioritize local processing, privacy, and
              offline
              autonomy, ensuring that augmented systems remain fully operational, resilient, and independent of
              fragile
              cloud dependencies.
            </p>
          </div>
        </div>

        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-git-merge"></i>
              Ecosystem Interoperability
            </h4>
            <p class="card-desc-small">
              We design and map technology infrastructure to break down the boundaries of rigid, isolated
              tech stacks. By evaluating your complete operational footprint, we deliver custom blueprints
              that tightly integrate legacy devices, edge intelligence, and modern cloud utilities.
            </p>
          </div>
        </div>

        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-lock-key"></i>
              Non-Invasive Evolution
            </h4>
            <p class="card-desc-small">
              Progress shouldn't require destruction. We engineer smart retrofits that install seamlessly
              alongside existing workflows, ensuring zero operational disruption and preserving the validated
              mechanics of
              proven hardware. Our translation layers ensure your legacy systems securely output the
              structured
              data of tomorrow.
            </p>
          </div>
        </div>

        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-recycle"></i>
              Sustainability Through Retrofitting
            </h4>
            <p class="card-desc-small">
              We actively combat the growing e-waste crisis by extending the operational lifespan of
              existing mechanical and electrical machinery. Through modular, smart retrofitting, we
              seamlessly inject modern digital capabilities into legacy hardware. This sustainable
              approach eliminates the need for costly capital replacements, drastically reduces industrial
              footprint, and proves that environmental responsibility directly drives operational
              efficiency.
            </p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
  width: 100%;
}

.section-header {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.section-title {
  font-family: var(--font-display);
  font-size: 2.5rem;
  color: var(--color-text-primary);
  margin-top: 16px;
  font-weight: 800;
}

.section-subtitle {
  color: var(--color-text-secondary);
  max-width: 800px;
  margin: 8px auto 0;
}

.eyebrow-badge {
  background: rgba(255, 136, 0, 0.06);
  border: 1px solid rgba(255, 136, 0, 0.15);
  padding: 6px 16px;
  border-radius: 100px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 0;
}

.eyebrow-dot {
  width: 5px;
  height: 5px;
  background: var(--color-accent-primary);
  border-radius: 50%;
}

.eyebrow-text {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--color-accent-primary);
  letter-spacing: 2px;
  text-transform: uppercase;
}

/* --- Bento Grid Layout --- */
.features-section {
  position: relative;
  z-index: 10;
  padding: 2rem 1rem;
  height: 100%;
  width: 100%;
  scroll-snap-align: start;
  scroll-snap-stop: always;
}

.bento-grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 24px;
  margin-top: 2rem;
}

.col-span-6 {
  grid-column: span 6;
}

.bezel-card-outer {
  background: var(--color-surface-outer);
  border: 1px solid var(--color-border-outer);
  padding: 6px;
  border-radius: 28px;
  transition: all 0.6s var(--ease-premium);
  display: flex;
}

.bezel-card-outer:hover {
  transform: translateY(-8px);
  background: rgba(255, 255, 255, 0.03);
  border-color: rgba(255, 136, 0, 0.25);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6), 0 0 50px rgba(255, 136, 0, 0.04);
}

.bezel-card-inner {
  background: var(--color-surface-inner);
  border-radius: 22px;
  border: 1px solid var(--color-border-inner);
  padding: 20px;
  width: 100%;
  display: flex;
  flex-direction: column;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.06);
  position: relative;
  overflow: hidden;
}

.card-title-small {
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin-bottom: 12px;
  display: flex;
  gap: 10px;
  align-items: center;
}

.card-title-small i {
  color: var(--color-accent-primary);
  font-size: 1.4rem;
}

.card-desc-small {
  font-size: 0.9rem;
  color: var(--color-text-secondary);
  line-height: 1.6;
}

/* --- Scroll Reveal Animations --- */
.reveal-item {
  opacity: 0;
  transform: translateY(32px);
  transition: opacity 1.2s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1)), transform 1.2s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1)), filter 1.2s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1));
  filter: blur(8px);
  will-change: transform, opacity, filter;
}

.reveal-item.revealed {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}

@media (max-width: 768px) {
  .features-section {
    height: auto;
    min-height: auto;
    scroll-snap-align: none;
    scroll-snap-stop: normal;
  }

  .bento-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .col-span-6 {
    grid-column: span 1 !important;
  }
}

@media (prefers-reduced-motion: reduce) {
  .reveal-item {
    opacity: 1 !important;
    transform: none !important;
    filter: none !important;
    transition: none !important;
  }
}
</style>
