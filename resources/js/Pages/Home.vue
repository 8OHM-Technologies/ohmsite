<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Navbar from '@/Components/Navbar.vue'

const frequencies = [
  { value: 'monthly', label: 'Monthly' },
  { value: 'annually', label: 'Annually (Save 17%)' },
]
const tiers = [
  {
    name: 'Developer API',
    id: 'tier-developer',
    href: '#',
    featured: false,
    description: 'Power your custom applications or train AI models with direct access to our structured legal dataset feed. Includes 20+ years of historical cases and active live updates.',
    price: { monthly: 'R380', annually: 'R3,800' },
    onceOffPrice: 'R5,000',
    highlights: [
      '20+ Years of Historical Legal Data',
      'Live API access to new court cases and decisions',
      'Structured JSON payloads & REST endpoints',
      'Standard rate limits (1000 req/month)',
      'Standard rates on new datasets',
      'Standard Helpdesk Ticket Support',
      'POPIA Compliant Data Entries',
    ],
  },
  {
    name: 'Pro Dashboard',
    id: 'tier-pro',
    href: '#',
    featured: true,
    description: 'No code required. Access to the Legal Dataset through our interactive analytics dashboard. Includes standard API access and unlimited access to the archive dataset.',
    price: { monthly: 'R3,500', annually: 'R35,000' },
    highlights: [
      'Includes all Developer API features',
      'No-code Interactive Analytics Dashboard',
      'Visual trend analysis & CSV/Excel exports',
      'Expanded API Endpoint Catalogue',
      'Increased rate limits (3000 req/month)',
      'Discounted access to new Datasets as Add-ons to the Pro Dashboard',
      'Priority Helpdesk Ticket Support',
      'POPIA Compliant Data Entries',
    ],
  },
  {
    name: 'Managed Data Pipeline',
    id: 'tier-pipeline',
    href: '#contact',
    featured: false,
    description: 'Build custom, automated extraction workflows tailored to your specific industry needs. We handle the extraction, transformation, and secure routing of structured data directly into your private ecosystem.',
    price: { monthly: 'R16,500', annually: 'R165,000' },
    highlights: [
      'Deployed on your own on-premises or cloud infrastructure',
      'Custom web scraping and ETL data engineering',
      'Private AI models (LLMs) & secure processing for sensitive data',
      'Direct influence over our data product roadmap',
      '24/7 Dedicated Priority Support & SLA',
      'POPIA Compliant Data Entries',
    ],
  },
];

const roadmap = [
  {
    status: 'Live',
    date: 'Current',
    title: 'CCMA Arbitration Awards',
    description: 'Over 20 years of sanitized, structured CCMA awards with daily live updates.',
    icon: 'ph-check-circle',
    iconClass: 'text-green-500'
  },
  {
    status: 'Beta',
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
];

const frequency = ref(frequencies[0])
const developerOption = ref('subscription')

const props = defineProps({
  auth: Object,
});

const showSuccess = ref(false)
const isSending = ref(false)
const formError = ref('')

const form = reactive({
  name: '',
  email: '',
  division: '',
  message: ''
})

const orb1 = ref(null)
const orb2 = ref(null)
const orb3 = ref(null)
let revealObserver = null

const resetForm = () => {
  showSuccess.value = false
  formError.value = ''
  form.name = ''
  form.email = ''
  form.message = ''
  form.division = ''
}

const handleSubmit = async () => {
  formError.value = ''
  if (!form.name || !form.email || !form.division || !form.message) {
    formError.value = 'Please fill out all required fields.'
    return
  }

  isSending.value = true
  try {
    const response = await fetch('https://control-plane.8ohm.co.za/api/send-website-enquiry', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        name: form.name,
        email: form.email,
        division: form.division,
        message: form.message
      })
    })

    const result = await response.json()
    if (response.ok) {
      showSuccess.value = true
    } else {
      formError.value = result.message || 'Something went wrong. Please try again.'
    }
  } catch (err) {
    console.error('Submission error:', err)
    formError.value = 'Failed to connect to the server. Please check your connection and try again.'
  } finally {
    isSending.value = false
  }
}

const goToDashboard = () => {
  window.location.href = '/dashboard/index.html'
}

const handleMouseMove = (e) => {
  const x = e.clientX / window.innerWidth
  const y = e.clientY / window.innerHeight

  const list = [orb1.value, orb2.value, orb3.value]
  list.forEach((orb, index) => {
    if (!orb) return
    const speed = (index + 1) * 20
    const dx = (x - 0.5) * speed
    const dy = (y - 0.5) * speed
    orb.style.transform = `translate(${dx}px, ${dy}px)`
  })
}

const checkHashAndParams = () => {
  const params = new URLSearchParams(window.location.search)
  if (params.get('contact') === 'true' || window.location.hash === '#contact' || window.location.hash === '#enquire') {
    //Go to page location
  }
}

