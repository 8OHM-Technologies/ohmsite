<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SubscriberLayout from '@/Layouts/SubscriberLayout.vue';
import axios from 'axios';
import {
  Scale,
  Search,
  Filter,
  Eye,
  RefreshCw,
  Calendar,
  FileText,
  ChevronRight,
  X,
  ExternalLink,
  Database,
  CheckCircle2,
  AlertTriangle,
  FileCheck
} from 'lucide-vue-next';

// PrimeVue Free Components (Open Source MIT)
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Modal from '@/Components/Modal.vue';
import Skeleton from 'primevue/skeleton';
import type { DataTablePageEvent, DataTableSortEvent, DataTableFilterEvent } from 'primevue/datatable';

type DataTableLazyLoadEvent = DataTablePageEvent | DataTableSortEvent | DataTableFilterEvent;

interface AuthUser {
  id: number;
  role: string;
  first_name?: string;
  last_name?: string;
  email?: string;
}

// Dynamic Layout Selection based on User Role (Admin vs Subscriber)
const page = usePage();
const user = computed(() => (page.props.auth?.user as unknown as AuthUser | null) || null);
const isAdmin = computed(() => user.value && user.value.role === 'admin');
const LayoutComponent = computed(() => isAdmin.value ? AdminLayout : SubscriberLayout);

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

// Document Detail Dialog state
const detailModalVisible = ref(false);
const detailLoading = ref(false);
const selectedDetail = ref<any>(null);

