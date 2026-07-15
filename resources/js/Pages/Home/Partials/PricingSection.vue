<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  },
  sectionHeaders: {
    type: Object,
    default: () => ({})
  }
})

const root = ref(null)

const frequencies = [
  { value: 'monthly', label: 'Monthly' },
  { value: 'annually', label: 'Annually (Save 17%)' },
]

const sectionHeader = (field) => {
  const defaults = {
    eyebrow: 'Data Solutions and IT Consulting',
    title: 'SA Labour Law Datasets & Analytics for Legal, HR, and Data Professionals',
    subtitle: 'Access structured South African public legal data (currently CCMA & Labour Courts, with more arriving soon) via download and API, or explore trends visually using our analytics dashboard.',
  };
  return props.sectionHeaders?.services?.[field] || defaults[field] || '';
};

const frequency = ref(frequencies[0])

const datasets = computed(() => {
  const list = usePage().props.datasets || [];
  if (list.length > 0) {
    return list.map(d => ({
      value: d.slug,
      label: `${d.name} (20+ Years)`,
    }));
  }
  return [
    { value: 'ccma', label: 'CCMA Awards (20+ Years)' },
    { value: 'labour-court', label: 'Labour Court Judgments (20+ Years)' },
  ];
});

const onceOffDataset = ref('ccma')
const developerDataset = ref('ccma')

const onceOffProduct = computed(() => {
  return props.products?.find(p => p.slug === 'once-off-dataset') || {};
});

const developerProduct = computed(() => {
  return props.products?.find(p => p.slug === 'developer-api') || {};
});

const analyticsProduct = computed(() => {
  return props.products?.find(p => p.slug === 'pro-analytics') || {};
});

const pipelineProduct = computed(() => {
  return props.products?.find(p => p.slug === 'managed-data-pipeline') || {};
});

const onceOffPrice = computed(() => {
  const base = onceOffProduct.value.price || 5000;
  if (onceOffDataset.value === 'all') {
    const n = datasets.value.length;
    return n * base - (n - 1) * 500;
  }
  return base;
});

const developerPrice = computed(() => {
  const isMonthly = frequency.value.value === 'monthly';
  const base = developerProduct.value.price || 380;
  const basePrice = isMonthly ? base : base * 10;

  if (developerDataset.value === 'all') {
    const extraDatasets = datasets.value.length - 1;
    const addOnRate = isMonthly ? 100 : 1000;
    return basePrice + extraDatasets * addOnRate;
  }
  return basePrice;
});

const analyticsPrice = computed(() => {
  const isMonthly = frequency.value.value === 'monthly';
  const base = analyticsProduct.value.price || 3800;
  return isMonthly ? base : base * 10;
});

const pipelinePrice = computed(() => {
  const isMonthly = frequency.value.value === 'monthly';
  const base = pipelineProduct.value.price || 19500;
  return isMonthly ? base : base * 10;
});

