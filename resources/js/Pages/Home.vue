<script setup>
import { onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import Navbar from '@/Components/Navbar.vue'
import BackgroundVisuals from '@/Pages/Home/Partials/BackgroundVisuals.vue'
import HeroSection from '@/Pages/Home/Partials/HeroSection.vue'
import PricingSection from '@/Pages/Home/Partials/PricingSection.vue'
import PhilosophySection from '@/Pages/Home/Partials/PhilosophySection.vue'
import OhmBaseSection from '@/Pages/Home/Partials/OhmBaseSection.vue'
import FaqSection from '@/Pages/Home/Partials/FaqSection.vue'
import ContactSection from '@/Pages/Home/Partials/ContactSection.vue'
import FooterSection from '@/Pages/Home/Partials/FooterSection.vue'

defineProps({
  auth: Object,
  products: Array,
  roadmapItems: Array,
  sectionHeaders: Object,
});

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
})

onUnmounted(() => {
  document.body.classList.remove('home-page-active')
  document.documentElement.classList.remove('home-page-active')
  window.removeEventListener('hashchange', checkHashAndParams)
})
</script>

<template>

  <Head title="End-to-end Data Solutions">
    <link rel="canonical" href="https://8ohm.co.za" />
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@graph": [
          {
            "@type": "Organization",
            "@id": "https://8ohm.co.za/#organization",
            "name": "8OHM Technologies",
            "url": "https://8ohm.co.za",
            "logo": "https://8ohm.co.za/assets/images/8OHM_Logo.webp",
            "sameAs": [
              "https://github.com/8OHM-Technologies"
            ]
          },
          {
            "@type": "WebSite",
            "@id": "https://8ohm.co.za/#website",
            "url": "https://8ohm.co.za",
            "name": "8OHM",
            "publisher": {
              "@id": "https://8ohm.co.za/#organization"
            }
          },
          {
            "@type": "FAQPage",
            "mainEntity": [
              {
                "@type": "Question",
                "name": "What public data sources do you currently provide?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Our primary focus is on South African legal and labour data. We currently provide comprehensive, structured datasets from the CCMA and Labour Courts, with Bargaining Council resolutions and High Court judgments on our roadmap."
                }
              },
              {
                "@type": "Question",
                "name": "Is the dataset compliant with POPIA and privacy laws?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Yes. We apply rigorous sanitization, anonymization, and data cleaning processes to our ingestion pipelines, ensuring that all structured legal datasets fully comply with the Protection of Personal Information Act (POPIA)."
                }
              },
              {
                "@type": "Question",
                "name": "How frequently is the API and Dashboard data updated?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Our data pipelines run continuously. As soon as new judgments or arbitration awards are published on public portals, they are extracted, cleaned, and immediately made available through our Developer API and Pro Dashboard."
                }
              },
              {
                "@type": "Question",
                "name": "Do I need to be a developer to access the datasets?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Not at all. While we offer a powerful Developer API for custom integrations, our Pro Dashboard provides a complete no-code solution for HR and legal professionals to visually analyze trends and export data to CSV or Excel."
                }
              },
              {
                "@type": "Question",
                "name": "Can you extract custom data specific to our industry?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Absolutely. Through our Managed Data Pipeline tier, we design and build bespoke web scraping, data extraction, and ETL workflows tailored to your operational needs, delivering structured data directly into your private ecosystem."
                }
              },
              {
                "@type": "Question",
                "name": "How do the consumer (OhmBase) and business divisions relate?",
                "acceptedAnswer": {
                  "@type": "Answer",
                  "text": "Both share our core philosophy of digital sovereignty. OhmBase is our open-source initiative empowering individuals to self-host secure smart homes, while our business division delivers professional-grade public data pipelines and IT consulting."
                }
              }
            ]
          }
        ]
      }
    </script>
  </Head>

  <div class="home-page-container">
    <BackgroundVisuals />

    <Navbar :auth="auth" />

    <main>
      <HeroSection :roadmap-items="roadmapItems" />

      <PricingSection :products="products" :section-headers="sectionHeaders" />

      <PhilosophySection :section-headers="sectionHeaders" />

      <OhmBaseSection :section-headers="sectionHeaders" />

      <FaqSection :section-headers="sectionHeaders" />

      <ContactSection :section-headers="sectionHeaders" />
    </main>

    <FooterSection />
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

  /* Fallback definitions for local variables */
  --bg-secondary: rgba(255, 255, 255, 0.02);
  --bg-tertiary: rgba(255, 255, 255, 0.04);
  --border-color: rgba(255, 255, 255, 0.08);
  --text-primary: var(--color-text-primary, #f0f0f5);
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
</style>