// Lazy parameter state
const lazyParams = ref<Partial<DataTableLazyLoadEvent>>({
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

const setRecordType = (type: string) => {
  selectedRecordType.value = type;
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

const formatValue = (val: any) => {
  if (val === null || val === undefined) return '';
  if (typeof val === 'object') return JSON.stringify(val, null, 2);
  return String(val);
};

onMounted(() => {
  loadLazyRecords();
});

// Document-specific category helpers and metadata filters
const EXCLUDED_KEYS = new Set([
  'detail_url',
  'detail_title',
  'index_scraped_at',
  'preview_image_url',
  'details_scraped_at',
  'scraped_at',
  'source_url',
  'metadata'
]);

const SPECIAL_LAYOUT_KEYS = new Set([
  'result',
  'order',
  'holding',
  'reason_for_dismissal',
  'dismissal_reason',
  'reasons_for_dismissal',
  'summary',
  'ai_summary',
  'headnotes',
  'abstract',
  'full_text',
  'text',
  'content',
  'raw_text',
  'judgment_text',
  'title',
  'name',
  'heading'
]);

const getDocumentType = (record: any): 'case' | 'gazette' | 'journal' | 'court_roll' => {
  if (!record) return 'case';
  const url = (record.source_url || '').toLowerCase();
  const type = (record.record_type || '').toLowerCase();

  if (url.includes('/gaz/') || url.includes('gazette') || type.includes('gazette') || type.includes('gaz')) {
    return 'gazette';
  }
  if (url.includes('/journals/') || url.includes('journal') || type.includes('journal')) {
    return 'journal';
  }
  if (url.includes('/rolls/') || url.includes('/other/') || url.includes('courtroll') || url.includes('roll') || type.includes('roll')) {
    return 'court_roll';
  }
  return 'case';
};

const getDocumentBodyText = (recordData: any) => {
  if (!recordData) return '';
  return recordData.full_text || recordData.text || recordData.content || recordData.raw_text || recordData.judgment_text || '';
};

const getDocumentSummary = (recordData: any) => {
  if (!recordData) return '';
  return recordData.ai_summary || recordData.summary || recordData.headnotes || recordData.abstract || '';
};

const getDocumentHolding = (recordData: any) => {
  if (!recordData) return '';
  return recordData.result || recordData.order || recordData.holding || '';
};

const getDocumentDismissal = (recordData: any) => {
  if (!recordData) return '';
  return recordData.reason_for_dismissal || recordData.dismissal_reason || recordData.reasons_for_dismissal || '';
};

const getFilteredMetadata = (recordData: any) => {
  if (!recordData) return [];

  const courtForumValues: string[] = [];
  const courtForumKeysToExclude = new Set<string>();

  for (const key of Object.keys(recordData)) {
    const val = recordData[key];
    if (val === null || val === undefined || val === '') continue;
    const kNorm = key.toLowerCase().replace(/_/g, ' ').trim();
    if (
      kNorm === 'court' ||
      kNorm === 'forum' ||
      kNorm === 'court forum' ||
      kNorm === 'court/forum' ||
      kNorm === 'forum/court' ||
      kNorm === 'court / forum'
    ) {
      courtForumKeysToExclude.add(key);
      const valStr = String(val).trim();
      if (valStr && !courtForumValues.includes(valStr)) {
        courtForumValues.push(valStr);
      }
    }
  }

  const items: { label: string; value: any }[] = [];

  if (courtForumValues.length > 0) {
    items.push({
      label: 'Court/Forum',
      value: courtForumValues.join(' / '),
    });
  }

  for (const key of Object.keys(recordData)) {
    const val = recordData[key];
    if (val === null || val === undefined || val === '') continue;
    if (EXCLUDED_KEYS.has(key) || SPECIAL_LAYOUT_KEYS.has(key) || courtForumKeysToExclude.has(key)) {
      continue;
    }

    const label = key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    items.push({ label, value: val });
  }

  return items.sort((a, b) => a.label.toLowerCase().localeCompare(b.label.toLowerCase()));
};
</script>

<template>
  <Head title="OHMLaw - Legal Intelligence" />

  <component :is="LayoutComponent">
    <!-- Page Header conforming to ohmsite design system -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 lg:mb-12 gap-6">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <div class="w-2 h-8 bg-admin-modern rounded-full"></div>
          <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tighter text-primary">
            OHMLaw Intelligence
          </h1>
        </div>
        <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">
          South African Legal Records & CCMA Dispute Intelligence Engine (630,000+ Indexed Records)
        </p>
      </div>

      <div class="flex items-center gap-3">
        <span class="inline-flex items-center gap-2 px-4 py-3 bg-zinc-900 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-admin-modern shadow-md">
          <span class="w-2 h-2 rounded-full bg-admin-modern animate-pulse"></span>
          {{ totalRecords.toLocaleString() }} Active Records
        </span>
      </div>
    </div>

    <!-- Filter & Search Controls Container -->
    <div class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden p-6 sm:p-8 space-y-6 mb-8">
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        
        <!-- Global Search Field -->
        <div class="relative flex-1 max-w-2xl">
          <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
          <input
            type="text"
            v-model="searchQuery"
            @input="onSearchInput"
            placeholder="Search by Case #, Applicant, Respondent, Court, or Keywords..."
            class="w-full bg-black/60 border border-white/10 rounded-xl py-3.5 pl-11 pr-4 text-xs font-bold text-white focus:ring-1 focus:ring-admin-modern/50 focus:border-admin-modern/50 placeholder:text-zinc-500 shadow-inner"
          />
        </div>

        <!-- Filter Buttons with High Contrast -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="setRecordType('')"
            class="px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all"
            :class="selectedRecordType === '' ? 'bg-white text-black border-white shadow-lg' : 'bg-zinc-800 text-zinc-300 border-white/10 hover:text-white hover:bg-zinc-700'"
          >
            All Records
          </button>

          <button
            @click="setRecordType('sabinet_ccma')"
            class="px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all"
            :class="selectedRecordType === 'sabinet_ccma' ? 'bg-admin-modern text-black border-admin-modern shadow-lg' : 'bg-zinc-800 text-zinc-300 border-white/10 hover:text-white hover:bg-zinc-700'"
          >
            CCMA Awards
          </button>

          <button
            @click="setRecordType('saflii_courts')"
            class="px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all"
            :class="selectedRecordType === 'saflii_courts' ? 'bg-admin-modern text-black border-admin-modern shadow-lg' : 'bg-zinc-800 text-zinc-300 border-white/10 hover:text-white hover:bg-zinc-700'"
          >
            SAFLII Courts
          </button>

          <button
            @click="loadLazyRecords()"
            class="p-3 bg-zinc-800 border border-white/10 text-zinc-300 hover:text-white hover:bg-zinc-700 rounded-xl transition-all flex items-center justify-center"
            title="Refresh Dataset"
          >
            <RefreshCw class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- PrimeVue Free Version DataTable Container -->
    <div class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden p-6 sm:p-8">
      <DataTable
        :value="records"
        :lazy="true"
        :totalRecords="totalRecords"
        :loading="loading"
        @page="onLazy"
        @sort="onLazy"
        @filter="onLazy"
        paginator
        :rows="25"
        :rowsPerPageOptions="[10, 25, 50, 100]"
        dataKey="id"
        stateStorage="local"
        stateKey="ohmlaw-datatable-state"
        tableStyle="min-width: 60rem"
        class="p-datatable-dark-custom"
      >
        <template #empty>
          <div class="py-20 text-center flex flex-col items-center">
            <div class="w-16 h-16 bg-zinc-800/50 rounded-full flex items-center justify-center mb-4 border border-white/5">
              <Database class="w-8 h-8 text-zinc-600" />
            </div>
            <h3 class="text-xl font-black uppercase tracking-tighter text-zinc-400 mb-1">No legal records found</h3>
            <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">Try adjusting your search terms or filters</p>
          </div>
        </template>

        <Column field="case_number" header="Case Reference" sortable style="width: 18%">
          <template #body="{ data }">
            <span v-if="data.case_number" class="font-mono text-xs font-bold px-3 py-1.5 bg-black/60 border border-white/10 text-white rounded-lg inline-block shadow-sm">
              {{ data.case_number }}
            </span>
            <span v-else class="text-xs text-zinc-500 font-bold uppercase tracking-widest">N/A</span>
          </template>
          <template #loading>
            <Skeleton width="80%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="court" header="Court / Forum" sortable style="width: 16%">
          <template #body="{ data }">
            <span class="px-3 py-1 bg-admin-modern/10 border border-admin-modern/30 text-admin-modern font-black text-[10px] uppercase tracking-wider rounded-lg inline-block shadow-sm">
              {{ data.court }}
            </span>
          </template>
          <template #loading>
            <Skeleton width="60%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="document_date" header="Date" sortable style="width: 14%">
          <template #body="{ data }">
            <span class="text-xs font-bold text-zinc-300 tracking-wider">
              {{ data.document_date || 'N/A' }}
            </span>
          </template>
          <template #loading>
            <Skeleton width="70%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="title" header="Title / Matter" style="width: 40%">
          <template #body="{ data }">
            <div class="font-black text-sm text-white uppercase tracking-tight hover:text-admin-modern transition cursor-pointer" @click="viewRecordDetail(data)">
              {{ data.title }}
            </div>
            <div v-if="data.summary" class="text-[10px] text-zinc-400 font-medium line-clamp-1 mt-1">
              {{ data.summary }}
            </div>
          </template>
          <template #loading>
            <Skeleton width="90%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column header="Actions" style="width: 12%" class="text-right">
          <template #body="{ data }">
            <button
              @click="viewRecordDetail(data)"
              class="w-full sm:w-auto bg-white text-black px-4 py-2 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-admin-modern hover:text-black transition-all shadow-md active:scale-95 flex items-center justify-center gap-1.5"
            >
              <Eye class="w-3.5 h-3.5" />
              View
            </button>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Document Detail Modal -->
    <Modal :show="detailModalVisible" @close="detailModalVisible = false" maxWidth="5xl">
      <div class="relative bg-zinc-950 text-white rounded-2xl border border-white/10 overflow-hidden shadow-2xl max-h-[95vh] flex flex-col">
        <!-- Top Sticky Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-zinc-900/60 backdrop-blur-md sticky top-0 z-10">
          <div class="flex items-center gap-2">
            <Scale class="w-5 h-5 text-admin-modern" />
            <span class="text-xs font-black uppercase tracking-widest text-zinc-400">Legal Document Intelligence</span>
          </div>
          <button 
            @click="detailModalVisible = false" 
            class="p-1.5 bg-zinc-800/80 border border-white/10 text-zinc-400 hover:text-white hover:bg-zinc-700 rounded-lg transition-all"
            title="Close Document"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Scrollable content area -->
        <div class="flex-1 overflow-y-auto p-6 sm:p-8 custom-scrollbar">
          <!-- Loading State -->
          <div v-if="detailLoading" class="space-y-6">
            <Skeleton width="60%" height="2rem" class="bg-zinc-800/60" />
            <Skeleton width="40%" height="1.2rem" class="bg-zinc-800/60" />
            <Skeleton width="100%" height="16rem" class="bg-zinc-800/60 rounded-xl" />
          </div>

          <!-- Document Detail Loaded -->
          <div v-else-if="selectedDetail" class="space-y-6">
            
            <!-- Category-specific layouts -->
            
            <!-- 1. COURT ROLLS LAYOUT (Compact, one-liner style) -->
            <div v-if="getDocumentType(selectedDetail) === 'court_roll'" class="space-y-4">
              <div class="flex items-start gap-4 p-5 rounded-2xl bg-zinc-900/60 border border-white/5 shadow-inner">
                <div class="w-10 h-10 rounded-xl bg-zinc-800 flex items-center justify-center border border-white/10 text-admin-modern shrink-0">
                  <Calendar class="w-5 h-5" />
                </div>
                <div class="space-y-2 flex-1">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-admin-modern/10 border border-admin-modern/30 text-admin-modern text-[9px] font-black uppercase tracking-widest rounded">Court Roll</span>
                    <span v-if="selectedDetail.document_date" class="text-zinc-500 text-[10px] font-bold uppercase tracking-wider">{{ selectedDetail.document_date }}</span>
                  </div>
                  <h2 class="text-lg font-black uppercase tracking-tight text-white leading-snug">
                    {{ selectedDetail.data.title || selectedDetail.data.name || 'Court Roll Schedule' }}
                  </h2>
                  <p class="text-xs font-bold text-zinc-400">
                    <span class="text-zinc-600 uppercase tracking-widest text-[9px] mr-1">Forum:</span>
                    {{ selectedDetail.data.court || selectedDetail.record_type }}
                  </p>
                </div>
              </div>

              <!-- Compact Text Block -->
              <div v-if="getDocumentBodyText(selectedDetail.data)" class="p-6 rounded-2xl bg-black/60 border border-white/10 font-serif text-sm leading-relaxed text-zinc-300 whitespace-pre-line max-h-[350px] overflow-y-auto custom-scrollbar">
                {{ getDocumentBodyText(selectedDetail.data) }}
              </div>
              
              <!-- Clean minimal detail list -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-zinc-900/30 p-4 rounded-xl border border-white/5">
                <div v-for="item in getFilteredMetadata(selectedDetail.data)" :key="item.label" class="flex items-center justify-between py-1 border-b border-white/5 last:border-0">
                  <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">{{ item.label }}</span>
                  <span class="font-mono text-zinc-300 font-bold truncate max-w-[200px]">{{ formatValue(item.value) }}</span>
                </div>
                <div v-if="selectedDetail.source_url" class="flex items-center justify-between py-1 col-span-1 sm:col-span-2 border-t border-white/5 mt-1 pt-2">
                  <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Source Link</span>
                  <a :href="selectedDetail.source_url" target="_blank" class="text-admin-modern hover:underline flex items-center gap-1 font-bold">
                    Go to Original <ExternalLink class="w-3 h-3" />
                  </a>
                </div>
              </div>
            </div>

            <!-- 2. GAZETTE & JOURNAL LAYOUT (Formatted, premium printed text document) -->
            <div v-else-if="['gazette', 'journal'].includes(getDocumentType(selectedDetail))" class="max-w-3xl mx-auto space-y-8 py-2">
              
              <!-- Document Header Block -->
              <div class="border-b-2 border-white/10 pb-6 text-center space-y-4">
                <div class="flex items-center justify-center gap-2">
                  <span class="px-3 py-1 bg-white text-black text-[9px] font-black uppercase tracking-[0.2em] rounded-full">
                    {{ getDocumentType(selectedDetail) === 'gazette' ? 'Official Gazette Notice' : 'Academic Law Journal' }}
                  </span>
                </div>

                <h1 class="font-serif text-3xl sm:text-4xl font-black text-white tracking-tight leading-snug max-w-2xl mx-auto">
                  {{ selectedDetail.data.title || selectedDetail.data.name || 'Publication Document' }}
                </h1>

                <!-- Formal Sub-Header -->
                <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-[10px] font-black uppercase tracking-widest text-zinc-400">
                  <span v-if="selectedDetail.data.publisher || selectedDetail.data.journal_name" class="flex items-center gap-1">
                    <span class="text-zinc-600">Publisher:</span> 
                    {{ selectedDetail.data.publisher || selectedDetail.data.journal_name }}
                  </span>
                  <span v-if="selectedDetail.data.gazette_number || selectedDetail.data.volume" class="flex items-center gap-1">
                    <span class="text-zinc-600">Reference:</span> 
                    <code>{{ selectedDetail.data.gazette_number || selectedDetail.data.volume }}</code>
                  </span>
                  <span v-if="selectedDetail.document_date" class="flex items-center gap-1">
                    <span class="text-zinc-600">Date:</span> 
                    {{ selectedDetail.document_date }}
                  </span>
                </div>

                <!-- Abstract / Summary Callout in reader layout -->
                <div v-if="getDocumentSummary(selectedDetail.data)" class="text-left bg-zinc-900/40 border border-white/5 p-5 rounded-2xl max-w-2xl mx-auto">
                  <p class="text-[9px] font-black uppercase tracking-widest text-zinc-500 mb-2">Abstract / Executive Summary</p>
                  <p class="text-xs leading-relaxed text-zinc-300 font-medium whitespace-pre-line">{{ getDocumentSummary(selectedDetail.data) }}</p>
                </div>
              </div>

              <!-- Main Reading Body (No table, premium formatted document look and feel) -->
              <div v-if="getDocumentBodyText(selectedDetail.data)" class="prose prose-invert font-serif max-w-none text-zinc-200 leading-relaxed text-base space-y-6 whitespace-pre-line px-2 sm:px-6">
                {{ getDocumentBodyText(selectedDetail.data) }}
              </div>

              <!-- End of Document Footer / Details section (subtle, clean) -->
              <div class="border-t border-white/10 pt-6 mt-10">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                  <div class="flex flex-wrap gap-2">
                    <span v-for="item in getFilteredMetadata(selectedDetail.data)" :key="item.label" class="px-2.5 py-1 bg-zinc-900/60 border border-white/5 rounded-lg text-zinc-400">
                      <strong>{{ item.label }}:</strong> {{ formatValue(item.value) }}
                    </span>
                  </div>
                  <a v-if="selectedDetail.source_url" :href="selectedDetail.source_url" target="_blank" class="text-admin-modern hover:underline flex items-center gap-1 shrink-0 font-black tracking-widest text-[9px]">
                    Go to Source <ExternalLink class="w-3.5 h-3.5" />
                  </a>
                </div>
              </div>
            </div>

            <!-- 3. CASE LAW / COURT JUDGMENTS LAYOUT (Default detailed case view) -->
            <div v-else class="space-y-6">
              <!-- Title & Metadata Header -->
              <div class="border-b border-white/10 pb-6">
                <h2 class="text-2xl font-black uppercase tracking-tight text-white mb-3">
                  {{ selectedDetail.data.title || selectedDetail.data.name || 'Legal Record Details' }}
                </h2>
                <div class="flex flex-wrap items-center gap-4 text-xs font-bold text-zinc-300">
                  <span v-if="selectedDetail.data.court" class="flex items-center gap-1">
                    <span class="text-zinc-500 uppercase tracking-widest text-[9px]">Forum:</span> {{ selectedDetail.data.court }}
                  </span>
                  <span v-if="selectedDetail.data.case_number" class="flex items-center gap-1">
                    <span class="text-zinc-500 uppercase tracking-widest text-[9px]">Case #:</span> {{ selectedDetail.data.case_number }}
                  </span>
                  <span v-if="selectedDetail.document_date" class="flex items-center gap-1">
                    <span class="text-zinc-500 uppercase tracking-widest text-[9px]">Date:</span> {{ selectedDetail.document_date }}
                  </span>
                  <a v-if="selectedDetail.source_url" :href="selectedDetail.source_url" target="_blank" class="text-admin-modern hover:underline flex items-center gap-1">
                    Original Source <ExternalLink class="w-3.5 h-3.5" />
                  </a>
                </div>
              </div>

              <!-- Highlight Callout Boxes (Result / Holding / Order) -->
              <div v-if="getDocumentHolding(selectedDetail.data)" class="p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-medium space-y-1">
                <div class="flex items-center gap-2 text-emerald-400 font-black uppercase text-[10px] tracking-widest">
                  <CheckCircle2 class="w-4 h-4" /> Holding & Final Order
                </div>
                <p class="leading-relaxed">{{ getDocumentHolding(selectedDetail.data) }}</p>
              </div>

              <div v-if="getDocumentDismissal(selectedDetail.data)" class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-medium space-y-1">
                <div class="flex items-center gap-2 text-rose-400 font-black uppercase text-[10px] tracking-widest">
                  <AlertTriangle class="w-4 h-4" /> Reason for Dismissal
                </div>
                <p class="leading-relaxed">{{ getDocumentDismissal(selectedDetail.data) }}</p>
              </div>

              <div v-if="getDocumentSummary(selectedDetail.data)" class="p-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-medium space-y-1">
                <div class="flex items-center gap-2 text-amber-400 font-black uppercase text-[10px] tracking-widest">
                  <FileCheck class="w-4 h-4" /> Summary & Headnotes
                </div>
                <p class="whitespace-pre-line leading-relaxed">{{ getDocumentSummary(selectedDetail.data) }}</p>
              </div>

              <!-- Full Judgment Text Viewer -->
              <div v-if="getDocumentBodyText(selectedDetail.data)" class="space-y-2">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Full Judgment Text</h3>
                <div class="p-6 rounded-2xl bg-black/60 border border-white/10 font-serif text-sm leading-relaxed text-zinc-200 whitespace-pre-line max-h-[500px] overflow-y-auto custom-scrollbar">
                  {{ getDocumentBodyText(selectedDetail.data) }}
                </div>
              </div>

              <!-- Metadata Attributes Grid (Alphabetically sorted, filtered) -->
              <div v-if="getFilteredMetadata(selectedDetail.data).length > 0" class="space-y-2">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Document Metadata Attributes</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs bg-black/40 p-5 rounded-2xl border border-white/10">
                  <div v-for="item in getFilteredMetadata(selectedDetail.data)" :key="item.label" class="flex flex-col gap-0.5">
                    <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">{{ item.label }}</span>
                    <span class="font-mono text-zinc-200 break-all">{{ formatValue(item.value) }}</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Modal>
  </component>
</template>

<style>
/* Custom PrimeVue dark table styling */
.p-datatable-dark-custom {
  background: transparent !important;
}

.p-datatable-dark-custom .p-datatable-header,
.p-datatable-dark-custom .p-datatable-footer {
  background: transparent !important;
  border: none !important;
}

.p-datatable-dark-custom .p-datatable-thead > tr > th {
  background: transparent !important;
  color: #a1a1aa !important;
  font-weight: 900 !important;
  text-transform: uppercase !important;
  font-size: 10px !important;
  letter-spacing: 0.2em !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
  padding: 1rem 1.5rem !important;
}

.p-datatable-dark-custom .p-datatable-tbody > tr {
  background: rgba(0, 0, 0, 0.3) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
  transition: all 0.2s ease !important;
}

.p-datatable-dark-custom .p-datatable-tbody > tr:hover {
  background: rgba(39, 39, 42, 0.6) !important;
}

.p-datatable-dark-custom .p-datatable-tbody > tr > td {
  padding: 1.25rem 1.5rem !important;
  border: none !important;
}

/* High Contrast Paginator Controls Fix */
.p-datatable-dark-custom .p-paginator {
  background: transparent !important;
  border: none !important;
  padding-top: 1.5rem !important;
  color: #e4e4e7 !important;
}

.p-datatable-dark-custom .p-paginator .p-paginator-first,
.p-datatable-dark-custom .p-paginator .p-paginator-prev,
.p-datatable-dark-custom .p-paginator .p-paginator-next,
.p-datatable-dark-custom .p-paginator .p-paginator-last,
.p-datatable-dark-custom .p-paginator .p-paginator-page {
  background: rgba(39, 39, 42, 0.8) !important;
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  border-radius: 0.75rem !important;
  margin: 0 0.125rem !important;
  min-width: 2.5rem !important;
  height: 2.5rem !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.p-datatable-dark-custom .p-paginator .p-paginator-page.p-highlight {
  background: #ffffff !important;
  color: #000000 !important;
  font-weight: 900 !important;
  border-color: #ffffff !important;
}

.p-datatable-dark-custom .p-paginator svg,
.p-datatable-dark-custom .p-paginator .p-icon {
  fill: #ffffff !important;
  color: #ffffff !important;
  width: 1rem !important;
  height: 1rem !important;
}

/* Custom scrollbar for reader layout scroll containers */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.02);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>
