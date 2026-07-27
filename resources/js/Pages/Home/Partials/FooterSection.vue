<script setup>
import { Link } from '@inertiajs/vue3'
import { defineComponent, h } from 'vue'

const socialNavigation = [
  {
    name: 'Facebook',
    href: 'https://www.facebook.com/8ohmtechnologies',
    icon: defineComponent({
      render: () =>
        h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
          h('path', {
            'fill-rule': 'evenodd',
            d: 'M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z',
            'clip-rule': 'evenodd',
          }),
        ]),
    }),
  },
  {
    name: 'Instagram',
    href: 'https://www.instagram.com/8ohmtechnologies/',
    icon: defineComponent({
      render: () =>
        h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
          h('path', {
            'fill-rule': 'evenodd',
            d: 'M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z',
            'clip-rule': 'evenodd',
          }),
        ]),
    }),
  },
  {
    name: 'X',
    href: 'https://x.com/8ohm_tech',
    icon: defineComponent({
      render: () =>
        h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
          h('path', {
            d: 'M13.6823 10.6218L20.2391 3H18.6854L12.9921 9.61788L8.44486 3H3.2002L10.0765 13.0074L3.2002 21H4.75404L10.7663 14.0113L15.5685 21H20.8131L13.6819 10.6218H13.6823ZM11.5541 13.0956L10.8574 12.0991L5.31391 4.16971H7.70053L12.1742 10.5689L12.8709 11.5655L18.6861 19.8835H16.2995L11.5541 13.096V13.0956Z',
          }),
        ]),
    }),
  },
  {
    name: 'LinkedIn',
    href: 'https://www.linkedin.com/company/8ohm-technologies',
    icon: defineComponent({
      render: () =>
        h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
          h('path', {
            d: 'M21,21H17V14.25C17,13.19 15.81,12.31 14.75,12.31C13.69,12.31 13,13.19 13,14.25V21H9V9H13V11C13.66,9.93 15.36,9.24 16.5,9.24C19,9.24 21,11.28 21,13.75V21M7,21H3V9H7V21M5,3A2,2 0 0,1 7,5A2,2 0 0,1 5,7A2,2 0 0,1 3,5A2,2 0 0,1 5,3Z',
            'clip-rule': 'evenodd',
          }),
        ]),
    }),
  },
  {
    name: 'GitHub',
    href: 'https://github.com/8OHM-Technologies',
    icon: defineComponent({
      render: () =>
        h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
          h('path', {
            'fill-rule': 'evenodd',
            d: 'M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z',
            'clip-rule': 'evenodd',
          }),
        ]),
    }),
  },
]
</script>

<template>
  <footer class="relative z-10 bg-[#050505] border-t border-white/5" aria-labelledby="footer-heading">
    <h2 id="footer-heading" class="sr-only">Footer</h2>
    <div class="mx-auto max-w-[1400px] px-6 pb-8 pt-16 sm:pt-24 lg:px-8 lg:pt-32">
      <div class="xl:grid xl:grid-cols-3 xl:gap-8">
        <!-- Logo and Intro -->
        <div class="space-y-6">
          <Link :href="route('home')" class="inline-block">
            <img class="h-8 opacity-85 hover:opacity-100 transition-opacity duration-300"
              src="/assets/images/8OHM_Logo.webp" alt="8OHM Logo" />
          </Link>
          <p class="text-xs/6 text-zinc-400 max-w-xs font-semibold uppercase tracking-wider">
            Professional-grade public data pipelines, custom web scraping, and secure, open-source smart home solutions.
          </p>
        </div>

        <!-- Navigation Columns -->
        <div class="mt-16 grid grid-cols-2 gap-8 sm:grid-cols-3 xl:col-span-2 xl:mt-0">
          <div>
            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-400">Services</h3>
            <ul role="list" class="mt-6 space-y-4">
              <li>
                <Link :href="route('home') + '#services'"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">
                  CCMA Datasets</Link>
              </li>
              <li>
                <Link :href="route('home') + '#services'"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">
                  Developer API</Link>
              </li>
              <li>
                <Link :href="route('home') + '#services'"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">
                  Pro Dashboard</Link>
              </li>
              <li>
                <a :href="route('home') + '#contact'"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">Custom
                  Pipelines</a>
              </li>
            </ul>
          </div>

          <div>
            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-400">Navigation</h3>
            <ul role="list" class="mt-6 space-y-4">
              <li>
                <Link :href="route('home')"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">
                  Home</Link>
              </li>
              <li>
                <a :href="route('home') + '#services'"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">Services</a>
              </li>
              <li>
                <a :href="route('home') + '#philosophy'"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">Our
                  Philosophy</a>
              </li>
              <li>
                <a :href="route('home') + '#faq'"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">FAQ</a>
              </li>
            </ul>
          </div>

          <div>
            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-400">Legal</h3>
            <ul role="list" class="mt-6 space-y-4">
              <li>
                <Link :href="route('privacy-policy')" target="_blank"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">
                  Privacy Policy</Link>
              </li>
              <li>
                <Link :href="route('terms')" target="_blank"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">
                  Terms & Conditions</Link>
              </li>
              <li>
                <Link :href="route('refund-cancellation')" target="_blank"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">
                  Refund Policy</Link>
              </li>
              <li>
                <Link :href="route('fair-usage')" target="_blank"
                  class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-primary transition-colors duration-300">
                  Fair Usage Policy</Link>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Bottom section: Copyright and Socials -->
      <div class="mt-16 border-t border-white/5 pt-8 md:flex md:items-center md:justify-between">
        <div class="flex justify-center gap-x-6 md:order-2">
          <a v-for="item in socialNavigation" :key="item.name" :href="item.href" target="_blank"
            class="text-zinc-500 hover:text-primary transition-colors duration-300">
            <span class="sr-only">{{ item.name }}</span>
            <component :is="item.icon" class="size-6" aria-hidden="true" />
          </a>
        </div>
        <p class="mt-8 text-center text-[10px] text-zinc-700 uppercase font-black tracking-[0.25em] md:order-1 md:mt-0">
          &copy; 2026 Infinity Ohm Technologies (Pty) Ltd t/a 8OHM. CIPC Reg No:
          2026/408257/07. All rights reserved.
        </p>
      </div>
    </div>
  </footer>
</template>