onMounted(() => {
  // Add body class for scroll snapping
  document.body.classList.add('home-page-active')
  document.documentElement.classList.add('home-page-active')

  // Check URL params/hash for contact modal
  checkHashAndParams()
  window.addEventListener('hashchange', checkHashAndParams)

  // Mouse events
  document.addEventListener('mousemove', handleMouseMove)

  // Setup Reveal Intersection Observer
  const options = {
    root: null,
    rootMargin: '0px',
    threshold: 0.1
  }

  revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('revealed')
        }, index * 80)
        observer.unobserve(entry.target)
      }
    })
  }, options)

  document.querySelectorAll('.reveal-item').forEach((item) => {
    revealObserver.observe(item)
  })
})

onUnmounted(() => {
  document.body.classList.remove('home-page-active')
  document.documentElement.classList.remove('home-page-active')
  window.removeEventListener('hashchange', checkHashAndParams)
  document.removeEventListener('mousemove', handleMouseMove)
  if (revealObserver) {
    revealObserver.disconnect()
  }
})
</script>

<template>

  <Head title="Host Your World. Own Your Data." />

  <div class="home-page-container">
    <div class="background-visuals">
      <div class="glow-orb orb-1" ref="orb1"></div>
      <div class="glow-orb orb-2" ref="orb2"></div>
      <div class="glow-orb orb-3" ref="orb3"></div>
      <div class="grid-overlay"></div>
      <div class="noise-overlay"></div>
    </div>

    <Navbar :auth="auth" />

    <main>
      <section class="hero container" id="home">
        <div class="eyebrow-badge reveal-item">
          <span class="eyebrow-dot"></span>
          <span class="eyebrow-text">End-to-end Data Solutions</span>
        </div>

        <h1 class="hero-title reveal-item">
          Turn Fragmented Data<br />
          <span class="gradient-text">into Analytical Advantage</span>
        </h1>

        <p class="hero-tagline reveal-item">
          South African public data sources are notoriously unstable and difficult to extract. We handle the extraction,
          cleaning, and structuring of public records—starting with the CCMA and Labour Courts, and expanding soon to
          High Courts and beyond. Get reliable,
          POPIA-compliant data feeds directly via API, track judicial trends using our Analytics Dashboard, or let us
          build custom data pipelines for your proprietary needs.
        </p>

        <div class="cta-group reveal-item">
          <a href="#services" class="btn btn-primary">
            <span>Explore Data Services</span>
            <div class="btn-icon">
              <i class="ph-light ph-database"></i>
            </div>
          </a>
          <a href="#demo" class="btn btn-secondary">
            <span>View Demo Dashboard</span>
            <div class="btn-icon">
              <i class="ph-light ph-presentation"></i>
            </div>
          </a>
        </div>

        <!-- Trust & Provenance Banner -->
        <div class="trust-banner container reveal-item" id="trust-banner">
          <div class="trust-banner-inner">
            <span class="trust-title">Ingesting From The Following Public Sources:</span>
            <div class="trust-logos">
              <div class="trust-logo-item">
                <i class="ph-light ph-scales"></i>
                <span>saflii.org</span>
              </div>
              <div class="trust-logo-item">
                <i class="ph-light ph-briefcase"></i>
                <span>judiciary.org.za</span>
              </div>
              <div class="trust-logo-item">
                <i class="ph-light ph-scales"></i>
                <span>statssa.gov.za</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="features-section container reveal-item" id="services" aria-label="Datasets Pricing">
        <div class="section-header">
          <div class="eyebrow-badge secondary">
            <span class="eyebrow-dot"></span>
            <span class="eyebrow-text">Data Solutions and IT Consulting</span>
          </div>
          <h2 class="section-title">
            SA Labour Law Datasets & Analytics for Legal, HR, and Data Professionals
          </h2>
          <p class="section-subtitle">
            Access structured South African public legal data (currently CCMA & Labour Courts, with more arriving soon)
            via download and API, or explore trends visually using our analytics dashboard.
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
                <div v-for="tier in tiers" :key="tier.id" class="pricing-card" :class="{ featured: tier.featured }">
                  <div class="pricing-card-header">
                    <h3 :id="tier.id" class="pricing-tier-name">{{ tier.name }}</h3>
                    <p class="card-desc-small" style="margin-bottom: 20px;">{{ tier.description }}</p>

                    <!-- Dataset Selection -->
                    <div v-if="tier.name === 'Developer API' || tier.name === 'Pro Dashboard'" class="form-group"
                      style="margin-bottom: 20px; text-align: left;">
                      <label :for="'dataset-' + tier.id"
                        style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 8px; display: block; font-weight: 500;">Select
                        Dataset:</label>
                      <select :id="'dataset-' + tier.id"
                        style="width: 100%; padding: 10px 12px; border-radius: 8px; background: var(--bg-tertiary); border: 1px solid var(--color-accent-primary); color: var(--text-primary); font-size: 0.875rem; font-family: inherit; cursor: pointer; transition: border-color 0.2s;">
                        <option value="ccma" selected>CCMA/Labour Court Judgments</option>
                      </select>
                    </div>

                    <!-- Pricing Value & Period -->
                    <div v-if="tier.name === 'Developer API'" class="developer-pricing-options">
                      <!-- Subscription Option -->
                      <div class="developer-pricing-option" :class="{ active: developerOption === 'subscription' }"
                        @click="developerOption = 'subscription'">
                        <div class="pricing-price-container">
                          <span class="pricing-price-value">{{ tier.price[frequency.value] }}</span>
                          <div class="pricing-price-period">
                            <span class="pricing-price-currency">ZAR</span>
                            <span>Billed {{ frequency.value }}</span>
                          </div>
                        </div>
                        <span class="developer-pricing-label">Live API & Updates</span>
                      </div>

                      <!-- Once-off Option -->
                      <div class="developer-pricing-option" :class="{ active: developerOption === 'once-off' }"
                        @click="developerOption = 'once-off'">
                        <div class="pricing-price-container">
                          <span class="pricing-price-value">{{ tier.onceOffPrice }}</span>
                          <div class="pricing-price-period">
                            <span class="pricing-price-currency">ZAR</span>
                            <span>Once-off fee</span>
                          </div>
                        </div>
                        <span class="developer-pricing-label">20y Archive Download</span>
                      </div>
                    </div>

                    <!-- Default for Pro & Managed Data Pipeline -->
                    <div v-else class="developer-pricing-options">
                      <div class="developer-pricing-option active" style="cursor: default;">
                        <div class="pricing-price-container">
                          <span v-if="tier.name === 'Managed Data Pipeline'"
                            style="font-size: 0.875rem; color: var(--text-secondary); margin-right: 8px; align-self: center; font-weight: 500;">20
                            hours per month retainer</span>
                          <span class="pricing-price-value">{{ tier.price[frequency.value] }}</span>
                          <div class="pricing-price-period">
                            <span class="pricing-price-currency">ZAR</span>
                            <span>Billed {{ frequency.value }}</span>
                          </div>
                        </div>
                        <span class="developer-pricing-label">{{ tier.name === 'Pro Dashboard' ? 'Full Dashboard Access'
                          : 'Custom Implementation' }}</span>
                      </div>
                    </div>
                  </div>

                  <div style="margin-top: auto;">
                    <!-- Tier Action Button -->
                    <a v-if="tier.name === 'Developer API'"
                      :href="developerOption === 'subscription' ? tier.href : tier.href + '-archive'"
                      :aria-describedby="tier.id" class="btn btn-secondary"
                      style="width: 100%; justify-content: center; margin-top: 16px;">
                      <span>{{ developerOption === 'subscription' ? 'Subscribe to Live API' : 'Buy Historical Archive'
                        }}</span>
                      <div class="btn-icon">
                        <i class="ph-light"
                          :class="developerOption === 'subscription' ? 'ph-rss' : 'ph-download-simple'"></i>
                      </div>
                    </a>
                    <a v-else :href="tier.href" :aria-describedby="tier.id" class="btn"
                      :class="[tier.featured ? 'btn-primary' : 'btn-secondary']"
                      style="width: 100%; justify-content: center; margin-top: 16px;">
                      <span>{{ tier.name === 'Managed Data Pipeline' ? 'Enquire Now' : 'Subscribe to ' + tier.name
                      }}</span>
                      <div class="btn-icon">
                        <i class="ph-light"
                          :class="tier.name === 'Managed Data Pipeline' ? 'ph-chat-circle-dots' : 'ph-credit-card'"></i>
                      </div>
                    </a>

                    <!-- Tier Highlights -->
                    <ul class="pricing-features-list">
                      <li v-for="mainFeature in tier.highlights" :key="mainFeature" class="pricing-feature-item">
                        <i class="ph-light ph-check-circle pricing-feature-icon"></i>
                        <span>{{ mainFeature }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="section-header" style="text-align: center;">
                <h3 class="section-title" style="font-size: 1.5rem;">Product Release Roadmap</h3>
                <p class="section-subtitle">See what is currently available and what we are building next.</p>
              </div>

              <!-- Roadmap Carousel -->
              <div id="multi-slide" data-carousel='{ "loadingClasses": "opacity-0", "slidesQty": { "xs": 1, "lg": 3 } }'
                class="relative w-full mt-4">
                <div class="carousel h-auto">
                  <div class="carousel-body h-full opacity-0">
                    <div v-for="item in roadmap" :key="item.title" class="carousel-slide">
                      <div class="roadmap-card h-full mx-2"
                        style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; position: relative; overflow: hidden; display: flex; flex-direction: column;">

                        <!-- Status Badge -->
                        <div class="roadmap-status-badge"
                          style="display: inline-flex; align-self: flex-start; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px; padding: 4px 10px; border-radius: 9999px; background: var(--bg-tertiary);">
                          <i class="ph-fill" :class="[item.icon, item.iconClass]"></i>
                          <span>{{ item.status }} &bull; {{ item.date }}</span>
                        </div>

                        <h4 class="roadmap-title" style="font-size: 1.125rem; font-weight: 600; margin-bottom: 8px;">
                          {{ item.title }}
                        </h4>

                        <p class="roadmap-desc"
                          style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5;">
                          {{ item.description }}
                        </p>

                      </div>
                    </div>
                  </div>
                </div>

                <!-- Previous Slide -->
                <button type="button"
                  class="carousel-prev start-0 max-sm:start-1 carousel-disabled:opacity-50 w-10 h-10 flex items-center justify-center rounded-full shadow-sm"
                  style="background: var(--bg-tertiary); border: 1px solid var(--border-color);">
                  <i class="ph-light ph-caret-left" style="font-size: 3rem; color: var(--text-primary);"></i>
                  <span class="sr-only">Previous</span>
                </button>
                <!-- Next Slide -->
                <button type="button"
                  class="carousel-next end-1 max-sm:end-1 carousel-disabled:opacity-50 w-10 h-10 flex items-center justify-center rounded-full shadow-sm"
                  style="background: var(--bg-tertiary); border: 1px solid var(--border-color);">
                  <i class="ph-light ph-caret-right" style="font-size: 3rem; color: var(--text-primary);"></i>
                  <span class="sr-only">Next</span>
                </button>
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
                We design, build, and deploy custom data pipelines, business , and local AI
                automation systems using a combination of industry leading frameworks and custom software solutions.
              </p>
              <p class="card-desc">

              </p>
              <ul class="card-list">
                <li class="card-list-item">
                  <i class="ph-light ph-brain card-list-icon"></i>
                  <div>
                    <strong>On-Premises AI Model Integration:</strong>
                    Deploy secure, open-source LLMs (like Llama-3, Qwen) on your own hardware. Process sensitive
                    documents,
                    run
                    internal document indexing and search, and automate workflows locally with zero external
                    dependencies.
                  </div>
                </li>
                <li class="card-list-item">
                  <i class="ph-light ph-plug card-list-icon"></i>
                  <div>
                    <strong>Managed Data Acquisition & Pipelines:</strong>
                    Build custom, automated extraction workflows tailored to your specific industry needs. Whether
                    aggregating
                    public web data, monitoring competitor pricing, or integrating fragmented internal databases, we
                    handle
                    the extraction, transformation, and secure routing of structured data directly into your private
                    ecosystem.
                  </div>
                </li>
              </ul>
              <a href="#contact" class="btn btn-primary contact-trigger-btn btn-align-bottom">
                <span>Enquire Now</span>
                <div class="btn-icon">
                  <i class="ph-light ph-arrow-right"></i>
                </div>
              </a>
            </div>
          </div>
        </div>
      </section>

      <section class="features-section container reveal-item" id="philosophy" aria-label="Our Philosophy">
        <div class="section-header">
          <div class="eyebrow-badge">
            <span class="eyebrow-dot"></span>
            <span class="eyebrow-text">Why Infinity Ohm?</span>
          </div>
          <h2 class="section-title">Our Philosophy</h2>
          <p class="section-subtitle">
            Strategic engineering, absolute data ownership, and open standards.
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

      <section class="features-section container reveal-item" id="ohmbase" aria-label="Consumer Bento Grid">
        <div class="section-header">
          <div class="eyebrow-badge">
            <span class="eyebrow-dot"></span>
            <span class="eyebrow-text">DIY Smart Home Blueprints</span>
          </div>
          <h2 class="section-title">OhmBase</h2>
          <p class="section-subtitle">
            The business data architectures we build are born from our philosophy of absolute digital sovereignty.
            Explore our open-source blueprints and Smart Home/IoT hardware store, designed to help individuals build
            their own
            self-hosted, cloud-free smart home ecosystems.
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
              <ul class="card-list">
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
              <a href="#contact" class="btn btn-primary contact-trigger-btn btn-align-bottom">
                <span>Launching Soon</span>
                <div class="btn-icon">
                  <i class="ph-light ph-arrow-right"></i>
                </div>
              </a>
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
              <a href="#contact" class="btn btn-secondary contact-trigger-btn btn-align-bottom">
                <span>Join the Waitlist</span>
                <div class="btn-icon">
                  <i class="ph-light ph-bell"></i>
                </div>
              </a>
            </div>
          </div>
        </div>
      </section>

      <section class="faq-section container reveal-item" id="faq" aria-label="Technical FAQs">
        <div class="section-header">
          <div class="eyebrow-badge">
            <span class="eyebrow-dot"></span>
            <span class="eyebrow-text">FAQs</span>
          </div>
          <h2 class="section-title section-title-large">
            Frequently Asked Questions
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

      <section class="contact-section container reveal-item" id="contact" aria-label="Enquire Now">
        <div class="section-header">
          <div class="eyebrow-badge">
            <span class="eyebrow-dot"></span>
            <span class="eyebrow-text">Contact</span>
          </div>
          <h2 class="section-title section-title-large">
            Enquire Now
          </h2>
          <p class="section-subtitle">
            Have questions about our South African public data feeds, custom analytics dashboards, or DIY hardware
            waitlists? Reach out below.
          </p>
        </div>

        <div class="contact-grid">
          <div class="bezel-card-outer reveal-item">
            <div class="bezel-card-inner">
              <div v-show="!showSuccess" id="contactFormWrapper">
                <form @submit.prevent="handleSubmit" class="contact-form">
                  <div class="form-status error" :class="{ active: formError }" id="formError">
                    <i class="ph-light ph-warning-circle"></i>
                    <span class="error-text">{{ formError }}</span>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label for="name">Your name?</label>
                      <input type="text" id="name" name="name" required placeholder="John Doe" v-model="form.name" />
                    </div>

                    <div class="form-group">
                      <label for="email">How do we get in touch?</label>
                      <input type="email" id="email" name="email" required placeholder="example@email.com"
                        v-model="form.email" />
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="division">What is it about?</label>
                    <select id="division" name="division" required v-model="form.division">
                      <option value="" disabled selected>Choose an option...</option>
                      <option value="dataset-dump-request">Once-off Dataset Enquiry</option>
                      <option value="pro-dashboard-request">Pro Dashboard Enquiry</option>
                      <option value="custom-pipeline">Custom Managed Data Pipeline Enquiry</option>
                      <option value="shop-waitlist">Join the OhmBase/Hardware Shop Waitlist</option>
                      <option value="general">General Enquiry</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="message">How can we help you?</label>
                    <textarea id="message" name="message" rows="4" required placeholder="Detail your requirements..."
                      v-model="form.message"></textarea>
                  </div>

                  <button type="submit" class="btn btn-primary submit-btn" :disabled="isSending"
                    :style="isSending ? { opacity: 0.7 } : {}">
                    <span>{{ isSending ? 'Sending...' : 'Send Message' }}</span>
                    <div class="btn-icon">
                      <i class="ph-light ph-paper-plane-tilt"></i>
                    </div>
                  </button>
                </form>
              </div>

              <div v-show="showSuccess" class="form-status-success active" id="successMessage">
                <div class="success-icon">
                  <i class="ph-light ph-check-circle"></i>
                </div>
                <h3 class="success-title">Enquiry Sent</h3>
                <p class="success-desc">
                  Thank you for your enquiry. Our team will get back to you within 48 hours.
                </p>
                <button type="button" class="btn btn-primary submit-btn" id="successCloseBtn" @click="resetForm">
                  <span>Send Another Message</span>
                  <div class="btn-icon">
                    <i class="ph-light ph-paper-plane-tilt"></i>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer>
      <div class="container">
        <p class="footer-text">
          &copy; 2026 Infinity Ohm Technologies (Pty) Ltd t/a 8OHM. All rights
          reserved.<br />
          <a href="/privacy-policy" target="_blank">Privacy Policy</a>
        </p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.home-page-container {
  font-family: var(--font-body);
  background-color: var(--color-bg);
  color: var(--color-text-primary);
  line-height: 1.6;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
  min-height: 100vh;
}

.home-page-container,
.home-page-container *,
.home-page-container *::before,
.home-page-container *::after {
  box-sizing: border-box;
}

:global(html.home-page-active) {
  overflow-y: scroll;
}

:global(html.home-page-active),
:global(body.home-page-active) {
  scroll-snap-type: y proximity;
  scroll-padding-top: 100px;
}

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

.section-subtitle {
  color: var(--color-text-secondary);
  max-width: 800px;
  margin: 8px auto 0;
}

/* --- Background Visuals --- */
.background-visuals {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.glow-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(130px);
  opacity: 0.12;
  mix-blend-mode: screen;
  will-change: transform;
  transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.orb-1 {
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, var(--color-accent-primary) 0%, rgba(255, 136, 0, 0) 70%);
  top: -15%;
  left: -10%;
}

.orb-2 {
  width: 700px;
  height: 700px;
  background: radial-gradient(circle, var(--color-accent-secondary) 0%, rgba(141, 215, 218, 0) 70%);
  bottom: -15%;
  right: -10%;
}

.orb-3 {
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, #ff3c00 0%, rgba(255, 60, 0, 0) 70%);
  top: 40%;
  left: 35%;
}

.grid-overlay {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(to right, rgba(255, 255, 255, 0.015) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
  background-size: 64px 64px;
  mask-image: radial-gradient(circle at center, black 40%, transparent 95%);
  -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 95%);
}

.noise-overlay {
  position: absolute;
  inset: 0;
  opacity: 0.015;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
}

:link {
  color: var(--color-accent-primary);
  text-decoration: underline;
  cursor: pointer;
}

a:visited {
  color: var(--color-accent-primary);
  text-decoration: underline;
}

a:active {
  color: var(--color-accent-secondary);
}

.card-flex-container {
  display: flex;
  flex-direction: column;
  height: 60rem !important;
}

.card-footer {
  margin-top: auto;
}

/* --- Hero Section --- */
.hero {
  height: 100vh;
  width: 100%;
  position: relative;
  z-index: 10;
  padding-top: 8rem;
  padding-bottom: 4rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
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
  border: none;
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
  background: #ffa133;
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
  background: rgba(255, 255, 255, 0.35);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.03);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border-outer);
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.05);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.15);
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

