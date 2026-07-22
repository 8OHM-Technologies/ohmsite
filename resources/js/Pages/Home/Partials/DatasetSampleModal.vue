<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  datasets: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close'])

const selectedDataset = ref(null)
const demoData = ref(null)
const loading = ref(false)
const errorMsg = ref(null)
const copying = ref(false)

const selectDataset = async (dataset) => {
  if (selectedDataset.value?.id === dataset.id) return

  selectedDataset.value = dataset
  demoData.value = null
  loading.value = true
  errorMsg.value = null

  try {
    const response = await fetch(`/datasets/${dataset.slug}/sample`)
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()
    demoData.value = data
  } catch (err) {
    console.error('Failed to load dataset sample:', err)
    errorMsg.value = 'Failed to load sample data. Please try again later.'
  } finally {
    loading.value = false
  }
}

const copyToClipboard = () => {
  if (!demoData.value) return
  copying.value = true
  navigator.clipboard.writeText(JSON.stringify(demoData.value, null, 2))
    .then(() => {
      setTimeout(() => {
        copying.value = false
      }, 2000)
    })
    .catch((err) => {
      console.error('Failed to copy to clipboard:', err)
      copying.value = false
    })
}

// Watch for show state to select first dataset by default
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
    if (props.datasets.length > 0 && !selectedDataset.value) {
      selectDataset(props.datasets[0])
    }
  } else {
    document.body.style.overflow = ''
  }
})

// Escape key to close
const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) {
    emit('close')
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Transition name="fade">
    <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
      <Transition name="zoom">
        <div class="modal-card">
          <!-- Modal Header -->
          <div class="modal-header">
            <div class="header-left">
              <div class="db-icon-container">
                <i class="ph-light ph-database"></i>
              </div>
              <div>
                <h3 class="modal-title">Explore Dataset Samples</h3>
                <p class="modal-subtitle">Preview the structure of the dataset (uses synthetic data modelled on the
                  actual dataset schema) </p>
              </div>
            </div>
            <button @click="$emit('close')" class="close-btn">
              <i class="ph-light ph-x"></i>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body">
            <!-- Sidebar: Dataset Selection List -->
            <div class="sidebar">
              <span class="sidebar-label">Available Datasets</span>
              <div class="dataset-list">
                <button v-for="dataset in datasets" :key="dataset.id" @click="selectDataset(dataset)"
                  class="dataset-item-btn" :class="{ active: selectedDataset?.id === dataset.id }">
                  <div class="dataset-item-content">
                    <span class="dataset-item-name">{{ dataset.name }}</span>
                    <span class="dataset-item-desc">{{ dataset.description || 'No description provided.' }}</span>
                  </div>
                  <i class="ph-light ph-caret-right caret-icon"></i>
                </button>
              </div>
            </div>

            <!-- Content Area: JSON Viewer -->
            <div class="viewer-area">
              <template v-if="selectedDataset">
                <div class="viewer-header">
                  <div class="viewer-header-info">
                    <span class="viewer-dataset-name">{{ selectedDataset.name }}</span>
                    <span class="viewer-dataset-slug">slug: {{ selectedDataset.slug }}</span>
                  </div>
                  <button v-if="demoData" @click="copyToClipboard" class="copy-btn" :class="{ success: copying }"
                    :disabled="copying">
                    <i v-if="copying" class="ph-light ph-check"></i>
                    <i v-else class="ph-light ph-copy"></i>
                    <span>{{ copying ? 'Copied!' : 'Copy JSON' }}</span>
                  </button>
                </div>

                <div class="viewer-content">
                  <!-- Loading State -->
                  <div v-if="loading" class="loader-container">
                    <div class="spinner">
                      <i class="ph-light ph-spinner-gap animate-spin"></i>
                    </div>
                    <span class="loader-text">Loading dataset sample...</span>
                  </div>

                  <!-- Error State -->
                  <div v-else-if="errorMsg" class="error-container">
                    <i class="ph-light ph-warning-circle error-icon"></i>
                    <span class="error-text">{{ errorMsg }}</span>
                  </div>

                  <!-- JSON Viewer -->
                  <pre v-else-if="demoData" class="json-code"><code>{{ JSON.stringify(demoData, null, 2) }}</code></pre>

                  <!-- Empty State -->
                  <div v-else class="empty-data-container">
                    <i class="ph-light ph-file-code empty-icon"></i>
                    <span class="empty-text">No sample data available for this dataset.</span>
                  </div>
                </div>
              </template>

              <template v-else>
                <div class="empty-state">
                  <i class="ph-light ph-database empty-icon-big animate-pulse"></i>
                  <span class="empty-state-title">Select a Dataset</span>
                  <span class="empty-state-subtitle">Select a dataset from the list to preview its structured JSON
                    payload</span>
                </div>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<style scoped>
/* --- Transitions --- */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1));
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.zoom-enter-active,
.zoom-leave-active {
  transition: transform 0.4s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1)), opacity 0.4s var(--ease-premium, cubic-bezier(0.16, 1, 0.3, 1));
}

.zoom-enter-from,
.zoom-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(10px);
}

/* --- Modal Base --- */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background-color: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.modal-card {
  width: 100%;
  max-width: 1100px;
  height: 80vh;
  min-height: 500px;
  display: flex;
  flex-direction: column;
  background-color: rgba(10, 10, 12, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 40px rgba(255, 136, 0, 0.05);
}

/* --- Modal Header --- */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 32px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  background-color: rgba(255, 255, 255, 0.01);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.db-icon-container {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background-color: rgba(255, 136, 0, 0.08);
  border: 1px solid rgba(255, 136, 0, 0.15);
  color: #ff8800;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.modal-title {
  font-family: var(--font-display, sans-serif);
  font-size: 1.25rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.5px;
  text-transform: uppercase;
  margin: 0;
}

.modal-subtitle {
  font-size: 0.75rem;
  font-weight: 600;
  color: #71717a;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  margin: 4px 0 0 0;
}

.close-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background-color: rgba(255, 255, 255, 0.02);
  color: #a1a1aa;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.close-btn:hover {
  background-color: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.15);
  transform: rotate(90deg);
}