const formatZAR = (amount) => {
  return 'R' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

const getProductId = (slug) => {
  const p = props.products?.find(product => product.slug === slug);
  if (!p) {
    console.error(`[Error] Product with slug "${slug}" was not found in the database. Please ensure your database is seeded on the VPS by running: php artisan db:seed --class=InitialSeeder`);
    alert(`Product with slug "${slug}" is not configured in the database. Please ensure the database has been seeded on the server.`);
  }
  return p ? p.id : null;
};

const addToCart = (productId, options) => {
  router.post(route('cart.store'), {
    product_id: productId,
    quantity: 1,
    options,
  }, {
    onSuccess: () => {
      router.visit(route('cart.index'));
    }
  });
};

const handlePurchaseOnceOff = () => {
  const id = getProductId('once-off-dataset');
  if (!id) return;
  addToCart(id, { dataset: onceOffDataset.value });
};

const handleSubscribeDeveloper = () => {
  const id = getProductId('developer-api');
  if (!id) return;
  addToCart(id, {
    dataset: developerDataset.value,
    frequency: frequency.value.value
  });
};

const handleSubscribeAnalytics = () => {
  const id = getProductId('pro-analytics');
  if (!id) return;
  addToCart(id, {
    frequency: frequency.value.value
  });
};

onMounted(() => {
  if (datasets.value.length > 0) {
    const hasCcma = datasets.value.some(d => d.value === 'ccma');
    if (!hasCcma) {
      onceOffDataset.value = datasets.value[0].value;
      developerDataset.value = datasets.value[0].value;
    }
  }

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
    <section class="features-section container reveal-item" id="services" aria-label="Datasets Pricing">
      <div class="section-header">
        <div class="eyebrow-badge secondary">
          <span class="eyebrow-dot"></span>
          <span class="eyebrow-text">{{ sectionHeader('eyebrow') }}</span>
        </div>
        <h2 class="section-title">
          {{ sectionHeader('title') }}
        </h2>
        <p class="section-subtitle">
          {{ sectionHeader('subtitle') }}
        </p>
      </div>
      <div class="bento-grid">
        <div class="bezel-card-outer col-span-12 reveal-item">
          <div class="bezel-card-inner">

            <!-- Billing Frequency Toggle -->
            <div class="pricing-toggle-wrapper">
              <div class="pricing-toggle-container">
                <button v-for="option in frequencies" :key="option.value" type="button" class="pricing-toggle-btn"
                  :class="{ active: frequency.value === option.value }" @click="frequency = option">
                  {{ option.label }}
                </button>
              </div>
            </div>

            <!-- Pricing Tiers Grid -->
            <div class="pricing-grid">
              <!-- Card 1: Once-off Dataset -->
              <div class="pricing-card" id="card-dataset">
                <div class="pricing-card-header">
                  <h3 id="tier-dataset" class="pricing-tier-name">{{ onceOffProduct.name }}</h3>
                  <p class="card-desc-small" style="margin-bottom: 20px;">{{ onceOffProduct.description }}</p>
                  <!-- Dataset Selection -->
                  <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                    <label for="dataset-tier-dataset"
                      style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 8px; display: block; font-weight: 500;">Select
                      Dataset:</label>
                    <select id="dataset-tier-dataset" v-model="onceOffDataset"
                      style="width: 100%; padding: 8px 12px !important; border-radius: 8px; background: var(--bg-tertiary); border: 1px solid var(--color-accent-primary); color: var(--color-text-primary); font-size: 0.875rem; font-family: inherit; cursor: pointer; transition: border-color 0.2s;">
                      <option v-for="ds in datasets" :key="ds.value" :value="ds.value"
                        style="background-color: #0a0a0f; color: var(--color-text-primary);">{{ ds.label }}</option>
                      <option value="all" style="background-color: #0a0a0f; color: var(--color-text-primary);">All
                        Datasets</option>
                    </select>
                  </div>

                  <!-- Pricing Value & Period -->
                  <div class="developer-pricing-options">
                    <div class="developer-pricing-option active" style="cursor: default;">
                      <div class="pricing-price-container">
                        <span class="pricing-price-value">{{ formatZAR(onceOffPrice) }}</span>
                        <div class="pricing-price-period">
                          <span class="pricing-price-currency">ZAR</span>
                          <span>Once-off</span>
                        </div>
                      </div>
                      <span class="developer-pricing-label">
                        {{ onceOffDataset === 'all' ? 'All datasets download' : 'Single dataset download' }}
                      </span>
                    </div>
                  </div>
                </div>

                <div style="margin-top: auto;">
                  <!-- Tier Action Button -->
                  <button type="button" @click="handlePurchaseOnceOff" aria-describedby="tier-dataset"
                    class="btn btn-secondary" style="width: 100%; justify-content: center; margin-top: 16px;">
                    <span>Purchase Dataset</span>
                    <div class="btn-icon">
                      <i class="ph-light ph-download-simple"></i>
                    </div>
                  </button>

                  <!-- Tier Highlights -->
                  <ul class="pricing-features-list">
                    <li v-for="(feature, idx) in onceOffProduct.features" :key="idx" class="pricing-feature-item">
                      <i class="ph-light ph-check-circle pricing-feature-icon"></i>
                      <span>{{ feature }}</span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Card 2: Basic API -->
              <div class="pricing-card" id="card-developer">
                <div class="pricing-card-header">
                  <h3 id="tier-developer" class="pricing-tier-name">{{ developerProduct.name }}</h3>
                  <p class="card-desc-small" style="margin-bottom: 20px;">{{ developerProduct.description }}</p>

                  <!-- Dataset Selection -->
                  <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                    <label for="dataset-tier-developer"
                      style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 8px; display: block; font-weight: 500;">Select
                      Dataset:</label>
                    <select id="dataset-tier-developer" v-model="developerDataset"
                      style="width: 100%; padding: 8px 12px !important; border-radius: 8px; background: var(--bg-tertiary); border: 1px solid var(--color-accent-primary); color: var(--color-text-primary); font-size: 0.875rem; font-family: inherit; cursor: pointer; transition: border-color 0.2s;">
                      <option v-for="ds in datasets" :key="ds.value" :value="ds.value"
                        style="background-color: #0a0a0f; color: var(--color-text-primary);">{{ ds.label }}</option>
                      <option value="all" style="background-color: #0a0a0f; color: var(--color-text-primary);">All
                        Datasets</option>
                    </select>
                  </div>

                  <!-- Pricing Value & Period -->
                  <div class="developer-pricing-options">
                    <div class="developer-pricing-option active" style="cursor: default;">
                      <div class="pricing-price-container">
                        <span class="pricing-price-value">{{ formatZAR(developerPrice) }}</span>
                        <div class="pricing-price-period">
                          <span class="pricing-price-currency">ZAR</span>
                          <span>Billed {{ frequency.value }}</span>
                        </div>
                      </div>
                      <span class="developer-pricing-label">Live API & Updates</span>
                    </div>
                  </div>
                </div>

                <div style="margin-top: auto;">
                  <!-- Tier Action Button -->
                  <button type="button" @click="handleSubscribeDeveloper" aria-describedby="tier-developer"
                    class="btn btn-secondary" style="width: 100%; justify-content: center; margin-top: 16px;">
                    <span>Subscribe to Basic API</span>
                    <div class="btn-icon">
                      <i class="ph-light ph-credit-card"></i>
                    </div>
                  </button>

                  <!-- Tier Highlights -->
                  <ul class="pricing-features-list">
                    <li v-for="(feature, idx) in developerProduct.features" :key="idx" class="pricing-feature-item">
                      <i class="ph-light ph-check-circle pricing-feature-icon"></i>
                      <span>{{ feature }}</span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Card 3: Analytics Dashboard -->
              <div class="pricing-card featured" id="card-analytics">
                <div class="pricing-card-header">
                  <h3 id="tier-analytics" class="pricing-tier-name">{{ analyticsProduct.name }}</h3>
                  <p class="card-desc-small" style="margin-bottom: 20px;">{{ analyticsProduct.description }} <br><br>
                  <div style="font-weight: 500; font-size: 0.875rem; color: var(--color-accent-primary);">Subscribe
                    Annually before 31
                    December 2026 and get full access to all current and future released Datasets!</div>
                  </p>
                  <!-- Pricing Value & Period -->
                  <div class="developer-pricing-options">
                    <div class="developer-pricing-option active" style="cursor: default;">
                      <div class="pricing-price-container">
                        <span class="pricing-price-value">{{ formatZAR(analyticsPrice) }}</span>
                        <div class="pricing-price-period">
                          <span class="pricing-price-currency">ZAR</span>
                          <span>Billed {{ frequency.value }}</span>
                        </div>
                      </div>
                      <span class="developer-pricing-label">Full Dashboard & API Access</span>
                    </div>
                  </div>
                </div>

                <div style="margin-top: auto;">
                  <!-- Tier Action Button -->
                  <button type="button" @click="handleSubscribeAnalytics" aria-describedby="tier-analytics"
                    class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 16px;">
                    <span>Subscribe to Pro Analytics</span>
                    <div class="btn-icon">
                      <i class="ph-light ph-credit-card"></i>
                    </div>
                  </button>

                  <!-- Tier Highlights -->
                  <ul class="pricing-features-list">
                    <li v-for="(feature, idx) in analyticsProduct.features" :key="idx" class="pricing-feature-item">
                      <i class="ph-light ph-check-circle pricing-feature-icon"></i>
                      <span>{{ feature }}</span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Card 4: Managed Data Pipeline -->
              <div class="pricing-card" id="card-pipeline">
                <div class="pricing-card-header">
                  <h3 id="tier-pipeline" class="pricing-tier-name">{{ pipelineProduct.name }}</h3>
                  <p class="card-desc-small" style="margin-bottom: 20px;">{{ pipelineProduct.description }}</p>

                  <!-- Pricing Value & Period -->
                  <div class="developer-pricing-options">
                    <div class="developer-pricing-option active" style="cursor: default;">
                      <div class="pricing-price-container">
                        <span class="pricing-price-value">{{ formatZAR(pipelinePrice) }}</span>
                        <div class="pricing-price-period">
                          <span class="pricing-price-currency">ZAR</span>
                          <span>Billed {{ frequency.value }}</span>
                        </div>
                      </div>
                      <span class="developer-pricing-label">Custom Implementation <br>(Retainer @ 20 hours p/m)</span>
                    </div>
                  </div>
                </div>

                <div style="margin-top: auto;">
                  <!-- Tier Action Button -->
                  <Link href="#contact" aria-describedby="tier-pipeline" class="btn btn-secondary"
                    style="width: 100%; justify-content: center; margin-top: 16px;">
                    <span>Enquire Now</span>
                    <div class="btn-icon">
                      <i class="ph-light ph-chat-circle-dots"></i>
                    </div>
                  </Link>

                  <!-- Tier Highlights -->
                  <ul class="pricing-features-list">
                    <li v-for="(feature, idx) in pipelineProduct.features" :key="idx" class="pricing-feature-item">
                      <i class="ph-light ph-check-circle pricing-feature-icon"></i>
                      <span>{{ feature }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="bento-grid">
        <div class="bezel-card-outer col-span-12 reveal-item">
          <div class="bezel-card-inner">
            <div class="card-icon">
              <i class="ph-light ph-shield-check"></i>
            </div>
            <h3 class="card-title">
              Sovereign Data Infrastructure & Business Automation
            </h3>
            <p class="card-desc">
              We design, build, and deploy custom data pipelines, business automation systems, and local AI
              automation systems using a combination of industry leading frameworks and custom software solutions.
            </p>
            <ul class="card-list">
              <li class="card-list-item">
                <i class="ph-light ph-brain card-list-icon"></i>
                <div>
                  <strong>On-Premises AI Model Integration:</strong>
                  Deploy secure, open-source LLMs (like Llama-3, Qwen) on your own hardware. Process sensitive
                  documents,
                  run internal document indexing and search, and automate workflows locally with zero external
                  dependencies.
                </div>
              </li>
              <li class="card-list-item">
                <i class="ph-light ph-plug card-list-icon"></i>
                <div>
                  <strong>Managed Data Acquisition & Pipelines:</strong>
                  Build custom, automated extraction workflows tailored to your specific industry needs. Whether
                  aggregating public web data, monitoring competitor pricing, or integrating fragmented internal
                  databases, we
                  handle the extraction, transformation, and secure routing of structured data directly into your
                  private
                  ecosystem.
                </div>
              </li>
            </ul>
            <Link href="#contact" class="btn btn-primary contact-trigger-btn btn-align-bottom">
              <span>Enquire Now</span>
              <div class="btn-icon">
                <i class="ph-light ph-arrow-right"></i>
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

.eyebrow-badge.secondary {
  background: rgba(141, 215, 218, 0.06);
  border-color: rgba(141, 215, 218, 0.15);
}

.eyebrow-badge.secondary .eyebrow-dot {
  background: var(--color-accent-secondary);
}

.eyebrow-badge.secondary .eyebrow-text {
  color: var(--color-accent-secondary);
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

.col-span-12 {
  grid-column: span 12;
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

.card-icon i {
  font-size: 1.5rem;
  color: var(--color-accent-primary);
  transition: all 0.5s var(--ease-premium);
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

.card-desc-small {
  font-size: 0.9rem;
  color: var(--color-text-secondary);
  line-height: 1.6;
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

/* ── Pricing Section Styles ── */
.pricing-toggle-wrapper {
  display: flex;
  justify-content: center;
  width: 100%;
  margin-top: 24px;
}

.pricing-toggle-container {
  display: inline-flex;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--color-border-outer);
  padding: 4px;
  border-radius: 100px;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.02);
}

.pricing-toggle-btn {
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  font-family: var(--font-body);
  font-size: 0.85rem;
  font-weight: 600;
  padding: 8px 24px;
  border-radius: 100px;
  cursor: pointer;
  transition: all 0.5s var(--ease-premium);
  outline: none;
}

.pricing-toggle-btn:hover {
  color: var(--color-text-primary);
}

.pricing-toggle-btn.active {
  background: var(--color-accent-primary);
  color: #050505;
  box-shadow: 0 4px 15px rgba(255, 136, 0, 0.2);
}

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-top: 40px;
  width: 100%;
  position: relative;
  z-index: 2;
}

.pricing-card {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--color-border-outer);
  border-radius: 20px;
  padding: 32px 16px;
  display: flex;
  flex-direction: column;
  transition: all 0.6s var(--ease-premium);
  position: relative;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.02);
}

.pricing-card:hover {
  transform: translateY(-6px);
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.15);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.05);
}

.pricing-card.featured {
  background: rgba(255, 255, 255, 0.03);
  border-color: rgba(255, 136, 0, 0.25);
  box-shadow: 0 0 40px rgba(255, 136, 0, 0.04), inset 0 1px 1px rgba(255, 255, 255, 0.03);
}

.pricing-card.featured:hover {
  border-color: var(--color-accent-primary);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 40px rgba(255, 136, 0, 0.15), inset 0 1px 1px rgba(255, 255, 255, 0.05);
}

.pricing-card-header {
  margin-bottom: 12px;
}

.pricing-tier-name {
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin-bottom: 8px;
  letter-spacing: 1px;
}

.pricing-card.featured .pricing-tier-name {
  color: var(--color-accent-primary);
}

.pricing-price-container {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-top: 16px;
}

.pricing-price-value {
  font-family: var(--font-display);
  font-size: 2.25rem;
  font-weight: 800;
  color: var(--color-text-primary);
  line-height: 1;
}

.pricing-price-period {
  display: flex;
  flex-direction: column;
  font-size: 0.72rem;
  color: var(--color-text-secondary);
  line-height: 1.3;
}

.pricing-price-currency {
  font-weight: 700;
  color: var(--color-text-primary);
  font-size: 0.8rem;
}

/* ── Developer Pricing Sub-options ── */
.developer-pricing-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 20px;
}

