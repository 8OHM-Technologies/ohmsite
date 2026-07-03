<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Navbar from '@/Components/Navbar.vue'

const props = defineProps({
  heroProducts: Array,
  brands: Array,
  categories: Array,
  popularProducts: Array,
  newArrivals: Array,
  aboutUs: Object,
  auth: Object,
});

const isModalOpen = ref(false)
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

const openModal = (division = '') => {
  // Reset form
  form.name = ''
  form.email = ''
  form.message = ''
  formError.value = ''
  showSuccess.value = false

  if (division) {
    if (division === 'architecture') {
      form.division = 'level3-pipeline'
    } else if (division === 'coeus') {
      form.division = 'sample-dataset'
    } else if (division === 'consumer') {
      form.division = 'consumer-hardware'
    } else {
      form.division = division
    }
  } else {
    form.division = ''
  }

  isModalOpen.value = true
  document.body.style.overflow = 'hidden'
}

const closeModal = () => {
  isModalOpen.value = false
  document.body.style.overflow = 'auto'
  // Clear hash from URL so it doesn't stay #contact
  if (window.location.hash === '#contact' || window.location.hash === '#enquire') {
    history.pushState("", document.title, window.location.pathname + window.location.search);
  }
  setTimeout(() => {
    showSuccess.value = false
    formError.value = ''
    form.name = ''
    form.email = ''
    form.message = ''
    form.division = ''
  }, 500)
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

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && isModalOpen.value) {
    closeModal()
  }
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
    openModal()
  }
}

