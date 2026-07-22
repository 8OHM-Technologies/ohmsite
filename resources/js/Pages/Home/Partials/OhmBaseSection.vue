<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  sectionHeaders: {
    type: Object,
    default: () => ({})
  }
})

const root = ref(null)

const sectionHeader = (field) => {
  const defaults = {
    eyebrow: 'DIY Smart Home Blueprints',
    title: 'OhmBase',
    subtitle: 'The business data architectures we build are born from our philosophy of absolute digital sovereignty. Explore our open-source blueprints and Smart Home/IoT hardware store, designed to help individuals build their own self-hosted, cloud-free smart home ecosystems.',
  };
  return props.sectionHeaders?.ohmbase?.[field] || defaults[field] || '';
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
    <section class="features-section container reveal-item" id="ohmbase" aria-label="Consumer Bento Grid">
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
        <div class="bezel-card-outer col-span-7 reveal-item">
          <div class="bezel-card-inner">
            <div class="card-icon">
              <i class="ph-light ph-book-open"></i>
            </div>
            <h3 class="card-title">
              OhmBase: DIY Smart Home Blueprints
            </h3>
            <p class="card-desc">
              We provide the knowledge and tools for you to
              host your own digital life. Our blueprints guide
              you through deploying everything you need from
              Home Assistant, local AI agents, secure media
              servers and more. All without mandatory cloud
              lock-in or ring-fenced ecosystems.
            </p>
            <ul class="card-list" style="margin-bottom: 20px;">
              <li class="card-list-item">
                <i class="ph-light ph-brain card-list-icon"></i>
                <div>
                  <strong>Local AI & Automation:</strong>
                  Lay the foundation to run personalized
                  AI agents and complex automations that
                  process entirely within the walls of
                  your home.
                </div>
              </li>
              <li class="card-list-item">
                <i class="ph-light ph-plug card-list-icon"></i>
                <div>
                  <strong>Legacy Integration:</strong>
                  Safely bridge older alarms, gate motors,
                  and sensors directly into your
                  self-hosted network.
                </div>
              </li>
            </ul>
            <Link href="#contact" class="btn btn-primary contact-trigger-btn btn-align-bottom">
              <span>Launching Soon</span>
              <div class="btn-icon">
                <i class="ph-light ph-arrow-right"></i>
              </div>
            </Link>
          </div>
        </div>

        <div class="bezel-card-outer col-span-5 reveal-item accent-glow">
          <div class="bezel-card-inner">
            <div class="card-icon">
              <i class="ph-light ph-shopping-bag"></i>
            </div>
            <h3 class="card-title">Smart Home & IoT Hardware Shop</h3>
            <p class="card-desc">
              To support our blueprints we are building an online shop, which will stock only open-standard, smart
              home, IoT and other components.
            </p>
            <div
              style="margin-top: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 600; color: var(--color-accent-secondary);">
              <span class="status-dot"
                style="width: 6px; height: 6px; background: var(--color-accent-secondary); border-radius: 50%; box-shadow: 0 0 6px var(--color-accent-secondary);"></span>
              Shop Launching Soon! Join the Waitlist to get notified...
            </div>
            <Link href="#contact" class="btn btn-secondary contact-trigger-btn btn-align-bottom">
              <span>Join the Waitlist</span>
              <div class="btn-icon">
                <i class="ph-light ph-bell"></i>
              </div>
            </Link>
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

.col-span-7 {
  grid-column: span 7;
}

.col-span-5 {
  grid-column: span 5;
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

.bezel-card-outer.accent-glow:hover {
  border-color: rgba(141, 215, 218, 0.3);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6), 0 0 50px rgba(141, 215, 218, 0.05);
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

.card-icon {
  width: 52px;
  height: 52px;
  background: rgba(255, 136, 0, 0.04);
  border: 1px solid rgba(255, 136, 0, 0.15);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 28px;
  transition: all 0.5s var(--ease-premium);
}

.bezel-card-outer:hover .card-icon {
  background: var(--color-accent-primary);
  border-color: var(--color-accent-primary);
  box-shadow: 0 0 15px rgba(255, 136, 0, 0.3);
}

.bezel-card-outer:hover .card-icon i {
  color: #050505;
}

.bezel-card-outer.accent-glow .card-icon {
  background: rgba(141, 215, 218, 0.04);
  border-color: rgba(141, 215, 218, 0.15);
}

.bezel-card-outer.accent-glow:hover .card-icon {
  background: var(--color-accent-secondary);
  border-color: var(--color-accent-secondary);
  box-shadow: 0 0 15px rgba(141, 215, 218, 0.3);
}

.card-icon i {
  font-size: 1.5rem;
  color: var(--color-accent-primary);
  transition: all 0.5s var(--ease-premium);
}

.bezel-card-outer.accent-glow .card-icon i {
  color: var(--color-accent-secondary);
}

.bezel-card-outer.accent-glow:hover .card-icon i {
  color: #050505;
}

.card-title {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin-bottom: 16px;
}

.card-desc {
  color: var(--color-text-secondary);
  font-size: 0.95rem;
  line-height: 1.6;
  margin-top: 10px !important;
  margin-bottom: 10px !important;
  max-width: 800px;
}

.card-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 10px !important;
  margin-bottom: 10px !important;
  text-align: left;
}

.card-list-item {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  font-size: 0.9rem;
  color: var(--color-text-secondary);
}

.card-list-icon {
  color: var(--color-accent-primary);
  font-size: 1.2rem;
  flex-shrink: 0;
  margin-top: 2px;
}

.bezel-card-outer.accent-glow .card-list-icon {
  color: var(--color-accent-secondary);
}

.card-list-item strong {
  color: var(--color-text-primary);
}

/* --- Buttons --- */
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

.btn-align-bottom {
  align-self: flex-end;
  margin-top: auto;
  width: fit-content;
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

  .btn {
    width: 100%;
    justify-content: space-between;
    white-space: normal;
    text-align: left;
  }

  .bento-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .col-span-7,
  .col-span-5 {
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