.developer-pricing-option {
  border: 1px solid var(--color-border-outer);
  background: rgba(255, 255, 255, 0.01);
  border-radius: 12px;
  padding: 12px 14px;
  cursor: pointer;
  transition: all 0.4s var(--ease-premium);
  display: flex;
  flex-direction: column;
  position: relative;
  text-align: left;
}

.developer-pricing-option:hover {
  background: rgba(255, 255, 255, 0.03);
  border-color: rgba(255, 255, 255, 0.15);
}

.developer-pricing-option.active {
  border-color: var(--color-accent-primary);
  background: rgba(255, 136, 0, 0.03);
  box-shadow: 0 4px 20px rgba(255, 136, 0, 0.05);
}

.developer-pricing-option.active::after {
  content: '';
  position: absolute;
  top: 14px;
  right: 14px;
  width: 8px;
  height: 8px;
  background: var(--color-accent-primary);
  border-radius: 50%;
  box-shadow: 0 0 8px var(--color-accent-primary);
}

.developer-pricing-option .pricing-price-container {
  margin-top: 0;
  gap: 6px;
  align-items: baseline;
}

.developer-pricing-option .pricing-price-value {
  font-size: 1.8rem;
}

.developer-pricing-option .pricing-price-period {
  font-size: 0.65rem;
}