/* --- Modal Body --- */
.modal-body {
  flex: 1;
  display: grid;
  grid-template-columns: 320px 1fr;
  overflow: hidden;
  background-color: rgba(0, 0, 0, 0.2);
}

/* --- Sidebar --- */
.sidebar {
  border-right: 1px solid rgba(255, 255, 255, 0.06);
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  overflow-y: auto;
  background-color: rgba(255, 255, 255, 0.005);
}

.sidebar-label {
  font-size: 0.7rem;
  font-weight: 800;
  color: #52525b;
  letter-spacing: 1.5px;
  text-transform: uppercase;
}

.dataset-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.dataset-item-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  text-align: left;
  padding: 16px;
  background-color: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 14px;
  color: #d4d4d8;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  outline: none;
}

.dataset-item-btn:hover {
  background-color: rgba(255, 136, 0, 0.03);
  border-color: rgba(255, 136, 0, 0.15);
  transform: translateX(4px);
}

.dataset-item-btn.active {
  background-color: rgba(255, 136, 0, 0.08);
  border-color: rgba(255, 136, 0, 0.4);
  color: #ff8800;
  box-shadow: 0 4px 20px rgba(255, 136, 0, 0.15);
}

.dataset-item-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  max-width: 90%;
}

.dataset-item-name {
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: -0.2px;
}

.dataset-item-btn.active .dataset-item-name {
  color: #ffffff;
}

.dataset-item-desc {
  font-size: 0.7rem;
  font-weight: 500;
  color: #71717a;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dataset-item-btn.active .dataset-item-desc {
  color: rgba(255, 255, 255, 0.5);
}

.caret-icon {
  font-size: 0.85rem;
  opacity: 0;
  transition: all 0.3s ease;
}

.dataset-item-btn:hover .caret-icon,
.dataset-item-btn.active .caret-icon {
  opacity: 1;
  transform: translateX(2px);
}

/* --- Viewer Area --- */
.viewer-area {
  padding: 32px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.viewer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  flex-shrink: 0;
}

.viewer-header-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.viewer-dataset-name {
  font-family: var(--font-display, sans-serif);
  font-size: 1.15rem;
  font-weight: 800;
  color: #ffffff;
  text-transform: uppercase;
  letter-spacing: -0.5px;
}

.viewer-dataset-slug {
  font-family: var(--font-mono, monospace);
  font-size: 0.65rem;
  font-weight: 700;
  color: #a1a1aa;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.copy-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 10px;
  background-color: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #e4e4e7;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.copy-btn:hover {
  background-color: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.15);
  color: #ffffff;
}

.copy-btn.success {
  background-color: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.3);
  color: #10b981;
}

.viewer-content {
  flex: 1;
  background-color: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  overflow: hidden;
  position: relative;
  display: flex;
}

/* --- Loader & Error & Empty --- */
.loader-container,
.error-container,
.empty-data-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  color: #a1a1aa;
  padding: 40px;
  text-align: center;
}

.spinner {
  font-size: 2.25rem;
  color: #ff8800;
}

.loader-text {
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #71717a;
}

.error-icon {
  font-size: 2.5rem;
  color: #ef4444;
}

.error-text {
  font-size: 0.85rem;
  font-weight: 600;
  color: #a1a1aa;
}

.empty-icon {
  font-size: 2.5rem;
  color: #52525b;
}

.empty-text {
  font-size: 0.8rem;
  font-weight: 600;
  color: #71717a;
}

/* --- JSON pre block --- */
.json-code {
  flex: 1;
  margin: 0;
  padding: 24px;
  overflow: auto;
  font-family: var(--font-mono, monospace);
  font-size: 0.8rem;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.85);
  background-color: transparent;
}

.json-code::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.json-code::-webkit-scrollbar-track {
  background: transparent;
}

.json-code::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.08);
  border-radius: 4px;
}

.json-code::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.15);
}

/* --- Large Empty State --- */
.empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 48px;
}

.empty-icon-big {
  font-size: 4rem;
  color: rgba(255, 255, 255, 0.02);
  margin-bottom: 24px;
}

.empty-state-title {
  font-family: var(--font-display, sans-serif);
  font-size: 1.25rem;
  font-weight: 800;
  color: #52525b;
  text-transform: uppercase;
  letter-spacing: -0.5px;
  margin-bottom: 8px;
}

.empty-state-subtitle {
  font-size: 0.8rem;
  font-weight: 500;
  color: #3f3f46;
  max-width: 320px;
  line-height: 1.5;
}

/* --- Responsive Adjustments --- */
@media (max-width: 768px) {
  .modal-overlay {
    padding: 12px;
  }

  .modal-card {
    height: 90vh;
  }

  .modal-header {
    padding: 16px 20px;
  }

  .modal-body {
    grid-template-columns: 1fr;
    grid-template-rows: 200px 1fr;
  }

  .sidebar {
    border-right: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    padding: 16px;
  }

  .viewer-area {
    padding: 16px;
  }

  .dataset-list {
    flex-direction: row;
    overflow-x: auto;
    padding-bottom: 8px;
  }

  .dataset-item-btn {
    min-width: 240px;
    padding: 12px;
    flex-shrink: 0;
  }

  .dataset-item-btn:hover,
  .dataset-item-btn.active {
    transform: none;
  }
}
</style>
