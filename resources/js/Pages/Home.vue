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
  <Head title="8OHM. | End-to-end Data Solutions">
    <link rel="canonical" href="https://8ohm.co.za" />
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
