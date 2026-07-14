<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const orb1 = ref(null)
const orb2 = ref(null)
const orb3 = ref(null)

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

onMounted(() => {
  document.addEventListener('mousemove', handleMouseMove)
})

onUnmounted(() => {
  document.removeEventListener('mousemove', handleMouseMove)
})
</script>

<template>
  <div class="background-visuals">
    <div class="glow-orb orb-1" ref="orb1"></div>
    <div class="glow-orb orb-2" ref="orb2"></div>
    <div class="glow-orb orb-3" ref="orb3"></div>
    <div class="grid-overlay"></div>
    <div class="noise-overlay"></div>
  </div>
</template>

<style scoped>
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

@media (prefers-reduced-motion: reduce) {
  .glow-orb {
    transform: none !important;
  }
}
</style>