.developer-pricing-label {
  font-size: 0.68rem;
  color: var(--color-text-secondary);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 2px;
}

.pricing-features-list {
  list-style: none;
  padding: 0;
  margin: 32px 0 0 0;
  border-top: 1px solid var(--color-border-outer);
  padding-top: 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  flex-grow: 1;
}

.pricing-feature-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 0.9rem;
  color: var(--color-text-secondary);
  line-height: 1.4;
}

.pricing-feature-icon {
  color: var(--color-accent-primary);
  font-size: 1.1rem;
  flex-shrink: 0;
  margin-top: 1px;
}

.pricing-card.featured .pricing-feature-icon {
  color: var(--color-accent-secondary);
}

/* ── Pricing Comparison Styles ── */
.comparison-section-container {
  margin-top: 80px;
  padding-top: 2rem;
  border-top: 1px solid var(--color-border-outer);
  width: 100%;
}

.comparison-table-wrapper {
  position: relative;
  margin-top: 24px;
  width: 100%;
}

.comparison-column-bg-container {
  pointer-events: none;
  position: absolute;
  inset: 0;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 32px;
}

.comparison-column-bg {
  height: 100%;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.005);
  border: 1px solid rgba(255, 255, 255, 0.015);
}

.comparison-column-bg.featured {
  background: rgba(255, 255, 255, 0.02);
  border-color: rgba(141, 215, 218, 0.15);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.comparison-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 32px 0;
  position: relative;
  z-index: 1;
}