onMounted(() => {
  // Add body class for scroll snapping
  document.body.classList.add('home-page-active')
  document.documentElement.classList.add('home-page-active')

  // Check URL params/hash for contact modal
  checkHashAndParams()
  window.addEventListener('hashchange', checkHashAndParams)

  // Keyboard and mouse events
  window.addEventListener('keydown', handleKeyDown)
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
  window.removeEventListener('keydown', handleKeyDown)
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
          <span class="eyebrow-dot" style="background: #ff5f56; box-shadow: 0 0 8px #ff5f56;"></span>
          <span class="eyebrow-text">South African Labour & Employment Law</span>
        </div>

        <h1 class="hero-title reveal-item">
          Turn Fragmented Legal Data<br />
          <span class="gradient-text">into Unfair Analytical Advantage</span>
        </h1>

        <p class="hero-tagline reveal-item">
          South African public data sources are notoriously unstable. We absorb the operational risk of scraping,
          cleaning, and structuring records from the CCMA, Labour Courts, and High Courts. Get continuous,
          POPIA-compliant data feeds delivered directly into your models, or access macro-trends via our interactive
          dashboards.
        </p>

        <div class="cta-group reveal-item">
          <a href="#level1" class="btn btn-primary">
            <span>Browse the Data Store</span>
            <div class="btn-icon">
              <i class="ph-light ph-database"></i>
            </div>
          </a>
          <a href="#level2" class="btn btn-secondary">
            <span>Book a Dashboard Demo</span>
            <div class="btn-icon">
              <i class="ph-light ph-presentation"></i>
            </div>
          </a>
        </div>

        <!-- Trust & Provenance Banner -->
        <div class="trust-banner container reveal-item" id="trust-banner">
          <div class="trust-banner-inner">
            <span class="trust-title">Ingesting From Trusted Public Jurisdictions & Registries:</span>
            <div class="trust-logos">
              <div class="trust-logo-item">
                <i class="ph-light ph-scales"></i>
                <span>CCMA</span>
              </div>
              <div class="trust-logo-item">
                <i class="ph-light ph-briefcase"></i>
                <span>Labour Court</span>
              </div>
              <div class="trust-logo-item">
                <i class="ph-light ph-scales"></i>
                <span>High Court Registries</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="features-section container reveal-item" id="b2b" aria-label="B2B Division">
        <div class="section-header">
          <div class="eyebrow-badge secondary">
            <span class="eyebrow-dot"></span>
            <span class="eyebrow-text">B2B Legal Data Services</span>
          </div>
          <h2 class="section-title">
            Flexible Delivery for Legal, HR, and Compliance Teams
          </h2>
          <p class="section-subtitle">
            Skip the manual research. Get structured South African public legal data delivered via high-speed feeds or
            explore trends visually.
          </p>
        </div>

        <div class="bento-grid">
          <!-- Card 1: Level 1 -->
          <div class="bezel-card-outer col-span-4 reveal-item accent-glow" id="level1">
            <div class="bezel-card-inner"
              style="display: flex; flex-direction: column; justify-content: space-between; min-height: 580px;">
              <div>
                <div class="card-visual-wrapper">
                  <div class="mock-diagram-compact">
                    <div class="mock-header">
                      <span class="mock-dot red"></span>
                      <span class="mock-dot yellow"></span>
                      <span class="mock-dot green"></span>
                      <span class="mock-title">judgement_parsed.json</span>
                    </div>
                    <pre class="mock-code-compact"><code>{
  <span class="yaml-key">"court"</span>: <span class="yaml-val">"Labour Court"</span>,
  <span class="yaml-key">"case_number"</span>: <span class="yaml-val">"JS869/2022"</span>,
  <span class="yaml-key">"reportable"</span>: <span class="yaml-val">"Yes"</span>,
  <span class="yaml-key">"subjects"</span>: <span class="yaml-val">"Labour > Dismissal"</span>,
  <span class="yaml-key">"result"</span>: <span class="yaml-val">"Dismissed with costs"</span>
}</code></pre>
                  </div>
                </div>

                <div class="compliance-badge">
                  <i class="ph-fill ph-shield-check"></i>
                  <span>POPIA Compliant Processing</span>
                </div>

                <h3 class="card-title" style="margin-top: 16px;">
                  Historic Data Archives (Level 1)
                </h3>
                <p class="card-desc">
                  Skip the scraping and bypass broken PDFs. Get 10+ years of pristine, POPIA-compliant historic labor
                  law data fully cleaned, parsed, and enriched with legal metadata. Delivered in JSON, CSV, or Parquet
                  for immediate integration into your internal AI models or academic research.
                </p>
              </div>

              <button type="button" class="btn btn-primary contact-trigger-btn btn-align-bottom"
                @click="openModal('sample-dataset')">
                <span>Download Free Sample Dataset</span>
                <div class="btn-icon">
                  <i class="ph-light ph-download"></i>
                </div>
              </button>
            </div>
          </div>

          <!-- Card 2: Level 2 -->
          <div class="bezel-card-outer col-span-4 reveal-item accent-glow" id="level2">
            <div class="bezel-card-inner"
              style="display: flex; flex-direction: column; justify-content: space-between; min-height: 580px;">
              <div>
                <div class="card-visual-wrapper">
                  <div class="mock-chart-container">
                    <div class="mock-header">
                      <span class="mock-dot red"></span>
                      <span class="mock-dot yellow"></span>
                      <span class="mock-dot green"></span>
                      <span class="mock-title">case_volume_by_sector.svg</span>
                    </div>
                    <div class="mock-chart-content">
                      <div class="chart-bar-wrapper">
                        <div class="chart-bar" style="height: 80%">
                          <span class="chart-tooltip">Retail: 12.8k cases</span>
                        </div>
                        <span class="chart-label">Retail</span>
                      </div>
                      <div class="chart-bar-wrapper">
                        <div class="chart-bar" style="height: 55%">
                          <span class="chart-tooltip">Mining: 8.3k cases</span>
                        </div>
                        <span class="chart-label">Mining</span>
                      </div>
                      <div class="chart-bar-wrapper">
                        <div class="chart-bar" style="height: 65%">
                          <span class="chart-tooltip">Mfg: 9.5k cases</span>
                        </div>
                        <span class="chart-label">Mfg</span>
                      </div>
                      <div class="chart-bar-wrapper">
                        <div class="chart-bar" style="height: 40%">
                          <span class="chart-tooltip">Fin: 6.2k cases</span>
                        </div>
                        <span class="chart-label">Fin</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="compliance-badge">
                  <i class="ph-fill ph-shield-check"></i>
                  <span>POPIA Compliant Processing</span>
                </div>

                <h3 class="card-title" style="margin-top: 16px;">
                  Analytics Dashboard (Level 2)
                </h3>
                <p class="card-desc">
                  Built for law firms and HR enterprises requiring macro insights with zero technical setup. Track
                  judicial trends, average trial durations, specific law firm win/loss ratios, and industry risk surges
                  in a responsive, interactive visual environment.
                </p>
              </div>

              <button type="button" class="btn btn-primary dashboard-trigger-btn btn-align-bottom"
                @click="goToDashboard">
                <span>View Demo Dashboard</span>
                <div class="btn-icon">
                  <i class="ph-light ph-arrow-up-right"></i>
                </div>
              </button>
            </div>
          </div>

          <!-- Card 3: Level 3 -->
          <div class="bezel-card-outer col-span-4 reveal-item accent-glow" id="level3">
            <div class="bezel-card-inner"
              style="display: flex; flex-direction: column; justify-content: space-between; min-height: 580px; padding-left: 20px; padding-right: 20px;">
              <div>
                <div class="card-visual-wrapper" style="padding: 0;">
                  <div class="coeus-diagram-container"
                    style="border: none; border-radius: 16px; background: rgba(0,0,0,0.25);">
                    <svg class="coeus-svg" viewBox="0 0 500 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M 100 150 Q 175 240 250 240" class="flow-base" />
                      <path d="M 100 150 Q 175 240 250 240" class="flow-path path-trigger" />
                      <path d="M 240 240 L 240 60" class="flow-base" />
                      <path d="M 240 240 L 240 60" class="flow-path path-solve-req" />
                      <path d="M 260 60 L 260 240" class="flow-base" />
                      <path d="M 260 60 L 260 240" class="flow-path path-solve-res" />
                      <path d="M 250 240 Q 325 240 400 150" class="flow-base" />
                      <path d="M 250 240 Q 325 240 400 150" class="flow-path path-store" />
                    </svg>

                    <div class="diagram-node" style="left: 22%; top: 50%; width: 110px; padding: 4px 6px;">
                      <div class="node-icon" style="font-size: 0.9rem;">
                        <i class="ph-light ph-cloud-arrow-down"></i>
                      </div>
                      <div class="node-content">
                        <span class="node-title" style="font-size: 0.58rem;">Ingestion</span>
                        <span class="node-subtitle" style="font-size: 0.48rem;">Scrapers</span>
                      </div>
                    </div>

                    <div class="diagram-node node-solver" style="left: 50%; top: 22%; width: 110px; padding: 4px 6px;">
                      <div class="node-icon" style="font-size: 0.9rem; color: var(--color-accent-secondary);">
                        <i class="ph-light ph-brain"></i>
                      </div>
                      <div class="node-content">
                        <span class="node-title" style="font-size: 0.58rem;">AI/LLM</span>
                        <span class="node-subtitle" style="font-size: 0.48rem;">Enrichment</span>
                      </div>
                    </div>

                    <div class="diagram-node" style="left: 50%; top: 78%; width: 110px; padding: 4px 6px;">
                      <div class="node-icon" style="font-size: 0.9rem;">
                        <i class="ph-light ph-terminal-window"></i>
                      </div>
                      <div class="node-content">
                        <span class="node-title" style="font-size: 0.58rem;">Processing</span>
                        <span class="node-subtitle" style="font-size: 0.48rem;">Workers</span>
                      </div>
                    </div>

                    <div class="diagram-node" style="left: 78%; top: 50%; width: 110px; padding: 4px 6px;">
                      <div class="node-icon" style="font-size: 0.9rem;">
                        <i class="ph-light ph-database"></i>
                      </div>
                      <div class="node-content">
                        <span class="node-title" style="font-size: 0.58rem;">Streams</span>
                        <span class="node-subtitle" style="font-size: 0.48rem;">APIs</span>
                      </div>
                    </div>
                  </div>
                </div>

                <h3 class="card-title" style="margin-top: 48px;">
                  Custom Proprietary Pipelines (Level 3)
                </h3>
                <p class="card-desc">
                  Stop losing sleep over structural shifts in government websites. We act as your outsourced data
                  engineering team. We build, maintain, and stream automated legal feeds directly into your internal
                  systems (iManage, Salesforce, SQL) with custom rule-based alerting and absolute exclusivity.
                </p>
              </div>

              <button type="button" class="btn btn-secondary contact-trigger-btn btn-align-bottom"
                @click="openModal('level3-pipeline')">
                <span>Speak with an Architect</span>
                <div class="btn-icon">
                  <i class="ph-light ph-user-gear"></i>
                </div>
              </button>
            </div>
          </div>
        </div>

        <!-- Request a Pipeline Section -->
        <div class="request-pipeline-card reveal-item" id="request-pipeline" style="margin-top: 48px;">
          <div class="bezel-card-outer col-span-12">
            <div class="bezel-card-inner editorial-card"
              style="padding: 40px; background: linear-gradient(180deg, #0b0e12 0%, #06080b 100%);">
              <div class="editorial-left">
                <div class="card-icon">
                  <i class="ph-light ph-git-pull-request"></i>
                </div>
                <h3 class="card-title">
                  Don’t see your jurisdiction? Request a Pipeline.
                </h3>
                <p class="card-desc" style="max-width: 800px; margin-bottom: 24px;">
                  We are actively expanding our public data catalog. If your firm requires a specific court registry,
                  regulatory body, or public record dataset, submit a request. If the source passes our technical and
                  market viability checks, we build the pipeline at zero setup cost to you and add it to our public data
                  store.
                </p>
                <button type="button" class="btn btn-primary contact-trigger-btn"
                  @click="openModal('pipeline-request')">
                  <span>Submit a Source Request</span>
                  <div class="btn-icon">
                    <i class="ph-light ph-plus"></i>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="features-section container reveal-item" id="consumer" aria-label="Consumer Bento Grid">
        <div class="section-header">
          <div class="eyebrow-badge">
            <span class="eyebrow-dot"></span>
            <span class="eyebrow-text">Sovereign Tech Foundation</span>
          </div>
          <h2 class="section-title">Infinity Ohm Labs</h2>
          <p class="section-subtitle">
            The enterprise data architectures we build are born from a radical philosophy: absolute digital sovereignty.
            Explore our open-source blueprints and hardware store, designed to help individuals build their own
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
              <button type="button" class="btn btn-primary contact-trigger-btn btn-align-bottom"
                @click="openModal('consumer-hardware')">
                <span>Go to Ohmbase</span>
                <div class="btn-icon">
                  <i class="ph-light ph-arrow-right"></i>
                </div>
              </button>
            </div>
          </div>

          <div class="bezel-card-outer col-span-5 reveal-item accent-glow">
            <div class="bezel-card-inner">
              <div class="card-icon">
                <i class="ph-light ph-shopping-bag"></i>
              </div>
              <h3 class="card-title">Sovereign Hardware Store</h3>
              <p class="card-desc" style="margin-bottom: 12px;">
                We are engineering open-standard, self-contained smart home controllers and stocking components to power
                your self-hosting journey without vendor lock-in.
              </p>
              <div class="product-status"
                style="margin-top: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 600; color: var(--color-accent-secondary);">
                <span class="status-dot"
                  style="width: 6px; height: 6px; background: var(--color-accent-secondary); border-radius: 50%; box-shadow: 0 0 6px var(--color-accent-secondary);"></span>
                Store Launching Soon
              </div>
              <button type="button" class="btn btn-secondary contact-trigger-btn btn-align-bottom"
                @click="openModal('consumer-hardware')">
                <span>Join the Waitlist</span>
                <div class="btn-icon">
                  <i class="ph-light ph-bell"></i>
                </div>
              </button>
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
            Grounding both divisions in strategic engineering, absolute data ownership, and open standards.
          </p>
        </div>
        <div class="bento-grid">
          <div class="bezel-card-outer col-span-6 reveal-item">
            <div class="bezel-card-inner">
              <h4 class="card-title-small">
                <i class="ph-light ph-git-merge"></i>
                Ecosystem Interoperability
              </h4>
              <p class="card-desc-small">
                We design and map technology infrastructure to break down the boundaries of rigid, isolated
                tech stacks. By evaluating your complete operational footprint, we deliver custom blueprints
                that tightly integrate legacy devices, edge network intelligence, and modern cloud
                utilities. By unifying these environments into a coherent, responsive system, we ensure your
                data moves securely from local servers to the cloud, driving true operational continuity and
                efficiency.
              </p>
            </div>
          </div>

          <div class="bezel-card-outer col-span-6 reveal-item">
            <div class="bezel-card-inner">
              <h4 class="card-title-small">
                <i class="ph-light ph-cloud-check"></i>
                Diagnostic Strategic Planning
              </h4>
              <p class="card-desc-small">
                Technology requires a visionary roadmap, not just superficial software installations. Every
                engagement begins with an objective, comprehensive IT assessment designed to evaluate your
                current digital ecosystem. We deliver highly actionable consulting reports that empower your
                business to scale freely, protecting your operations from restrictive tech vendor lock-in.
                Simultaneously, we fortify your digital infrastructure, ensuring your critical data is
                completely secure, compliant, and resilient against evolving cyber threats.
              </p>
            </div>
          </div>

          <div class="bezel-card-outer col-span-6 reveal-item">
            <div class="bezel-card-inner">
              <h4 class="card-title-small">
                <i class="ph-light ph-lock-key"></i>
                Enterprise Architecture & Advisory
              </h4>
              <p class="card-desc-small">
                We act as your dedicated strategic engineering partner, moving far beyond high-level
                consulting to seamlessly integrate technology directly into your business operations and
                products. From deploying secure, on-premises enterprise AI models to conducting exhaustive
                IT infrastructure assessments, we provide the definitive operational blueprints your
                organization requires—and we stick around to execute them. Our hands-on advisory services
                deliver absolute clarity on hidden technical overhead and establish long-term IT roadmaps
                designed specifically to drive day-to-day efficiency, unlock product innovation, and
                accelerate your commercial output.
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

      <section class="features-section container reveal-item" id="roadmap" aria-label="Future Vision and Roadmap">
        <div class="section-header">
          <div class="eyebrow-badge secondary">
            <span class="eyebrow-dot"></span>
            <span class="eyebrow-text">The Infinity Roadmap</span>
          </div>
          <h2 class="section-title">
            Building the Sovereign Future
          </h2>
          <p class="section-subtitle">
            Our current DIY kits and enterprise data pipelines are
            the foundation. Here is exactly where our technology is
            heading next.
          </p>
        </div>

        <div class="bento-grid">
          <div class="bezel-card-outer col-span-6 reveal-item">
            <div class="bezel-card-inner">
              <div class="card-icon">
                <i class="ph-light ph-cpu"></i>
              </div>
              <h3 class="card-title">
                Hardware: The Sovereign Smart Home Controller
              </h3>
              <p class="card-desc">
                Today, we supply hardware kits,
                microcontrollers, and IoT components to support
                our self-hosting guides.
                <strong>Tomorrow, this foundation evolves into our
                  own open-standard, all-in-one
                  smart home controllers.</strong>
                We are actively engineering smart devices
                designed natively for absolute local autonomy and
                seamless integration with your geyser, alarm system or gate motor
                straight out of the box.
              </p>
            </div>
          </div>

          <div class="bezel-card-outer col-span-6 reveal-item accent-glow">
            <div class="bezel-card-inner">
              <div class="card-icon">
                <i class="ph-light ph-brain"></i>
              </div>
              <h3 class="card-title">
                Software: The South African Labour Law AI Assistant
              </h3>
              <p class="card-desc">
                Our in-house data acquisition pipeline currently
                ingests and structures complex South African
                legal and labor records and court rolls.
                <strong>Soon, this engine will power our first
                  consumer SaaS.</strong>
                A dedicated AI legal assistant designed
                to empower the average South African—simplifying
                labor laws, navigating CCMA procedures, and
                democratizing access to justice.
              </p>
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

        <div class="faq-grid">
          <div class="bezel-card-outer reveal-item">
            <div class="bezel-card-inner">
              <h4 class="card-title-small">
                <i class="ph-light ph-recycle"></i>
                How do Infinity Ohm Labs and the B2B divisions relate?
              </h4>
              <p class="faq-answer">
                Both are built on the identical core philosophy: achieving true digital sovereignty through self-hosting
                and on-premises control. Infinity Ohm Labs serves as our open-source R&D foundation, empowering
                individuals to host their own secure ecosystems at home, while our B2B division delivers
                enterprise-grade public data pipelines and custom data engineering.
              </p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer>
      <div class="container">
        <p class="footer-text">
          &copy; 2026 Infinity Ohm Technologies (Pty) Ltd. All rights
          reserved.<br />
          <strong>Regulatory Disclaimer:</strong> Infinity Ohm
          Technologies is an educational publisher, components
          supplier, and IT/AI consultancy. We are not a registered
          private security service provider under the South African
          PSIRA frameworks. Modifying primary security setups should
          always be validated through your certified technician and
          insurer.
        </p>
      </div>
    </footer>

    <!-- Contact Modal -->
    <div id="contactModal" class="modal-overlay" :class="{ active: isModalOpen }" @click.self="closeModal">
      <div class="modal-bezel-outer">
        <div class="modal-content">
          <button class="close-btn" id="closeContactBtn" aria-label="Close modal" @click="closeModal">
            <i class="ph-light ph-x"></i>
          </button>

          <div v-show="!showSuccess" id="contactFormWrapper">
            <h2 class="modal-title">Enquiry</h2>
            <p class="modal-subtitle">
              Have questions about our South African public data feeds, custom analytics dashboards, or DIY hardware
              waitlists? Reach out below.
            </p>

            <form @submit.prevent="handleSubmit">
              <div class="form-status error" :class="{ active: formError }" id="formError">
                <i class="ph-light ph-warning-circle"></i>
                <span class="error-text">{{ formError }}</span>
              </div>
              <div class="form-group">
                <label for="name">What do we call you?</label>
                <input type="text" id="name" name="name" required placeholder="John Doe" v-model="form.name" />
              </div>
              <div class="form-group">
                <label for="email">How do we get in touch?</label>
                <input type="email" id="email" name="email" required placeholder="example@email.com"
                  v-model="form.email" />
              </div>
              <div class="form-group">
                <label for="division">What are you interested in?</label>
                <select id="division" name="division" required v-model="form.division">
                  <option value="" disabled selected>
                    Choose an option...
                  </option>
                  <option value="sample-dataset">
                    Request Sample Dataset (Level 1)
                  </option>
                  <option value="dashboard-demo">
                    Book Dashboard Demo (Level 2)
                  </option>
                  <option value="level3-pipeline">
                    Request a Level 3 Custom Pipeline
                  </option>
                  <option value="pipeline-request">
                    Submit a Source Request (Roadmap)
                  </option>
                  <option value="consumer-hardware">
                    Consumer DIY Hardware & Labs
                  </option>
                  <option value="general">
                    General Enquiry
                  </option>
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
              Thank you for your enquiry. Our team will get back
              to you within 48 hours.
            </p>
            <button type="button" class="btn btn-primary submit-btn" id="successCloseBtn" @click="closeModal">
              <span>Close Window</span>
              <div class="btn-icon">
                <i class="ph-light ph-x"></i>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
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

.home-page-container main h1,
.home-page-container main h2,
.home-page-container main h3,
.home-page-container main h4,
.home-page-container main p,
.home-page-container main ul,
.home-page-container main li,
.home-page-container footer p,
.home-page-container #contactModal h2,
.home-page-container #contactModal p,
.home-page-container #contactModal form,
.home-page-container #contactModal .form-group,
.home-page-container #contactModal label,
.home-page-container #contactModal input,
.home-page-container #contactModal textarea,
.home-page-container #contactModal select,
.home-page-container #contactModal button {
  margin: 0;
  padding: 0;
}

:global(html.home-page-active),
:global(body.home-page-active) {
  overflow-y: scroll;
  scroll-snap-type: y proximity;
  scroll-padding-top: 100px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  width: 100%;
}

.section-header {
  text-align: center;
  margin-bottom: 40px;
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
  max-width: 600px;
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
  padding: 8px 8px 8px 28px;
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
  color: #050505;
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
  margin-top: 48px;
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

.bezel-card-inner.flush {
  padding: 0;
}

.bezel-card-inner.flush .mock-diagram {
  border: none;
  border-radius: 0;
  box-shadow: none;
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

.product-link {
  color: var(--color-accent-primary);
  font-size: 0.75rem;
  margin-bottom: 10px;
  display: block;
}

/* --- Editorial Layout (B2B Structural Split) --- */
.editorial-card {
  flex-direction: row;
  gap: 48px;
  align-items: stretch;
  padding: 48px;
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

.editorial-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-top: 10px !important;
  margin-bottom: 10px !important;
  text-align: left;
}

.editorial-grid-item {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.editorial-grid-item strong {
  color: var(--color-text-primary);
  display: block;
  margin-bottom: 4px;
}

.editorial-right {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 320px;
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

.pulse-dot,
.pulse-dot-secondary {
  position: absolute;
  top: -3px;
  right: -3px;
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.pulse-dot {
  background: var(--color-accent-primary);
  box-shadow: 0 0 6px var(--color-accent-primary);
  animation: node-pulse 2s infinite;
}

.pulse-dot-secondary {
  background: var(--color-accent-secondary);
  box-shadow: 0 0 6px var(--color-accent-secondary);
  animation: node-pulse-sec 2s infinite;
}

@keyframes node-pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(255, 136, 0, 0.7);
  }

  70% {
    box-shadow: 0 0 0 6px rgba(255, 136, 0, 0);
  }

  100% {
    box-shadow: 0 0 0 0 rgba(255, 136, 0, 0);
  }
}

@keyframes node-pulse-sec {
  0% {
    box-shadow: 0 0 0 0 rgba(141, 215, 218, 0.7);
  }

  70% {
    box-shadow: 0 0 0 6px rgba(141, 215, 218, 0);
  }

  100% {
    box-shadow: 0 0 0 0 rgba(141, 215, 218, 0);
  }
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

/* --- Store Section --- */
.store-section {
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

.store-header {
  text-align: center;
  margin-bottom: 56px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.store-title {
  font-family: var(--font-display);
  font-size: 3rem;
  font-weight: 800;
  line-height: 1.2;
  color: var(--color-text-primary);
  margin: 16px 0;
}

.store-subtitle {
  color: var(--color-text-secondary);
  max-width: 650px;
  line-height: 1.6;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 24px;
}

.product-card .bezel-card-inner {
  padding: 24px;
  align-items: stretch;
}

.product-badge {
  position: absolute;
  top: 20px;
  right: 20px;
  background: rgba(255, 136, 0, 0.08);
  border: 1px solid rgba(255, 136, 0, 0.25);
  color: var(--color-accent-primary);
  padding: 4px 12px;
  border-radius: 100px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  z-index: 2;
}

.product-image-placeholder {
  height: 200px;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid var(--color-border-outer);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
  position: relative;
  overflow: hidden;
  box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.5);
}

.product-image-placeholder i {
  font-size: 3.5rem;
  color: var(--color-accent-primary);
  opacity: 0.6;
  transition: all 0.5s var(--ease-premium);
}

.product-card:hover .product-image-placeholder i {
  transform: scale(1.1);
  opacity: 1;
}

.product-title {
  font-family: var(--font-display);
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin-bottom: 10px;
}

.product-description {
  color: var(--color-text-secondary);
  font-size: 0.88rem;
  line-height: 1.5;
  margin-bottom: 20px;
  flex-grow: 1;
}

.product-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-top: 15px;
  border-top: 1px solid var(--color-border-inner);
}

.product-price {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-primary);
  font-family: var(--font-display);
}

.product-status {
  font-size: 0.75rem;
  color: var(--color-accent-secondary);
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
}

.status-dot {
  width: 6px;
  height: 6px;
  background: var(--color-accent-secondary);
  border-radius: 50%;
  display: inline-block;
  box-shadow: 0 0 6px var(--color-accent-secondary);
}

/* --- FAQ Grid --- */
.faq-section {
  height: 100%;
  width: 100%;
  padding-bottom: 2rem;
  scroll-snap-align: start;
  scroll-snap-stop: always;
}

.faq-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  text-align: left;
  margin-top: 40px;
}

.faq-grid .bezel-card-inner {
  padding: 32px;
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

/* --- Contact Modal --- */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background-color: rgba(5, 5, 5, 0.8);
  z-index: 1000;
  justify-content: center;
  align-items: center;
  opacity: 0;
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  transition: opacity 0.5s var(--ease-premium);
  padding: 24px;
}

.modal-overlay.active {
  display: flex;
  opacity: 1;
}

.modal-bezel-outer {
  background: var(--color-surface-outer);
  border: 1px solid var(--color-border-outer);
  padding: 6px;
  border-radius: 32px;
  width: 100%;
  max-width: 540px;
  transform: scale(0.9) translateY(30px);
  transition: transform 0.5s var(--ease-premium);
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8);
}

.modal-overlay.active .modal-bezel-outer {
  transform: scale(1) translateY(0);
}

.modal-content {
  background: var(--color-surface-inner);
  border: 1px solid var(--color-border-inner);
  padding: 40px;
  border-radius: 26px;
  position: relative;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.06);
  display: flex;
  flex-direction: column;
}

.close-btn {
  position: absolute;
  top: 24px;
  right: 24px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--color-border-outer);
  color: var(--color-text-secondary);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.close-btn:hover {
  color: var(--color-text-primary);
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.15);
}

