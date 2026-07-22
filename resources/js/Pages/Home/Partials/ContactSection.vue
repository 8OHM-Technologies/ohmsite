<script setup>
import { ref, reactive, onMounted } from 'vue'

const props = defineProps({
  sectionHeaders: {
    type: Object,
    default: () => ({})
  }
})

const root = ref(null)

const sectionHeader = (field) => {
  const defaults = {
    eyebrow: 'Contact',
    title: 'Enquire Now',
    subtitle: 'Have questions about our South African public data feeds, custom analytics dashboards, or DIY hardware waitlists? Reach out below.',
  };
  return props.sectionHeaders?.contact?.[field] || defaults[field] || '';
};

const showSuccess = ref(false)
const isSending = ref(false)
const formError = ref('')

const form = reactive({
  name: '',
  email: '',
  division: '',
  message: ''
})

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
    const response = await fetch('/api/send-website-enquiry', {
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
    <section class="contact-section container reveal-item" id="contact" aria-label="Enquire Now">
      <div class="section-header">
        <div class="eyebrow-badge">
          <span class="eyebrow-dot"></span>
          <span class="eyebrow-text">{{ sectionHeader('eyebrow') }}</span>
        </div>
        <h2 class="section-title section-title-large">
          {{ sectionHeader('title') }}
        </h2>
        <p class="section-subtitle">
          {{ sectionHeader('subtitle') }}
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
                    <option value="general">General Enquiry</option>
                    <option value="custom-pipeline">Custom Managed Data Pipeline Enquiry</option>
                    <option value="shop-waitlist">Join the OhmBase/Hardware Shop Waitlist</option>
                    <option value="dataset-dump-request">Once-off Dataset Enquiry</option>
                    <option value="dataset-dump-request">Developer API Enquiry</option>
                    <option value="pro-dashboard-request">Pro Analytics Enquiry</option>
                    <option value="refund">Request a Refund (Have you read the Refund Policy?)</option>
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
  text-align: left;
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
  transition: opacity 1.2s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1)), transform 1.2s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1)), filter 1.2s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1));
  filter: blur(8px);
  will-change: transform, opacity, filter;
}

.reveal-item.revealed {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
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
  animation: fadeIn 0.5s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1));
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

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@media (max-width: 768px) {
  .contact-section {
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
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 28px;
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