.comparison-th-empty {
  width: 25%;
}

.comparison-th-tier {
  width: 25%;
  text-align: center;
  padding-bottom: 24px;
}

.comparison-th-tier .pricing-tier-name {
  font-size: 1.1rem;
  margin-bottom: 8px;
}

.comparison-row-header {
  border-bottom: 1px solid var(--color-border-outer);
}

.comparison-section-title {
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-accent-secondary);
  padding: 32px 0 12px 16px;
  text-align: left;
  letter-spacing: 1px;
  text-transform: uppercase;
}

.comparison-row {
  transition: background-color 0.3s ease;
}

.comparison-cell-feature {
  width: 25%;
  padding: 16px 16px;
  text-align: left;
  font-size: 0.9rem;
  color: var(--color-text-primary);
  font-weight: 500;
  position: relative;
}

.comparison-row-divider {
  position: absolute;
  left: 16px;
  right: -96px;
  bottom: 0;
  height: 1px;
  background-color: rgba(255, 255, 255, 0.04);
}

.comparison-cell-value {
  width: 25%;
  text-align: center;
  padding: 16px;
  font-size: 0.9rem;
  color: var(--color-text-secondary);
}

.comparison-cell-value.featured {
  color: var(--color-text-primary);
  font-weight: 600;
}