.modal-title {
  font-family: var(--font-display);
  color: var(--color-text-primary);
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 8px;
}

.modal-subtitle {
  color: var(--color-text-secondary);
  font-size: 0.95rem;
  margin-bottom: 32px;
}

.form-group {
  margin-bottom: 24px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  color: var(--color-text-primary);
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 14px 16px;
  background-color: rgba(0, 0, 0, 0.2);
  border: 1px solid var(--color-border-outer);
  border-radius: 12px;
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
  background-position: right 16px center;
  background-size: 14px;
  padding-right: 40px;
}

.form-group select option {
  background: #0b0e12;
  color: #fff;
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
  .store-section,
  .faq-section {
    height: auto;
    min-height: auto;
    scroll-snap-align: none;
    scroll-snap-stop: normal;
  }

  .features-section,
  .store-section,
  .faq-section {
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

  .proof-grid,
  .faq-grid,
  .product-grid {
    grid-template-columns: 1fr !important;
    gap: 24px !important;
  }

  .bezel-card-inner {
    padding: 28px;
  }

  .editorial-card {
    flex-direction: column;
    gap: 32px;
    padding: 28px;
  }

  .editorial-right {
    min-width: 100%;
  }

  .editorial-grid {
    grid-template-columns: 1fr;
    gap: 16px;
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

  .pulse-dot,
  .pulse-dot-secondary {
    width: 4px;
    height: 4px;
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
</style>
