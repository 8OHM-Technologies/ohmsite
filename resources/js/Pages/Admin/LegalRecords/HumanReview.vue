<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import {
  Scale,
  Search,
  RefreshCw,
  Database,
  ExternalLink,
  Users,
  Bookmark,
  Sparkles,
  AlertCircle,
  CheckCircle2,
  Calendar,
  Layers,
  FileText,
  Code2,
  CheckSquare,
  X,
  Clock,
  Save,
  Check,
  Tag,
  Eye,
  Sliders
} from 'lucide-vue-next';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Skeleton from 'primevue/skeleton';
import Paginator from 'primevue/paginator';
import type { DataTablePageEvent, DataTableSortEvent, DataTableFilterEvent } from 'primevue/datatable';

type DataTableLazyLoadEvent = DataTablePageEvent | DataTableSortEvent | DataTableFilterEvent;

interface FilterItem {
  target_name: string;
  vanity_name: string;
  target_type: string;
}

const props = defineProps<{
  filters: FilterItem[];
}>();

interface ReviewRecordSummary {
  id: string;
  extracted_record_id: string;
  scrubbed_record_id: string | null;
  parsed_record_id: string | null;
  record_type: string;
  title: string;
  court: string | null;
  case_number: string | null;
  document_date: string | null;
  source_url: string | null;
  requires_human_review: boolean;
  review_reason: string | null;
  status: string | null;
  summary: string | null;
  has_scrubbed: boolean;
  has_parsed: boolean;
}

const records = ref<ReviewRecordSummary[]>([]);
const totalRecords = ref(0);
const loading = ref(false);
const searchQuery = ref('');
const selectedRecordType = ref('');
const selectedCategory = ref('all');

const lazyParams = ref<{
  first: number;
  rows: number;
  sortField: string;
  sortOrder: number;
}>({
  first: 0,
  rows: 25,
  sortField: 'document_date',
  sortOrder: -1
});

let searchDebounceTimer: any = null;

const loadLazyRecords = async (event?: Partial<DataTableLazyLoadEvent> | { page: number; first: number; rows: number }) => {
  loading.value = true;
  if (event) {
    if (event.first !== undefined) lazyParams.value.first = event.first;
    if (event.rows !== undefined) lazyParams.value.rows = event.rows;
    if ('sortField' in event && event.sortField !== undefined) lazyParams.value.sortField = event.sortField as string;
    if ('sortOrder' in event && event.sortOrder !== undefined) lazyParams.value.sortOrder = event.sortOrder as number;
  }

  const first = lazyParams.value.first || 0;
  const rows = lazyParams.value.rows || 25;
  const sortField = lazyParams.value.sortField || 'document_date';
  const sortOrder = lazyParams.value.sortOrder ?? -1;

  try {
    const response = await axios.get('/admin/legal-records/human-review/data', {
      params: {
        offset: first,
        limit: rows,
        category: selectedCategory.value,
        search: searchQuery.value,
        record_type: selectedRecordType.value,
        sort_field: sortField,
        sort_order: sortOrder
      }
    });

    records.value = response.data.records;
    totalRecords.value = response.data.total;
  } catch (error) {
    console.error('Failed to fetch human review records:', error);
  } finally {
    loading.value = false;
  }
};

