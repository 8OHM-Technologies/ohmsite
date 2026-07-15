<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <footer class="pt-20 pb-10 border-t border-white/5 text-center relative z-10">
    <img src="/assets/images/8OHM_Logo.webp" alt="8OHM Logo" class="h-8 mx-auto opacity-20 mb-8" />
    <div class="text-zinc-700 text-[10px] font-black uppercase tracking-[0.5em]">
      © 2026 Infinity Ohm Technologies (Pty) Ltd t/a 8OHM. All rights reserved.
    </div>
    <div class="flex justify-center items-center gap-x-6 gap-y-2 flex-wrap text-sm">
      <Link href="/privacy-policy" target="_blank">Privacy Policy</Link>
      <Link href="/terms" target="_blank">Terms & Conditions</Link>
      <Link href="/refund-cancellation" target="_blank">Cancellation & Refund Policy</Link>
      <Link href="/fair-usage" target="_blank">Fair Usage Policy</Link>
    </div>
  </footer>
</template>

<style scoped>
/* --- Footer --- */
footer {
  position: relative;
  z-index: 10;
  padding: 30px 0;
  border-top: 1px solid var(--color-border-outer, rgba(255, 255, 255, 0.08));
  background: #050505;
  text-align: center;
}

.footer-text {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  letter-spacing: 0.5px;
  line-height: 1.8;
}
</style>
