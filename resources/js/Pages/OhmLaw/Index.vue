<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Skeleton from 'primevue/skeleton';
import type { DataTableLazyLoadEvent } from 'primevue/datatable';

interface RecordSummary {
  id: string;
  record_type: string;
  document_date: string | null;
  court: string;
  case_number: string | null;
  title: string;
  source_url: string | null;
  result: string | null;
  reason_for_dismissal: string | null;
  summary: string | null;
}

const records = ref<RecordSummary[]>([]);
const totalRecords = ref(0);
const loading = ref(false);
const searchQuery = ref('');
const selectedRecordType = ref('');
const selectedCourt = ref('');

// Record detail modal state
const detailModalVisible = ref(false);
const detailLoading = ref(false);
const selectedDetail = ref<any>(null);

// Options for record type filter
const recordTypeOptions = [
  { label: 'All Record Types', value: '' },
  { label: 'CCMA Arbitration Awards (sabinet_ccma)', value: 'sabinet_ccma' },
  { label: 'SAFLII Court Judgments (saflii_courts)', value: 'saflii_courts' }
];

// Current lazy load parameters
const lazyParams = ref<DataTableLazyLoadEvent>({
  first: 0,
  rows: 25,
  sortField: 'created_at',
  sortOrder: -1
});

let searchDebounceTimer: any = null;

const loadLazyRecords = async (event?: DataTableLazyLoadEvent) => {
  loading.value = true;
  const currentParams = event || lazyParams.value;

  const first = currentParams.first || 0;
  const rows = currentParams.rows || 25;
  const sortField = (currentParams.sortField as string) || 'created_at';
  const sortOrder = currentParams.sortOrder || -1;

  try {
    const response = await axios.get('/ohmlaw/data', {
      params: {
        offset: first,
        limit: rows,
        search: searchQuery.value,
        record_type: selectedRecordType.value,
        court: selectedCourt.value,
        sort_field: sortField,
        sort_order: sortOrder
      }
    });

    records.value = response.data.records;
    totalRecords.value = response.data.total;
  } catch (error) {
    console.error('Failed to fetch legal records:', error);
  } finally {
    loading.value = false;
  }
};

const onLazy = (event: DataTableLazyLoadEvent) => {
  lazyParams.value = event;
  loadLazyRecords(event);
};

const onSearchInput = () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    lazyParams.value.first = 0;
    loadLazyRecords();
  }, 350);
};

const onFilterChange = () => {
  lazyParams.value.first = 0;
  loadLazyRecords();
};

const viewRecordDetail = async (record: RecordSummary) => {
  detailModalVisible.value = true;
  detailLoading.value = true;
  selectedDetail.value = null;

  try {
    const response = await axios.get(`/ohmlaw/record/${record.id}`);
    selectedDetail.value = response.data;
  } catch (error) {
    console.error('Failed to load record details:', error);
  } finally {
    detailLoading.value = false;
  }
};

const getCourtSeverity = (court: string) => {
  if (!court) return 'secondary';
  const c = court.toUpperCase();
  if (c.includes('CCMA')) return 'info';
  if (c.includes('ZACC')) return 'warn';
  if (c.includes('ZASCA')) return 'success';
  if (c.includes('LABOUR')) return 'help';
  return 'contrast';
};

const formatValue = (val: any) => {
  if (val === null || val === undefined) return '';
  if (typeof val === 'object') return JSON.stringify(val, null, 2);
  return String(val);
};

onMounted(() => {
  loadLazyRecords();
});
</script>