/* --- Bento Grid Layout --- */
.features-section {
  position: relative;
  z-index: 10;
  padding-bottom: 4rem;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
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

.col-span-7 {
  grid-column: span 7;
}

.col-span-6 {
  grid-column: span 6;
}

.col-span-5 {
  grid-column: span 5;
}

.col-span-4 {
  grid-column: span 4;
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
  padding: 40px;
  border-radius: 22px;
  border: 1px solid var(--color-border-inner);
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

.card-desc:last-child {
  margin-bottom: 0;
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

.bezel-card-outer.accent-glow .card-title-small i {
  color: var(--color-accent-secondary);
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

.bezel-card-outer.accent-glow .card-list-icon {
  color: var(--color-accent-secondary);
}

.card-list-item strong {
  color: var(--color-text-primary);
}



/* --- Editorial Layout (Business Structural Split) --- */
.editorial-card {
  flex-direction: row;
  gap: 2rem;
  align-items: stretch;
  padding: 2rem;
  background: linear-gradient(180deg, #0b0e12 0%, #06080b 100%);
}

.editorial-left {
  flex: 1.2;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.editorial-left .btn {
  /* margin-top: 36px; */
  align-self: flex-end;
}



/* --- SVG Diagram Structure --- */
.coeus-diagram-container {
  position: relative;
  width: 100%;
  max-width: 500px;
  aspect-ratio: 5 / 3;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid var(--color-border-outer);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.6);
  margin: 0 auto;
}

.coeus-svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
  pointer-events: none;
}

.flow-base {
  stroke: rgba(255, 255, 255, 0.05);
  stroke-width: 2;
  fill: none;
}

.flow-path {
  fill: none;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-dasharray: 6 10;
  animation: flow-anim 1.5s linear infinite;
}

.path-trigger {
  stroke: var(--color-accent-primary);
  filter: drop-shadow(0 0 3px rgba(255, 136, 0, 0.5));
}

.path-solve-req {
  stroke: var(--color-accent-secondary);
  filter: drop-shadow(0 0 3px rgba(141, 215, 218, 0.5));
  animation-duration: 2s;
}

.path-solve-res {
  stroke: var(--color-accent-secondary);
  filter: drop-shadow(0 0 3px rgba(141, 215, 218, 0.5));
  animation-direction: reverse;
  animation-duration: 2s;
}

.path-store {
  stroke: var(--color-accent-primary);
  filter: drop-shadow(0 0 3px rgba(255, 136, 0, 0.5));
  animation-duration: 1.2s;
}

@keyframes flow-anim {
  to {
    stroke-dashoffset: -16;
  }
}

.diagram-node {
  position: absolute;
  transform: translate(-50%, -50%);
  background: rgba(11, 14, 18, 0.7);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid var(--color-border-inner);
  border-radius: 12px;
  padding: 8px 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  width: 155px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.05);
  z-index: 2;
  transition: all 0.3s ease;
}

.diagram-node:hover {
  border-color: rgba(255, 136, 0, 0.3);
  background: rgba(11, 14, 18, 0.9);
  transform: translate(-50%, -52%);
}

.diagram-node.node-solver:hover {
  border-color: rgba(141, 215, 218, 0.3);
}

.node-icon {
  font-size: 1.25rem;
  color: var(--color-accent-primary);
  display: flex;
  align-items: center;
  justify-content: center;
}

.node-solver .node-icon {
  color: var(--color-accent-secondary);
}

.node-content {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  text-align: left;
}

.node-title {
  font-family: var(--font-display);
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-text-primary);
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}

.node-subtitle {
  font-size: 0.58rem;
  color: var(--color-text-secondary);
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}



.mock-diagram {
  background: #050505;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.mock-header {
  background: rgba(255, 255, 255, 0.02);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding: 14px 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.mock-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.mock-dot.red {
  background: #ff5f56;
}

.mock-dot.yellow {
  background: #ffbd2e;
}

.mock-dot.green {
  background: #27c93f;
}

.mock-title {
  color: var(--color-text-muted);
  font-size: 0.75rem;
  font-family: 'Fira Code', monospace;
  margin-left: 8px;
}

.mock-code {
  padding: 10px;
  margin: 0;
  overflow-x: auto;
  font-family: 'Fira Code', monospace;
  font-size: 0.8rem;
  color: #ffa844;
  line-height: 1.5;
  background: #07090c;
  height: 100%;
  border-radius: 0;
}

.mock-code code {
  font-size: 0.75rem;
  text-align: left;
}

.mock-code .yaml-key {
  color: var(--color-accent-secondary);
}

.mock-code .yaml-val {
  color: #ffffff;
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
  padding-left: 32px;
}

/* --- Footer --- */
footer {
  position: relative;
  z-index: 10;
  padding: 56px 0;
  border-top: 1px solid var(--color-border-outer);
  background: #050505;
  text-align: center;
}

.footer-text {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  letter-spacing: 0.5px;
  line-height: 1.8;
}

/* --- Contact Section --- */
.contact-section {
  padding-top: 2rem;
  height: 100%;
  width: 100%;
  scroll-snap-align: start;
  scroll-snap-stop: always;
}

.contact-grid {
  max-width: 800px;
  margin: 40px auto 40px;
  width: 100%;
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 28px;
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 28px;
  }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.form-group label {
  color: var(--color-text-primary);
  font-size: 0.9rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 16px 20px 16px 28px !important;
  background-color: rgba(0, 0, 0, 0.2);
  border: 1px solid var(--color-border-outer);
  border-radius: 14px;
  color: var(--color-text-primary);
  font-family: inherit;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.form-group select {
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 256 256'%3E%3Cpath d='M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80a8,8,0,0,1,11.32-11.32L128,164.69l74.34-74.34a8,8,0,0,1,11.32,11.32Z'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 20px center;
  background-size: 14px;
  padding-right: 2rem;
}

.form-group select option {
  background: #0b0e12;
  color: #fff;
  font-size: 0.95rem;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--color-accent-primary);
  background-color: rgba(255, 255, 255, 0.02);
  box-shadow: 0 0 20px rgba(255, 136, 0, 0.12);
}

.submit-btn {
  width: 100%;
  margin-top: 8px;
  justify-content: center;
}

/* --- Scroll Reveal Animations --- */
.reveal-item {
  opacity: 0;
  transform: translateY(32px);
  transition: opacity 1.2s var(--ease-premium), transform 1.2s var(--ease-premium), filter 1.2s var(--ease-premium);
  filter: blur(8px);
  will-change: transform, opacity, filter;
}

.reveal-item.revealed {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
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

  :global(body.home-page-active) {
    scroll-snap-type: none;
  }

  .hero,
  .features-section,
  .faq-section,
  .contact-section {
    height: auto;
    min-height: auto;
    scroll-snap-align: none;
    scroll-snap-stop: normal;
  }

  .features-section,
  .faq-section,
  .contact-section {
    padding-top: 80px;
    padding-bottom: 80px;
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

  .col-span-12,
  .col-span-7,
  .col-span-6,
  .col-span-5,
  .col-span-4 {
    grid-column: span 1 !important;
  }

  .bezel-card-inner {
    padding: 28px;
  }

  .editorial-card {
    flex-direction: column;
    gap: 32px;
    padding: 28px;
  }

  .coeus-diagram-container {
    max-width: 100%;
    margin-top: 24px;
  }

  .diagram-node {
    width: 120px;
    padding: 4px 6px;
    gap: 4px;
  }

  .node-icon {
    font-size: 0.9rem;
  }

  .node-title {
    font-size: 0.58rem;
  }

  .node-subtitle {
    font-size: 0.48rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .reveal-item {
    opacity: 1 !important;
    transform: none !important;
    filter: none !important;
    transition: none !important;
  }

  .glow-orb {
    transform: none !important;
  }
}

/* --- Enquiry Form Status Messages --- */
.form-status {
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 0.9rem;
  margin-bottom: 24px;
  display: none;
  align-items: center;
  gap: 10px;
  line-height: 1.4;
}

.form-status.error {
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #ef4444;
}

.form-status.active {
  display: flex;
}

.form-status-success {
  display: none;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 40px 0;
  animation: fadeIn 0.5s var(--ease-premium);
}

.form-status-success.active {
  display: flex;
}

.success-icon {
  width: 64px;
  height: 64px;
  background: rgba(141, 215, 218, 0.08);
  border: 1px solid rgba(141, 215, 218, 0.25);
  color: var(--color-accent-secondary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  margin-bottom: 24px;
  box-shadow: 0 0 20px rgba(141, 215, 218, 0.15);
}

.success-title {
  font-family: var(--font-display);
  color: var(--color-text-primary);
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 12px;
}

.success-desc {
  color: var(--color-text-secondary);
  font-size: 0.95rem;
  line-height: 1.6;
  margin-bottom: 32px;
  max-width: 360px;
}

/* --- Trust & Provenance Banner --- */
.trust-banner {
  /* margin-top: 1rem;
  margin-bottom: 5rem; */
}

.trust-banner-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--color-border-outer);
  padding: 24px 32px;
  border-radius: 24px;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.03);
}

.trust-title {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-text-secondary);
  letter-spacing: 1.5px;
  text-transform: uppercase;
}

.trust-logos {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 24px;
}

.trust-logo-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-text-primary);
  font-size: 0.9rem;
  font-weight: 600;
  opacity: 0.85;
  transition: opacity 0.3s ease;
}

