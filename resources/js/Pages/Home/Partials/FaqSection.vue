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
    eyebrow: 'FAQs',
    title: 'Frequently Asked Questions',
    subtitle: '',
  };
  return props.sectionHeaders?.faq?.[field] || defaults[field] || '';
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
    <section class="faq-section container reveal-item" id="faq" aria-label="Technical FAQs">
      <div class="section-header">
        <div class="eyebrow-badge">
          <span class="eyebrow-dot"></span>
          <span class="eyebrow-text">{{ sectionHeader('eyebrow') }}</span>
        </div>
        <h2 class="section-title section-title-large">
          {{ sectionHeader('title') }}
        </h2>
      </div>

      <div class="bento-grid">
        <!-- Row 1 -->
        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-database"></i>
              What public data sources do you currently provide?
            </h4>
            <p class="faq-answer">
              Our primary focus is on South African legal and labour data. We currently provide comprehensive,
              structured datasets from the CCMA and Labour Courts, with Bargaining Council resolutions and High Court
              judgments on our roadmap.
            </p>
          </div>
        </div>
        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-shield-check"></i>
              Is the dataset compliant with POPIA and privacy laws?
            </h4>
            <p class="faq-answer">
              Yes. We apply rigorous sanitization, anonymization, and data cleaning processes to our ingestion
              pipelines, ensuring that all structured legal datasets fully comply with the Protection of Personal
              Information Act (POPIA).
            </p>
          </div>
        </div>

        <!-- Row 2 -->
        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-clock"></i>
              How frequently is the API and Dashboard data updated?
            </h4>
            <p class="faq-answer">
              Our data pipelines run continuously. As soon as new judgments or arbitration awards are published on
              public portals, they are extracted, cleaned, and immediately made available through our Developer API
              and Pro Dashboard.
            </p>
          </div>
        </div>
        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-code"></i>
              Do I need to be a developer to access the datasets?
            </h4>
            <p class="faq-answer">
              Not at all. While we offer a powerful Developer API for custom integrations, our Pro Dashboard provides
              a complete no-code solution for HR and legal professionals to visually analyze trends and export data to
              CSV or Excel.
            </p>
          </div>
        </div>

        <!-- Row 3 -->
        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-factory"></i>
              Can you extract custom data specific to our industry?
            </h4>
            <p class="faq-answer">
              Absolutely. Through our Managed Data Pipeline tier, we design and build bespoke web scraping, data
              extraction, and ETL workflows tailored to your operational needs, delivering structured data directly
              into your private ecosystem.
            </p>
          </div>
        </div>
        <div class="bezel-card-outer col-span-6 reveal-item">
          <div class="bezel-card-inner">
            <h4 class="card-title-small">
              <i class="ph-light ph-tree-structure"></i>
              How do the consumer (OhmBase) and business divisions relate?
            </h4>
            <p class="faq-answer">
              Both share our core philosophy of digital sovereignty. OhmBase is our open-source initiative empowering
              individuals to self-host secure smart homes, while our business division delivers professional-grade
              public data pipelines and IT consulting.
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

.section-title-large {
  font-size: 3rem;
  line-height: 1.2;
  margin: 16px 0;
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

/* --- FAQ Grid --- */
.faq-section {
  height: 100%;
  width: 100%;
  padding-bottom: 2rem;
  scroll-snap-align: start;
  scroll-snap-stop: always;
}

.faq-answer {
  font-size: 0.9rem;
  color: var(--color-text-secondary);
  line-height: 1.6;
  padding-left: 10px;
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
  .faq-section {
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
