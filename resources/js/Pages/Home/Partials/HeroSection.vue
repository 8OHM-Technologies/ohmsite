<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import RoadmapCarousel from './RoadmapCarousel.vue'

defineProps({
  roadmapItems: {
    type: Array,
    default: () => []
  }
})

defineEmits(['open-modal'])

const root = ref(null)

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
  <div ref="root" class="w-full">
    <section class="hero container" id="home">
      <!-- Hero main content grouped to minimize empty white space -->
      <div class="flex flex-col items-center justify-center flex-grow gap-4 sm:gap-6 w-full">
        <div class="eyebrow-badge reveal-item">
          <span class="eyebrow-dot"></span>
          <span class="eyebrow-text">End-to-end Data Solutions</span>
        </div>

        <h1 class="hero-title reveal-item">
          Turn Fragmented Data<br />
          <span class="gradient-text">into Analytical Advantage</span>
        </h1>

        <p class="hero-tagline reveal-item">
          South African public data sources are notoriously unstable and difficult to extract. We handle the
          extraction,
          cleaning, and structuring of public records—starting with the CCMA and Labour Courts, and expanding soon to
          High Courts and beyond. Get reliable,
          POPIA-compliant data feeds directly via API, track judicial trends using our analytics platform, or let us
          build custom data pipelines for your proprietary needs.
        </p>

        <div class="cta-group reveal-item">
          <button type="button" @click="$emit('open-modal')" class="btn btn-primary">
            <span>Explore Dataset Samples</span>
            <div class="btn-icon">
              <i class="ph-light ph-database"></i>
            </div>
          </button>
          <Link href="/demo" class="btn btn-secondary">
            <span>View Demo Dashboard</span>
            <div class="btn-icon">
              <i class="ph-light ph-presentation"></i>
            </div>
          </Link>
        </div>
      </div>

      <!-- Roadmap Carousel Wrapper -->
      <div class="w-full max-w-5xl mx-auto mt-14 sm:mt-auto pb-1 relative z-25">
        <RoadmapCarousel :roadmap-items="roadmapItems" />
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

/* --- Hero Section --- */
.hero {
  height: 100vh;
  width: 100%;
  position: relative;
  z-index: 10;
  padding-top: 3rem;
  padding-bottom: 1rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  scroll-snap-align: start;
  scroll-snap-stop: always;
  box-sizing: border-box;
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

.hero-title {
  font-family: var(--font-display);
  font-size: 4rem;
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -1.5px;
  color: var(--color-text-primary);
  max-width: 950px;
  margin: 0;
  padding: 0;
}

.hero-title .gradient-text {
  background: linear-gradient(135deg, #ff8800 0%, #ffaa44 100%);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-tagline {
  color: var(--color-text-secondary);
  max-width: 800px;
  font-weight: 400;
  line-height: 1.7;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
  margin: 0;
  padding: 0;
}

/* --- Buttons --- */
.cta-group {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  justify-content: center;
  z-index: 10;
  margin: 0;
  padding: 0;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 16px;
  padding: 25px 20px 25px 28px !important;
  border-radius: 100px;
  font-family: var(--font-body);
  font-size: 0.95rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.5s var(--ease-premium);
  cursor: pointer;
  border: 1px solid transparent;
  outline: none;
}

.btn-primary {
  background: var(--color-accent-primary);
  color: #050505 !important;
  box-shadow: 0 10px 30px rgba(255, 136, 0, 0.2);
}

.btn-primary:hover {
  background: rgba(255, 136, 0, 0.15);
  color: var(--color-accent-primary) !important;
  border-color: var(--color-accent-primary);
  transform: translateY(-2px);
  box-shadow: 0 15px 40px rgba(255, 136, 0, 0.35);
}

.btn-icon {
  width: 38px;
  height: 38px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.5s var(--ease-premium);
}

.btn-primary:hover .btn-icon {
  transform: translate(2px, -2px);
  background: rgba(255, 136, 0, 0.25);
  color: var(--color-accent-primary);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.03);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border-outer);
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.05);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--color-accent-primary);
  color: var(--color-accent-primary) !important;
  transform: translateY(-2px);
}

.btn-secondary .btn-icon {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--color-border-outer);
}

.btn-secondary:hover .btn-icon {
  background: rgba(255, 255, 255, 0.15);
  transform: translate(2px, -2px);
}

/* --- Responsive Styles --- */
@media (max-width: 1024px) {
  .hero-title {
    font-size: 3.2rem;
  }
}

@media (max-width: 768px) {
  .hero {
    padding: 120px 0 60px;
    height: auto;
    min-height: auto;
    scroll-snap-align: none;
    scroll-snap-stop: normal;
  }

  .hero-title {
    font-size: 3rem;
    letter-spacing: -1px;
  }

  .cta-group {
    flex-direction: column;
    width: 100%;
    max-width: 320px;
  }

  .btn {
    width: 100%;
    justify-content: space-between;
    white-space: normal;
    text-align: left;
  }
}
</style>