.trust-logo-item:hover {
  opacity: 1;
}

.trust-logo-item i {
  color: var(--color-accent-primary);
  font-size: 1.1rem;
}

/* --- Card Visuals and Badges --- */
.card-visual-wrapper {
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid var(--color-border-outer);
  border-radius: 18px;
  padding: 16px;
  margin-bottom: 24px;
  min-height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.5);
}

.compliance-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(141, 215, 218, 0.06);
  border: 1px solid rgba(141, 215, 218, 0.15);
  color: var(--color-accent-secondary);
  padding: 4px 12px;
  border-radius: 100px;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.compliance-badge i {
  font-size: 0.85rem;
}

.mock-diagram-compact {
  width: 100%;
  background: #050505;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  overflow: hidden;
}

.mock-code-compact {
  padding: 12px;
  margin: 0;
  font-family: 'Fira Code', monospace;
  font-size: 0.72rem;
  color: #ffa844;
  line-height: 1.4;
  background: #07090c;
  text-align: left;
}

/* --- CCMA Case Volume Sector Chart --- */
.mock-chart-container {
  width: 100%;
  background: #050505;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.mock-chart-content {
  padding: 20px;
  height: 120px;
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  background: #07090c;
  position: relative;
}

.chart-bar-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 18%;
  height: 100%;
  justify-content: flex-end;
  position: relative;
}