<template>
  <Head title="OHMLaw | Legal Search & Intelligence Portal" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
    <!-- Header Navigation -->
    <header class="border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black text-xl shadow-md">
            ⚖️
          </div>
          <div>
            <h1 class="text-xl font-bold tracking-tight">OHMLaw Intelligence</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">South African Legal Records & CCMA Award Search Engine</p>
          </div>
        </div>

        <div class="flex items-center space-x-4">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            630,000+ Records Indexed
          </span>
          <a href="/dashboard" class="text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white font-medium">
            Dashboard &rarr;
          </a>
        </div>
      </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

      <!-- Filter & Search Controls Bar -->
      <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
          
          <!-- Global Search Input -->
          <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <i class="ph ph-magnifying-glass text-lg"></i>
            </span>
            <InputText
              v-model="searchQuery"
              @input="onSearchInput"
              placeholder="Search by Case #, Applicant, Respondent, Court, or Keywords..."
              class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Filters -->
          <div class="flex flex-wrap items-center gap-3">
            <Select
              v-model="selectedRecordType"
              :options="recordTypeOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter Record Type"
              @change="onFilterChange"
              class="w-56 text-sm"
            />
            <Button
              icon="pi pi-refresh"
              severity="secondary"
              text
              rounded
              aria-label="Refresh"
              @click="loadLazyRecords()"
              title="Refresh Table"
            />
          </div>
        </div>
      </div>

      <!-- PrimeVue Lazy DataTable Container -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden p-2">
        <DataTable
          :value="records"
          :lazy="true"
          :totalRecords="totalRecords"
          :loading="loading"
          @lazy="onLazy"
          paginator
          :rows="25"
          :rowsPerPageOptions="[10, 25, 50, 100]"
          dataKey="id"
          stateStorage="local"
          stateKey="ohmlaw-datatable-state"
          tableStyle="min-width: 60rem"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="text-center py-12 text-slate-500">
              <i class="ph ph-folder-open text-4xl mb-2"></i>
              <p class="text-base font-medium">No legal records found matching your filters.</p>
            </div>
          </template>

          <Column field="case_number" header="Case Reference" sortable style="width: 18%">
            <template #body="{ data }">
              <span v-if="data.case_number" class="font-mono text-xs font-semibold px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                {{ data.case_number }}
              </span>
              <span v-else class="text-xs text-slate-400 italic">N/A</span>
            </template>
            <template #loading>
              <Skeleton width="80%" height="1.5rem" />
            </template>
          </Column>

          <Column field="court" header="Court / Forum" sortable style="width: 16%">
            <template #body="{ data }">
              <Tag :value="data.court" :severity="getCourtSeverity(data.court)" class="text-xs font-bold uppercase tracking-wider" />
            </template>
            <template #loading>
              <Skeleton width="60%" height="1.5rem" />
            </template>
          </Column>

          <Column field="document_date" header="Date" sortable style="width: 14%">
            <template #body="{ data }">
              <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                {{ data.document_date || 'N/A' }}
              </span>
            </template>
            <template #loading>
              <Skeleton width="70%" height="1.5rem" />
            </template>
          </Column>

          <Column field="title" header="Title / Matter" style="width: 42%">
            <template #body="{ data }">
              <div class="font-semibold text-sm text-slate-900 dark:text-slate-100 hover:text-blue-600 transition cursor-pointer" @click="viewRecordDetail(data)">
                {{ data.title }}
              </div>
              <div v-if="data.summary" class="text-xs text-slate-500 line-clamp-1 mt-0.5">
                {{ data.summary }}
              </div>
            </template>
            <template #loading>
              <Skeleton width="90%" height="1.5rem" />
            </template>
          </Column>

          <Column header="Actions" style="width: 10%" class="text-right">
            <template #body="{ data }">
              <Button
                icon="pi pi-eye"
                label="View"
                severity="primary"
                size="small"
                outlined
                @click="viewRecordDetail(data)"
              />
            </template>
          </Column>
        </DataTable>
      </div>

    </main>

    <!-- Judgment / Document Viewer Modal Dialog -->
    <Dialog
      v-model:visible="detailModalVisible"
      modal
      header="Legal Document View"
      :style="{ width: '80vw', maxWidth: '1000px' }"
      :breakpoints="{ '960px': '90vw', '640px': '95vw' }"
      dismissableMask
    >
      <div v-if="detailLoading" class="p-8 space-y-4">
        <Skeleton width="60%" height="2rem" />
        <Skeleton width="40%" height="1.2rem" />
        <Skeleton width="100%" height="12rem" />
      </div>

      <div v-else-if="selectedDetail" class="space-y-6 p-2">
        <!-- Title & Metadata Header -->
        <div class="border-b border-slate-200 dark:border-slate-800 pb-4">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
            {{ selectedDetail.data.title || selectedDetail.data.name || 'Legal Record Detail' }}
          </h2>
          <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600 dark:text-slate-400">
            <span v-if="selectedDetail.data.court"><strong>Forum:</strong> {{ selectedDetail.data.court }}</span>
            <span v-if="selectedDetail.data.case_number"><strong>Case #:</strong> {{ selectedDetail.data.case_number }}</span>
            <span v-if="selectedDetail.document_date"><strong>Date:</strong> {{ selectedDetail.document_date }}</span>
            <a v-if="selectedDetail.source_url" :href="selectedDetail.source_url" target="_blank" class="text-blue-600 hover:underline flex items-center gap-1">
              Source <i class="ph ph-arrow-square-out"></i>
            </a>
          </div>
        </div>

        <!-- Highlight Callouts -->
        <div v-if="selectedDetail.data.result || selectedDetail.data.order || selectedDetail.data.holding" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-900 dark:text-emerald-200 text-sm">
          <strong class="font-bold block mb-1">Holding & Final Order:</strong>
          <p>{{ selectedDetail.data.result || selectedDetail.data.order || selectedDetail.data.holding }}</p>
        </div>

        <div v-if="selectedDetail.data.reason_for_dismissal" class="p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-red-900 dark:text-red-200 text-sm">
          <strong class="font-bold block mb-1">Reason for Dismissal:</strong>
          <p>{{ selectedDetail.data.reason_for_dismissal }}</p>
        </div>

        <div v-if="selectedDetail.data.ai_summary || selectedDetail.data.summary" class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-900 dark:text-amber-200 text-sm">
          <strong class="font-bold block mb-1">📖 Summary & Headnotes:</strong>
          <p class="whitespace-pre-line">{{ selectedDetail.data.ai_summary || selectedDetail.data.summary }}</p>
        </div>

        <!-- Full Document Text -->
        <div v-if="selectedDetail.data.full_text || selectedDetail.data.text || selectedDetail.data.content" class="space-y-2">
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">📄 Full Document Text</h3>
          <div class="p-6 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 font-serif text-sm leading-relaxed text-slate-800 dark:text-slate-200 whitespace-pre-line max-h-[500px] overflow-y-auto">
            {{ selectedDetail.data.full_text || selectedDetail.data.text || selectedDetail.data.content }}
          </div>
        </div>

        <!-- Dynamic Payload Data Grid -->
        <div class="space-y-2">
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">📋 Metadata Attributes</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs bg-slate-50 dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
            <template v-for="(val, key) in selectedDetail.data" :key="key">
              <div v-if="val && !['full_text', 'text', 'content'].includes(String(key))" class="flex flex-col">
                <span class="font-semibold text-slate-500 capitalize">{{ String(key).replace(/_/g, ' ') }}:</span>
                <span class="font-mono text-slate-800 dark:text-slate-200 break-all">{{ formatValue(val) }}</span>
              </div>
            </template>
          </div>
        </div>
      </div>
    </Dialog>

  </div>
</template>

<style scoped>
:deep(.p-datatable) {
  font-family: inherit;
}
</style>