const onLazy = (event: DataTableLazyLoadEvent) => {
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

const setCategory = (cat: string) => {
  selectedCategory.value = cat;
  lazyParams.value.first = 0;
  loadLazyRecords();
};

// ========================
// 3-STATE EDIT MODAL STATE
// ========================
const editModalVisible = ref(false);
const editLoading = ref(false);
const saveLoading = ref(false);
const activeStateTab = ref<'scrubbed' | 'parsed' | 'extracted'>('scrubbed');
const recordStateData = ref<any>(null);

// Form Fields for Structured Editing
const formData = ref<{
  title: string;
  case_number: string;
  court: string;
  court_location: string;
  document_date: string;
  hearing_date: string;
  applicant: string;
  respondent: string;
  judges_text: string;
  reportable: boolean;
  duration_days: number | null;
  summary: string;
  ratio_decidendi: string;
  obiter_dicta: string;
  order: string;
  subjects: string;
  source_url: string;
  requires_human_review: boolean;
  review_reason: string;
}>({
  title: '',
  case_number: '',
  court: '',
  court_location: '',
  document_date: '',
  hearing_date: '',
  applicant: '',
  respondent: '',
  judges_text: '',
  reportable: true,
  duration_days: null,
  summary: '',
  ratio_decidendi: '',
  obiter_dicta: '',
  order: '',
  subjects: '',
  source_url: '',
  requires_human_review: true,
  review_reason: ''
});

// Raw JSON Editors state
const rawJsonScrubbed = ref('');
const rawJsonParsed = ref('');
const rawJsonExtracted = ref('');
const jsonModeActive = ref(false);
const jsonError = ref<string | null>(null);
const successMessage = ref<string | null>(null);

const openEditModal = async (record: ReviewRecordSummary) => {
  editModalVisible.value = true;
  editLoading.value = true;
  jsonError.value = null;
  successMessage.value = null;
  activeStateTab.value = 'scrubbed';
  jsonModeActive.value = false;

  const targetId = record.extracted_record_id || record.id;

  try {
    const response = await axios.get(`/admin/legal-records/${targetId}/states`);
    recordStateData.value = response.data;

    const f = response.data.form || {};
    formData.value = {
      title: f.title || '',
      case_number: f.case_number || '',
      court: f.court || '',
      court_location: f.court_location || '',
      document_date: f.document_date || '',
      hearing_date: f.hearing_date || '',
      applicant: f.applicant || '',
      respondent: f.respondent || '',
      judges_text: Array.isArray(f.judges) ? f.judges.join(', ') : (f.judges || ''),
      reportable: Boolean(f.reportable),
      duration_days: f.duration_days ?? null,
      summary: f.summary || '',
      ratio_decidendi: f.ratio_decidendi || '',
      obiter_dicta: f.obiter_dicta || '',
      order: f.order || '',
      subjects: f.subjects || '',
      source_url: f.source_url || '',
      requires_human_review: Boolean(f.requires_human_review),
      review_reason: f.review_reason || ''
    };

    const states = response.data.states || {};
    rawJsonScrubbed.value = states.scrubbed?.data ? JSON.stringify(states.scrubbed.data, null, 2) : '';
    rawJsonParsed.value = states.parsed?.data ? JSON.stringify(states.parsed.data, null, 2) : '';
    rawJsonExtracted.value = states.extracted?.data ? JSON.stringify(states.extracted.data, null, 2) : '';
  } catch (error) {
    console.error('Failed to load record states:', error);
  } finally {
    editLoading.value = false;
  }
};

const saveRecordChanges = async (markResolved = false) => {
  if (!recordStateData.value || saveLoading.value) return;

  saveLoading.value = true;
  jsonError.value = null;
  successMessage.value = null;

  const targetId = recordStateData.value.extracted_record_id || recordStateData.value.id;

  const payload: any = {
    requires_human_review: markResolved ? false : formData.value.requires_human_review,
    review_reason: markResolved ? 'Marked as reviewed and resolved by admin.' : formData.value.review_reason
  };

  if (jsonModeActive.value) {
    // Validate and use raw JSON
    try {
      if (activeStateTab.value === 'scrubbed' && rawJsonScrubbed.value.trim()) {
        payload.raw_scrubbed_data = JSON.parse(rawJsonScrubbed.value);
      }
      if (activeStateTab.value === 'parsed' && rawJsonParsed.value.trim()) {
        payload.raw_parsed_data = JSON.parse(rawJsonParsed.value);
      }
      if (activeStateTab.value === 'extracted' && rawJsonExtracted.value.trim()) {
        payload.raw_extracted_data = JSON.parse(rawJsonExtracted.value);
      }
    } catch (e: any) {
      jsonError.value = 'Invalid JSON syntax: ' + e.message;
      saveLoading.value = false;
      return;
    }
  } else {
    // Form fields payload
    const judgesArray = formData.value.judges_text
      ? formData.value.judges_text.split(',').map(j => j.trim()).filter(Boolean)
      : [];

    payload.title = formData.value.title;
    payload.case_number = formData.value.case_number;
    payload.court = formData.value.court;
    payload.court_location = formData.value.court_location;
    payload.document_date = formData.value.document_date || null;
    payload.hearing_date = formData.value.hearing_date || null;
    payload.applicant = formData.value.applicant;
    payload.respondent = formData.value.respondent;
    payload.judges = judgesArray;
    payload.reportable = formData.value.reportable;
    payload.duration_days = formData.value.duration_days;
    payload.summary = formData.value.summary;
    payload.ratio_decidendi = formData.value.ratio_decidendi;
    payload.obiter_dicta = formData.value.obiter_dicta;
    payload.order = formData.value.order;
    payload.subjects = formData.value.subjects;
    payload.source_url = formData.value.source_url;
  }

  try {
    const response = await axios.put(`/admin/legal-records/${targetId}`, payload);
    successMessage.value = markResolved ? 'Record marked as resolved and removed from active queue.' : 'Record changes successfully saved.';

    // Update table row locally
    const match = records.value.find(r => r.id === targetId || r.extracted_record_id === targetId);
    if (match) {
      match.title = formData.value.title;
      match.case_number = formData.value.case_number;
      match.court = formData.value.court;
      match.document_date = formData.value.document_date;
      match.requires_human_review = payload.requires_human_review;
      match.review_reason = payload.review_reason;
    }

    if (markResolved) {
      setTimeout(() => {
        editModalVisible.value = false;
        loadLazyRecords();
      }, 1000);
    }
  } catch (error: any) {
    console.error('Failed to update record:', error);
    jsonError.value = error.response?.data?.message || 'Failed to save changes. Please verify input fields.';
  } finally {
    saveLoading.value = false;
  }
};

const quickToggleResolve = async (record: ReviewRecordSummary) => {
  const targetId = record.extracted_record_id || record.id;
  try {
    const response = await axios.post(`/admin/legal-records/${targetId}/human-review`, {
      requires_human_review: !record.requires_human_review,
      review_reason: 'Resolved from human review queue table action.'
    });

    record.requires_human_review = response.data.requires_human_review;
    // Reload queue after resolving
    loadLazyRecords();
  } catch (error) {
    console.error('Failed to toggle review status:', error);
  }
};

onMounted(() => {
  loadLazyRecords();
});
</script>

<template>
  <Head title="8OHM | Legal Records Human Review Queue" />

  <AdminLayout>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 lg:mb-12 gap-6">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
            <AlertCircle class="w-5 h-5" />
          </div>
          <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tighter text-white">
            Human Review Queue
          </h1>
        </div>
        <div>
          <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">
            Administrative review, triage, and 3-state data refinement console for court judgments &amp; legal records
          </p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <span
          class="inline-flex items-center gap-2 px-4 py-3 bg-zinc-900 border border-amber-500/30 rounded-xl text-[10px] font-black uppercase tracking-widest text-amber-400 shadow-md">
          <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
          {{ totalRecords.toLocaleString() }} Records Requiring Review
        </span>
      </div>
    </div>

    <!-- Filter & Search Controls Container -->
    <div class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden p-6 sm:p-8 space-y-6 mb-8">
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        <!-- Global Search Field -->
        <div class="relative flex-1 max-w-2xl">
          <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
          <input type="text" v-model="searchQuery" @input="onSearchInput"
            placeholder="Search by Case #, Applicant, Court, Title, or Review Note..."
            class="w-full bg-black/60 border border-white/10 rounded-xl py-3.5 pl-11 pr-4 text-xs font-bold text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50 placeholder:text-zinc-500 shadow-inner" />
        </div>

        <!-- Filter Dropdown & Refresh -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative min-w-[200px]">
            <select :value="selectedCategory" @change="setCategory(($event.target as HTMLSelectElement).value)"
              class="w-full bg-black/60 border border-white/10 rounded-xl py-3 px-4 text-xs font-bold text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50">
              <option value="all">All Categories</option>
              <option value="cases">Case Law</option>
              <option value="journals">Journals &amp; Gazettes</option>
              <option value="court_rolls">Court Rolls</option>
            </select>
          </div>

          <div class="relative min-w-[220px]">
            <select :value="selectedRecordType" @change="setRecordType(($event.target as HTMLSelectElement).value)"
              class="w-full bg-black/60 border border-white/10 rounded-xl py-3 px-4 text-xs font-bold text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50">
              <option value="">All Courts / Targets</option>
              <option v-for="filter in filters" :key="filter.target_name" :value="filter.target_name">
                {{ filter.vanity_name }}
              </option>
            </select>
          </div>

          <button @click="loadLazyRecords()"
            class="p-3 bg-zinc-800 border border-white/10 text-zinc-300 hover:text-white hover:bg-zinc-700 rounded-xl transition-all flex items-center justify-center cursor-pointer"
            title="Refresh Dataset">
            <RefreshCw class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- PRIME VUE DATATABLE -->
    <div class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden p-6 sm:p-8">
      <DataTable :value="records" :lazy="true" :totalRecords="totalRecords" :loading="loading" :sortField="lazyParams.sortField"
        :sortOrder="lazyParams.sortOrder" @page="onLazy" @sort="onLazy" @filter="onLazy" paginator :rows="lazyParams.rows"
        :first="lazyParams.first" :rowsPerPageOptions="[10, 25, 50, 100]" dataKey="id"
        tableStyle="min-width: 60rem" class="p-datatable-dark-custom">
        <template #empty>
          <div class="py-20 text-center flex flex-col items-center">
            <div class="w-16 h-16 bg-emerald-500/10 rounded-full flex items-center justify-center mb-4 border border-emerald-500/20">
              <CheckCircle2 class="w-8 h-8 text-emerald-400" />
            </div>
            <h3 class="text-xl font-black uppercase tracking-tighter text-white mb-1">Queue Clear</h3>
            <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">No records currently require human review</p>
          </div>
        </template>

        <Column field="case_number" header="Case Ref / Type" sortable style="width: 18%">
          <template #body="{ data }">
            <div class="space-y-1">
              <span v-if="data.case_number"
                class="font-mono text-xs font-bold px-2.5 py-1 bg-black/60 border border-amber-500/30 text-amber-400 rounded-lg inline-block shadow-sm">
                {{ data.case_number }}
              </span>
              <span v-else class="text-xs text-zinc-500 font-bold uppercase tracking-widest">N/A</span>
              <div class="text-[10px] text-zinc-500 font-mono">{{ data.record_type }}</div>
            </div>
          </template>
          <template #loading>
            <Skeleton width="80%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="court" header="Court / Forum" sortable style="width: 18%">
          <template #body="{ data }">
            <span
              class="px-2.5 py-1 bg-white/5 border border-white/10 text-zinc-200 font-bold text-[10px] uppercase tracking-wider rounded-lg inline-block shadow-sm">
              {{ data.court || 'Court / Tribunal' }}
            </span>
          </template>
          <template #loading>
            <Skeleton width="60%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="document_date" header="Date" sortable style="width: 10%">
          <template #body="{ data }">
            <span class="text-xs font-bold font-mono text-zinc-300 tracking-wider">
              {{ data.document_date || 'N/A' }}
            </span>
          </template>
          <template #loading>
            <Skeleton width="70%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="title" header="Title / Review Reason" style="width: 36%">
          <template #body="{ data }">
            <div
              class="font-bold text-sm text-white uppercase tracking-tight hover:text-amber-400 transition cursor-pointer"
              @click="openEditModal(data)">
              {{ data.title }}
            </div>
            <div v-if="data.review_reason" class="flex items-center gap-1.5 text-[11px] text-amber-400/90 font-medium mt-1">
              <AlertCircle class="w-3 h-3 shrink-0" />
              <span class="line-clamp-1">{{ data.review_reason }}</span>
            </div>
            <div v-else-if="data.summary" class="text-[10px] text-zinc-400 font-medium line-clamp-1 mt-1">
              {{ data.summary }}
            </div>

            <!-- Pipeline State Chips -->
            <div class="flex items-center gap-2 mt-2">
              <span class="text-[9px] px-1.5 py-0.5 rounded font-mono font-bold"
                :class="data.has_scrubbed ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-zinc-800 text-zinc-500'">
                Scrubbed
              </span>
              <span class="text-[9px] px-1.5 py-0.5 rounded font-mono font-bold"
                :class="data.has_parsed ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-zinc-800 text-zinc-500'">
                Parsed
              </span>
              <span class="text-[9px] px-1.5 py-0.5 rounded font-mono font-bold bg-primary/10 text-primary border border-primary/20">
                Extracted
              </span>
            </div>
          </template>
          <template #loading>
            <Skeleton width="90%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column header="Actions" style="width: 18%" class="text-right">
          <template #body="{ data }">
            <div class="flex items-center justify-end gap-2">
              <button @click="openEditModal(data)"
                class="px-3 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[10px] font-black uppercase tracking-wider rounded-xl flex items-center gap-1.5 transition-all shadow-sm cursor-pointer">
                <Sliders class="w-3.5 h-3.5" />
                <span>Review &amp; Edit</span>
              </button>
              <button @click="quickToggleResolve(data)"
                class="p-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 rounded-xl transition-all cursor-pointer"
                title="Mark as Resolved">
                <Check class="w-4 h-4" />
              </button>
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- ============================================== -->
    <!-- 3-STATE COMPREHENSIVE RECORD REVIEW & EDIT MODAL -->
    <!-- ============================================== -->
    <Modal :show="editModalVisible" @close="editModalVisible = false" maxWidth="6xl">
      <div class="relative bg-zinc-950 text-white overflow-hidden max-h-[92vh] flex flex-col rounded-3xl border border-white/10 shadow-2xl">
        <!-- Top Modal Header -->
        <div class="flex items-start justify-between gap-4 p-6 sm:p-8 border-b border-white/10 bg-zinc-900/80 backdrop-blur-md sticky top-0 z-20">
          <div class="space-y-1.5 flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center gap-1">
                <AlertCircle class="w-3 h-3" />
                Human Review Refinement Console
              </span>
              <span v-if="formData.case_number"
                class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-white/5 text-zinc-300 border border-white/10">
                {{ formData.case_number }}
              </span>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/5 text-zinc-300 border border-white/10">
                {{ formData.court || 'Court / Tribunal' }}
              </span>
            </div>
            <h2 class="text-base sm:text-lg font-black text-white leading-snug break-words">
              {{ formData.title || 'Legal Record Inspection' }}
            </h2>
          </div>

          <button @click="editModalVisible = false"
            class="p-2 text-zinc-400 hover:text-white rounded-xl bg-white/5 hover:bg-white/10 transition-all shrink-0 cursor-pointer"
            title="Close">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- 3-State Navigation Tabs Bar -->
        <div class="px-6 sm:px-8 py-3 bg-black/40 border-b border-white/5 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <!-- Tab 1: Scrubbed Record (Live) -->
            <button @click="activeStateTab = 'scrubbed'"
              class="px-3.5 py-2 rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2 transition-all cursor-pointer"
              :class="activeStateTab === 'scrubbed' ? 'bg-amber-500 text-black shadow-md shadow-amber-500/20' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10'">
              <Layers class="w-3.5 h-3.5" />
              <span>1. Scrubbed (Live State)</span>
            </button>

            <!-- Tab 2: Parsed Record (Intermediate) -->
            <button @click="activeStateTab = 'parsed'"
              class="px-3.5 py-2 rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2 transition-all cursor-pointer"
              :class="activeStateTab === 'parsed' ? 'bg-purple-500 text-white shadow-md shadow-purple-500/20' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10'">
              <Sparkles class="w-3.5 h-3.5" />
              <span>2. Parsed (Intermediate)</span>
            </button>

            <!-- Tab 3: Extracted Record (Raw Scraped) -->
            <button @click="activeStateTab = 'extracted'"
              class="px-3.5 py-2 rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2 transition-all cursor-pointer"
              :class="activeStateTab === 'extracted' ? 'bg-primary text-black shadow-md shadow-primary/20' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10'">
              <Database class="w-3.5 h-3.5" />
              <span>3. Extracted (Raw Pipeline)</span>
            </button>
          </div>

          <!-- Structured Form vs Raw JSON Switcher -->
          <div class="flex items-center gap-2">
            <button @click="jsonModeActive = !jsonModeActive"
              class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all border border-white/10 cursor-pointer"
              :class="jsonModeActive ? 'bg-zinc-800 text-amber-400 border-amber-500/40' : 'bg-black/40 text-zinc-400 hover:text-white'">
              <Code2 class="w-3.5 h-3.5" />
              <span>{{ jsonModeActive ? 'JSON Editor Active' : 'Switch to Raw JSON' }}</span>
            </button>
          </div>
        </div>

        <!-- Notification Banner in Modal -->
        <div v-if="jsonError" class="mx-6 sm:mx-8 mt-4 p-3.5 bg-rose-500/10 border border-rose-500/30 rounded-2xl flex items-center gap-2.5 text-xs text-rose-400 font-bold">
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{{ jsonError }}</span>
        </div>
        <div v-if="successMessage" class="mx-6 sm:mx-8 mt-4 p-3.5 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl flex items-center gap-2.5 text-xs text-emerald-400 font-bold">
          <CheckCircle2 class="w-4 h-4 shrink-0" />
          <span>{{ successMessage }}</span>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="flex-1 overflow-y-auto p-6 sm:p-8 custom-scrollbar space-y-6">
          <div v-if="editLoading" class="space-y-4">
            <Skeleton width="100%" height="3rem" class="bg-zinc-800" />
            <Skeleton width="100%" height="8rem" class="bg-zinc-800" />
            <Skeleton width="100%" height="12rem" class="bg-zinc-800" />
          </div>

          <div v-else-if="jsonModeActive" class="space-y-3">
            <div class="flex items-center justify-between text-xs text-zinc-400">
              <span>Editing raw JSON payload for <strong>{{ activeStateTab.toUpperCase() }}</strong> stage</span>
              <span class="text-[10px] text-amber-400">Footnotes &amp; Citations will be preserved on save</span>
            </div>

            <textarea v-if="activeStateTab === 'scrubbed'" v-model="rawJsonScrubbed" rows="18"
              class="w-full bg-black/80 border border-white/10 rounded-2xl p-4 font-mono text-xs text-zinc-200 focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50"></textarea>

            <textarea v-else-if="activeStateTab === 'parsed'" v-model="rawJsonParsed" rows="18"
              class="w-full bg-black/80 border border-white/10 rounded-2xl p-4 font-mono text-xs text-zinc-200 focus:ring-1 focus:ring-purple-500/50 focus:border-purple-500/50"></textarea>

            <textarea v-else-if="activeStateTab === 'extracted'" v-model="rawJsonExtracted" rows="18"
              class="w-full bg-black/80 border border-white/10 rounded-2xl p-4 font-mono text-xs text-zinc-200 focus:ring-1 focus:ring-primary/50 focus:border-primary/50"></textarea>
          </div>

          <!-- Form View by Tab -->
          <div v-else>
            <!-- ============================== -->
            <!-- TAB 1: SCRUBBED RECORD (LIVE)  -->
            <!-- ============================== -->
            <div v-if="activeStateTab === 'scrubbed'" class="space-y-6">
              <!-- Grid 1: Basic Identifiers -->
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-1.5 sm:col-span-2">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Matter Title / Name</label>
                  <input type="text" v-model="formData.title"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Case Reference / Number</label>
                  <input type="text" v-model="formData.case_number"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-amber-400 font-mono focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Court / Tribunal</label>
                  <input type="text" v-model="formData.court"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>
              </div>

              <!-- Grid 2: Parties & Judges -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Applicant / Plaintiff / Employee</label>
                  <input type="text" v-model="formData.applicant"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Respondent / Defendant / Employer</label>
                  <input type="text" v-model="formData.respondent"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Judges / Bench (Comma-separated)</label>
                  <input type="text" v-model="formData.judges_text" placeholder="e.g. Maya DP, Zondo CJ"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>
              </div>

              <!-- Grid 3: Dates & Locations -->
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Judgment / Doc Date</label>
                  <input type="date" v-model="formData.document_date"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white font-mono focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Hearing Date</label>
                  <input type="date" v-model="formData.hearing_date"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white font-mono focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Location / Seat</label>
                  <input type="text" v-model="formData.court_location"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Duration (Days)</label>
                  <input type="number" v-model.number="formData.duration_days"
                    class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
                </div>
              </div>

              <!-- Executive Summary -->
              <div class="space-y-1.5">
                <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Executive Summary</label>
                <textarea v-model="formData.summary" rows="3"
                  class="w-full bg-black/60 border border-white/10 rounded-xl p-3.5 text-xs text-zinc-200 focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50"></textarea>
              </div>

              <!-- Ratio Decidendi & Obiter Dicta -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-amber-400">Ratio Decidendi (Binding Principle)</label>
                  <textarea v-model="formData.ratio_decidendi" rows="4"
                    class="w-full bg-black/60 border border-amber-500/30 rounded-xl p-3.5 text-xs text-zinc-200 focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50"></textarea>
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-purple-400">Obiter Dicta (Judicial Observations)</label>
                  <textarea v-model="formData.obiter_dicta" rows="4"
                    class="w-full bg-black/60 border border-purple-500/30 rounded-xl p-3.5 text-xs text-zinc-200 focus:ring-1 focus:ring-purple-500/50 focus:border-purple-500/50"></textarea>
                </div>
              </div>

              <!-- Formal Court Order & Relief Granted -->
              <div class="space-y-1.5">
                <label class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Formal Court Order &amp; Relief Granted</label>
                <textarea v-model="formData.order" rows="3"
                  class="w-full bg-black/60 border border-emerald-500/30 rounded-xl p-3.5 text-xs text-zinc-200 font-mono focus:ring-1 focus:ring-emerald-500/50 focus:border-emerald-500/50"></textarea>
              </div>
            </div>

            <!-- ============================== -->
            <!-- TAB 2: PARSED RECORD (INTERMEDIATE) -->
            <!-- ============================== -->
            <div v-else-if="activeStateTab === 'parsed'" class="space-y-4">
              <div v-if="recordStateData?.states?.parsed" class="space-y-4">
                <div class="bg-zinc-900/60 p-4 rounded-2xl border border-white/5 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                  <div>
                    <span class="text-[9px] text-zinc-500 font-bold uppercase block">Parsed ID</span>
                    <span class="font-mono text-zinc-300 text-[11px]">{{ recordStateData.states.parsed.id }}</span>
                  </div>
                  <div>
                    <span class="text-[9px] text-zinc-500 font-bold uppercase block">Created At</span>
                    <span class="font-mono text-zinc-300 text-[11px]">{{ recordStateData.states.parsed.created_at || 'N/A' }}</span>
                  </div>
                  <div>
                    <span class="text-[9px] text-zinc-500 font-bold uppercase block">Updated At</span>
                    <span class="font-mono text-zinc-300 text-[11px]">{{ recordStateData.states.parsed.updated_at || 'N/A' }}</span>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Intermediate LLM / Parsed JSON Payload</label>
                  <textarea v-model="rawJsonParsed" rows="14"
                    class="w-full bg-black/80 border border-white/10 rounded-2xl p-4 font-mono text-xs text-zinc-200 focus:ring-1 focus:ring-purple-500/50 focus:border-purple-500/50"></textarea>
                </div>
              </div>

              <div v-else class="py-16 text-center bg-zinc-900/30 rounded-2xl border border-white/5">
                <Sparkles class="w-8 h-8 text-zinc-600 mx-auto mb-2" />
                <p class="text-xs font-bold text-zinc-400">No intermediate parsed_record entry linked to this record</p>
              </div>
            </div>

            <!-- ============================== -->
            <!-- TAB 3: EXTRACTED RECORD (RAW PIPELINE) -->
            <!-- ============================== -->
            <div v-else-if="activeStateTab === 'extracted'" class="space-y-4">
              <div v-if="recordStateData?.states?.extracted" class="space-y-4">
                <div class="bg-zinc-900/60 p-4 rounded-2xl border border-white/5 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                  <div>
                    <span class="text-[9px] text-zinc-500 font-bold uppercase block">Extracted ID</span>
                    <span class="font-mono text-primary text-[11px]">{{ recordStateData.states.extracted.id }}</span>
                  </div>
                  <div>
                    <span class="text-[9px] text-zinc-500 font-bold uppercase block">Record Type / Target</span>
                    <span class="font-bold text-white text-xs">{{ recordStateData.states.extracted.record_type }}</span>
                  </div>
                  <div>
                    <span class="text-[9px] text-zinc-500 font-bold uppercase block">Scraper Status</span>
                    <span class="font-bold text-emerald-400 text-xs">{{ recordStateData.states.extracted.status || 'detailed' }}</span>
                  </div>
                  <div>
                    <span class="text-[9px] text-zinc-500 font-bold uppercase block">Source Document Date</span>
                    <span class="font-mono text-zinc-300 text-xs">{{ recordStateData.states.extracted.document_date || 'N/A' }}</span>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Source Publication URL</label>
                  <div class="flex items-center gap-2">
                    <input type="text" v-model="formData.source_url"
                      class="flex-1 bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white font-mono focus:ring-1 focus:ring-primary/50 focus:border-primary/50" />
                    <a v-if="formData.source_url" :href="formData.source_url" target="_blank" rel="noopener noreferrer"
                      class="p-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-white">
                      <ExternalLink class="w-4 h-4" />
                    </a>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Raw Scraped Payload (JSON)</label>
                  <textarea v-model="rawJsonExtracted" rows="12"
                    class="w-full bg-black/80 border border-white/10 rounded-2xl p-4 font-mono text-xs text-zinc-200 focus:ring-1 focus:ring-primary/50 focus:border-primary/50"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Review Status & Notes Control (Always present across all tabs) -->
          <div class="bg-zinc-900/60 p-5 rounded-2xl border border-amber-500/20 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black uppercase tracking-wider text-amber-400 flex items-center gap-2">
                <AlertCircle class="w-4 h-4" />
                Human Review Status &amp; Administrative Notes
              </span>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="formData.requires_human_review" class="rounded bg-black/60 border-white/20 text-amber-500 focus:ring-amber-500" />
                <span class="text-xs font-bold text-white">Requires Human Review</span>
              </label>
            </div>

            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Review Reason / Resolution Notes</label>
              <input type="text" v-model="formData.review_reason" placeholder="e.g. Scraper misidentified applicant name, manual correction applied..."
                class="w-full bg-black/60 border border-white/10 rounded-xl py-2.5 px-3.5 text-xs text-white focus:ring-1 focus:ring-amber-500/50 focus:border-amber-500/50" />
            </div>
          </div>
        </div>

        <!-- Modal Actions Footer -->
        <div class="p-4 sm:px-8 border-t border-white/10 bg-zinc-900/80 flex flex-wrap items-center justify-between gap-3 sticky bottom-0 z-20">
          <div class="flex items-center gap-2">
            <button @click="editModalVisible = false"
              class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white rounded-xl text-xs font-bold uppercase tracking-wider transition cursor-pointer">
              Cancel
            </button>
          </div>

          <div class="flex items-center gap-3">
            <button @click="saveRecordChanges(true)" :disabled="saveLoading"
              class="px-4 py-2.5 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2 transition cursor-pointer shadow-md shadow-emerald-500/10 disabled:opacity-50">
              <CheckCircle2 class="w-4 h-4 text-emerald-400" />
              <span>{{ saveLoading ? 'Saving...' : 'Save & Mark as Resolved' }}</span>
            </button>

            <button @click="saveRecordChanges(false)" :disabled="saveLoading"
              class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-black rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2 transition cursor-pointer shadow-lg shadow-amber-500/20 disabled:opacity-50">
              <Save class="w-4 h-4" />
              <span>{{ saveLoading ? 'Saving...' : 'Save Changes' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Modal>
  </AdminLayout>
</template>

<style>
.p-datatable-dark-custom {
  background: transparent !important;
}

.p-datatable-dark-custom .p-datatable-header,
.p-datatable-dark-custom .p-datatable-footer {
  background: transparent !important;
  border: none !important;
}

.p-datatable-dark-custom .p-datatable-thead>tr>th {
  background: transparent !important;
  color: #a1a1aa !important;
  font-weight: 900 !important;
  text-transform: uppercase !important;
  font-size: 10px !important;
  letter-spacing: 0.2em !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
  padding: 1rem 1.5rem !important;
}

.p-datatable-dark-custom .p-datatable-tbody>tr {
  background: rgba(0, 0, 0, 0.3) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
  transition: all 0.2s ease !important;
}

.p-datatable-dark-custom .p-datatable-tbody>tr:hover {
  background: rgba(39, 39, 42, 0.6) !important;
}

.p-datatable-dark-custom .p-datatable-tbody>tr>td {
  padding: 1.25rem 1.5rem !important;
  border: none !important;
}

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
  background: var(--color-primary, #ff8800) !important;
  color: #000000 !important;
  font-weight: 900 !important;
  border-color: var(--color-primary, #ff8800) !important;
}

.p-datatable-dark-custom .p-paginator svg,
.p-datatable-dark-custom .p-paginator .p-icon {
  fill: #ffffff !important;
  color: #ffffff !important;
  width: 1rem !important;
  height: 1rem !important;
}
</style>