.chart-bar {
  width: 100%;
  background: linear-gradient(to top, rgba(255, 136, 0, 0.4), var(--color-accent-primary));
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
  box-shadow: 0 0 10px rgba(255, 136, 0, 0.15);
}

.chart-bar:hover {
  background: linear-gradient(to top, rgba(255, 136, 0, 0.6), #ffa133);
  box-shadow: 0 0 15px rgba(255, 136, 0, 0.4);
  transform: scaleX(1.05);
}

.chart-tooltip {
  position: absolute;
  bottom: 105%;
  left: 50%;
  transform: translateX(-50%) translateY(4px);
  background: rgba(11, 14, 18, 0.95);
  border: 1px solid var(--color-border-inner);
  color: #fff;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 0.58rem;
  font-weight: 600;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: all 0.2s ease;
  z-index: 10;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
}

.chart-bar:hover .chart-tooltip {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

.chart-label {
  font-size: 0.58rem;
  color: var(--color-text-secondary);
  margin-top: 6px;
  font-weight: 500;
}

/* --- Request a Pipeline styling --- */
.request-pipeline-card .editorial-card {
  background: linear-gradient(180deg, #0b0e12 0%, #06080b 100%);
  border-color: rgba(255, 136, 0, 0.15);
}

.request-pipeline-card:hover .bezel-card-outer {
  border-color: rgba(255, 136, 0, 0.3);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6), 0 0 50px rgba(255, 136, 0, 0.05);
}

@media (max-width: 768px) {
  .trust-banner {
    margin-top: 0;
    margin-bottom: 3rem;
  }

  .trust-banner-inner {
    padding: 16px;
  }

  .trust-logos {
    gap: 16px;
  }

  .trust-logo-item {
    font-size: 0.8rem;
  }
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
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-top: 40px;
  width: 100%;
  position: relative;
  z-index: 2;
}

.pricing-card {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--color-border-outer);
  border-radius: 20px;
  padding: 40px 32px;
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
  margin-bottom: 24px;
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
  font-size: 3rem;
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
    max-width: 450px;
    margin-left: auto;
    margin-right: auto;
  }
}
</style>