.comparison-cell-value .icon-check {
  color: var(--color-accent-secondary);
  font-size: 1.25rem;
}

.comparison-cell-value .icon-cross {
  color: var(--color-text-muted);
  font-size: 1.25rem;
  opacity: 0.4;
}

/* ── Mobile Comparison Styles ── */
.mobile-comparison-list {
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.mobile-comparison-tier {
  border: 1px solid var(--color-border-outer);
  border-radius: 20px;
  padding: 24px;
  background: rgba(255, 255, 255, 0.01);
}

.mobile-comparison-tier.featured {
  border-color: rgba(255, 136, 0, 0.25);
  background: rgba(255, 255, 255, 0.02);
}

.mobile-tier-header {
  margin-bottom: 24px;
  border-bottom: 1px solid var(--color-border-outer);
  padding-bottom: 16px;
}

.mobile-tier-title {
  font-family: var(--font-display);
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin-bottom: 8px;
}

.mobile-comparison-tier.featured .mobile-tier-title {
  color: var(--color-accent-primary);
}

.mobile-tier-desc {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.mobile-sections-list {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.mobile-section-title {
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-accent-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
}

.mobile-features-box {
  background: rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  overflow: hidden;
}

.mobile-features-box.featured {
  border-color: rgba(255, 136, 0, 0.1);
}

.mobile-features-dl {
  margin: 0;
  padding: 0;
}

.mobile-feature-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
}

.mobile-feature-row:last-child {
  border-bottom: none;
}

.mobile-feature-name {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  text-align: left;
}

.mobile-feature-value {
  margin: 0;
}

.mobile-feature-value .value-text {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.mobile-feature-value .value-text.featured {
  color: var(--color-text-primary);
  font-weight: 600;
}

.mobile-feature-value .icon-check {
  color: var(--color-accent-secondary);
  font-size: 1.1rem;
}

.mobile-feature-value .icon-cross {
  color: var(--color-text-muted);
  font-size: 1.1rem;
  opacity: 0.4;
}

/* ── Responsive adjustments ── */
@media (max-width: 1024px) {
  .pricing-grid {
    grid-template-columns: 1fr;
    margin-left: auto;
    margin-right: auto;
  }
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

  .col-span-12 {
    grid-column: span 1 !important;
  }
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

@media (prefers-reduced-motion: reduce) {
  .reveal-item {
    opacity: 1 !important;
    transform: none !important;
    filter: none !important;
    transition: none !important;
  }
}
</style>
