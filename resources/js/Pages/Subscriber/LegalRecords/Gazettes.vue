<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SubscriberLayout from '@/Layouts/SubscriberLayout.vue';
import RecordDetailModal from './Components/RecordDetailModal.vue';
import axios from 'axios';
import {
  Scroll,
  Search,
  RefreshCw,
  Database,
  LayoutGrid,
  List,
  ExternalLink,
  MapPin,
  Calendar,
  Sparkles,
  Lock,
  ArrowRight,
  FileText,
  Download,
  AlertCircle,
  CheckSquare,
  X,
  Loader2,
  Building
} from 'lucide-vue-next';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Skeleton from 'primevue/skeleton';
import Paginator from 'primevue/paginator';
import type { DataTablePageEvent, DataTableSortEvent, DataTableFilterEvent } from 'primevue/datatable';

type DataTableLazyLoadEvent = DataTablePageEvent | DataTableSortEvent | DataTableFilterEvent;

interface AuthUser {
  id: number;
  role: string;
  is_subscribed?: boolean;
  has_pro_access?: boolean;
  first_name?: string;
  last_name?: string;
  email?: string;
}

interface FilterItem {
  target_name: string;
  vanity_name: string;
  target_type: string;
}

const props = defineProps<{
  filters: FilterItem[];
}>();

// Dynamic Layout Selection based on User Role (Admin vs Subscriber)
const page = usePage();
const user = computed(() => (page.props.auth?.user as unknown as AuthUser | null) || null);
const isAdmin = computed(() => user.value && user.value.role === 'admin');
const isPro = computed(() => {
  if (isAdmin.value) return true;
  if (user.value?.is_subscribed || user.value?.has_pro_access) return true;
  return false;
});
const LayoutComponent = computed(() => isAdmin.value ? AdminLayout : SubscriberLayout);

interface RecordSummary {
  id: string;
  extracted_record_id?: string;
  source_table: string;
  record_type: string;
  document_date: string | null;
  court: string;
  case_number: string | null;
  title: string;
  source_url: string | null;
  summary: string | null;
  jurisdiction?: string | null;
  gazette_type?: string | null;
  gazette_number?: string | null;
  volume?: string | null;
  pdf_url?: string | null;
  requires_human_review?: boolean;
}

const records = ref<RecordSummary[]>([]);
const selectedRecords = ref<RecordSummary[]>([]);
const totalRecords = ref(0);
const loading = ref(false);
const batchReviewLoading = ref(false);
const batchSuccessMessage = ref('');

const getInitialUrlParam = (param: string): string => {
  if (typeof window === 'undefined') return '';
  const searchParams = new URLSearchParams(window.location.search);
  return searchParams.get(param) || '';
};

const searchQuery = ref(getInitialUrlParam('search'));
const selectedRecordType = ref(getInitialUrlParam('gazette'));
const viewMode = ref<'cards' | 'table'>('table');

const detailModalVisible = ref(false);
const detailLoading = ref(false);
const selectedDetail = ref<any>(null);

let searchAbortController: AbortController | null = null;
let searchDebounceTimer: any = null;

const escapeHtml = (str: string): string => {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
};

const escapeRegExp = (str: string): string => {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
};

const highlightMatch = (text: string | null | undefined, query: string): string => {
  if (!text) return '';
  const cleanQuery = query?.trim();
  if (!cleanQuery) return escapeHtml(text);

  let tokens: string[] = [];
  if (cleanQuery.startsWith('"') && cleanQuery.endsWith('"') && cleanQuery.length > 2) {
    tokens = [cleanQuery.slice(1, -1).trim()];
  } else {
    tokens = cleanQuery
      .split(/\s+/)
      .map(t => t.trim())
      .filter(t => t.length >= 2);
  }

  if (tokens.length === 0) return escapeHtml(text);

  const pattern = new RegExp(`(${tokens.map(escapeRegExp).join('|')})`, 'gi');
  const parts = text.split(pattern);

  return parts
    .map((part) => {
      if (tokens.some(t => part.toLowerCase() === t.toLowerCase())) {
        return `<mark class="bg-primary/25 text-primary font-bold px-0.5 rounded">${escapeHtml(part)}</mark>`;
      }
      return escapeHtml(part);
    })
    .join('');
};

const updateUrlParams = () => {
  if (typeof window === 'undefined') return;
  const url = new URL(window.location.href);
  const q = searchQuery.value.trim();
  if (q) {
    url.searchParams.set('search', q);
  } else {
    url.searchParams.delete('search');
  }
  if (selectedRecordType.value) {
    url.searchParams.set('gazette', selectedRecordType.value);
  } else {
    url.searchParams.delete('gazette');
  }
  window.history.replaceState({}, '', url.toString());
};

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

const loadLazyRecords = async (event?: Partial<DataTableLazyLoadEvent> | { page: number; first: number; rows: number }) => {
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

  if (searchAbortController) {
    searchAbortController.abort();
  }
  searchAbortController = new AbortController();
  const currentController = searchAbortController;

  loading.value = true;
  updateUrlParams();

  try {
    const response = await axios.get('/legal-records/data', {
      params: {
        offset: first,
        limit: rows,
        category: 'gazettes',
        search: searchQuery.value.trim(),
        record_type: selectedRecordType.value,
        sort_field: sortField,
        sort_order: sortOrder
      },
      signal: currentController.signal
    });

    if (!currentController.signal.aborted) {
      records.value = response.data.records;
      totalRecords.value = response.data.total;
    }
  } catch (error: any) {
    if (axios.isCancel(error) || error?.name === 'CanceledError' || error?.code === 'ERR_CANCELED' || currentController.signal.aborted) {
      return;
    }
    console.error('Failed to fetch gazette records:', error);
  } finally {
    if (searchAbortController === currentController) {
      loading.value = false;
    }
  }
};

const onLazy = (event: DataTableLazyLoadEvent) => {
  loadLazyRecords(event);
};

const onPageChange = (event: any) => {
  lazyParams.value.first = event.first;
  lazyParams.value.rows = event.rows;
  loadLazyRecords(event);
};

const onSearchInput = () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    lazyParams.value.first = 0;
    loadLazyRecords();
  }, 350);
};

const triggerSearchNow = () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
  lazyParams.value.first = 0;
  loadLazyRecords();
};

const clearSearch = () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
  searchQuery.value = '';
  lazyParams.value.first = 0;
  loadLazyRecords();
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
    const response = await axios.get(`/legal-records/record/${record.id}`, {
      params: {
        source_table: record.source_table
      }
    });
    selectedDetail.value = response.data;
  } catch (error) {
    console.error('Failed to load gazette details:', error);
  } finally {
    detailLoading.value = false;
  }
};

const batchMarkForReview = async (requiresReview = true) => {
  if (!selectedRecords.value.length || batchReviewLoading.value) return;

  batchReviewLoading.value = true;
  batchSuccessMessage.value = '';
  const ids = selectedRecords.value.map(r => r.extracted_record_id || r.id);

  try {
    const response = await axios.post('/admin/legal-records/batch-human-review', {
      ids,
      requires_human_review: requiresReview,
      review_reason: 'Flagged via Gazettes table grid batch action by admin.'
    });

    const affectedIds = new Set(ids);
    if (requiresReview) {
      records.value = records.value.filter(r => !affectedIds.has(r.id) && (!r.extracted_record_id || !affectedIds.has(r.extracted_record_id)));
      totalRecords.value = Math.max(0, totalRecords.value - affectedIds.size);
    } else {
      records.value.forEach(r => {
        if (affectedIds.has(r.id) || (r.extracted_record_id && affectedIds.has(r.extracted_record_id))) {
          r.requires_human_review = requiresReview;
        }
      });
    }

    batchSuccessMessage.value = response.data.message || `Successfully updated ${ids.length} record(s).`;
    selectedRecords.value = [];
    setTimeout(() => {
      batchSuccessMessage.value = '';
    }, 4000);
  } catch (error) {
    console.error('Failed to batch mark gazette records for human review:', error);
  } finally {
    batchReviewLoading.value = false;
  }
};

const handleReviewUpdated = (payload: { id: string; requires_human_review: boolean }) => {
  if (payload.requires_human_review) {
    records.value = records.value.filter(r => r.id !== payload.id && r.extracted_record_id !== payload.id);
    totalRecords.value = Math.max(0, totalRecords.value - 1);
  } else {
    const match = records.value.find(r => r.id === payload.id || r.extracted_record_id === payload.id);
    if (match) {
      match.requires_human_review = payload.requires_human_review;
    }
  }
};

onMounted(() => {
  loadLazyRecords();
});
</script>

<template>
  <Head title="8OHM | Government &amp; Provincial Gazettes" />

  <component :is="LayoutComponent">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 lg:mb-12 gap-6">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
            <Scroll class="w-5 h-5" />
          </div>
          <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tighter text-primary">
            Government &amp; Provincial Gazettes
          </h1>
        </div>
        <div>
          <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">
            Official South African National Government Gazettes, Provincial Gazettes, regulations, and statutory notices
          </p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <!-- View Mode Switcher -->
        <div class="flex items-center bg-black/60 border border-white/10 rounded-xl p-1">
          <button @click="viewMode = 'table'"
            class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all cursor-pointer"
            :class="viewMode === 'table' ? 'btn btn-primary font-bold shadow-md shadow-primary/20' : 'text-zinc-400 hover:text-white'">
            <List class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Table Grid</span>
          </button>
          <button @click="viewMode = 'cards'"
            class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all cursor-pointer"
            :class="viewMode === 'cards' ? 'btn btn-primary font-bold shadow-md shadow-primary/20' : 'text-zinc-400 hover:text-white'">
            <LayoutGrid class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Dossier Cards</span>
          </button>
        </div>

        <span
          class="inline-flex items-center gap-2 px-4 py-3 bg-zinc-900 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-primary shadow-md">
          <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
          <span v-if="searchQuery.trim()">{{ totalRecords.toLocaleString() }} Matches</span>
          <span v-else>{{ totalRecords.toLocaleString() }} Active Gazettes</span>
        </span>
      </div>
    </div>

    <!-- Standard Tier Upgrade Notice Banner (if not Pro) -->
    <div v-if="!isPro" class="bg-gradient-to-r from-primary/10 via-amber-500/10 to-transparent border border-primary/30 p-6 rounded-[2rem] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 shadow-xl mb-8">
      <div class="space-y-1.5 max-w-2xl">
        <div class="flex items-center gap-2 text-primary font-black uppercase text-xs tracking-wider">
          <Sparkles class="w-4 h-4" /> Standard Registered Preview Mode
        </div>
        <p class="text-xs text-zinc-300 leading-relaxed">
          You are viewing basic gazette publication headers and summaries. Unredacted full-text statutory proclamations, regulations, and authenticated downloadable PDF files require an active Pro subscription.
        </p>
      </div>
      <a href="/#pricing" class="btn btn-primary px-5 py-3 text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-primary/20 flex items-center gap-2 shrink-0">
        <span>Unlock Now</span>
        <ArrowRight class="w-4 h-4" />
      </a>
    </div>

    <!-- Filter & Search Controls Container -->
    <div
      class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden p-6 sm:p-8 space-y-6 mb-8">
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">

        <!-- Global Search Field -->
        <div class="relative flex-1 max-w-2xl">
          <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
          <input type="text" v-model="searchQuery" @input="onSearchInput" @keydown.enter="triggerSearchNow"
            placeholder="Search by Gazette #, Notice Title, Jurisdiction, Type, or Keywords..."
            class="w-full bg-black/60 border border-white/10 rounded-xl py-3.5 pl-11 pr-11 text-xs font-bold text-white focus:ring-1 focus:ring-primary/50 focus:border-primary/50 placeholder:text-zinc-500 shadow-inner" />
          <div class="absolute right-3.5 top-1/2 -translate-y-1/2 flex items-center">
            <Loader2 v-if="loading" class="w-4 h-4 text-primary animate-spin" />
            <button
              v-else-if="searchQuery"
              @click="clearSearch"
              type="button"
              class="p-1 text-zinc-500 hover:text-white rounded-md hover:bg-white/10 transition cursor-pointer"
              title="Clear search">
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Gazette Jurisdiction Dropdown Selection -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative min-w-[260px]">
            <select :value="selectedRecordType" @change="setRecordType(($event.target as HTMLSelectElement).value)"
              class="w-full bg-black/60 border border-white/10 rounded-xl py-3 px-4 text-xs font-bold text-white focus:ring-1 focus:ring-primary/50 focus:border-primary/50">
              <option value="">All Gazettes &amp; Jurisdictions</option>
              <option v-for="filter in filters" :key="filter.target_name" :value="filter.target_name">
                {{ filter.vanity_name }}
              </option>
            </select>
          </div>

          <button @click="loadLazyRecords()"
            class="p-3 bg-zinc-800 border border-white/10 text-zinc-300 hover:text-white hover:bg-zinc-700 rounded-xl transition-all flex items-center justify-center cursor-pointer"
            title="Refresh Dataset">
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>
        </div>
      </div>
    </div>

    <!-- VIEW MODE 1: PRIME VUE DATATABLE (PRIMARY VIEW) -->
    <div v-if="viewMode === 'table'" class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden p-6 sm:p-8 space-y-4">
      <!-- Admin Batch Selection Action Bar -->
      <div v-if="isAdmin && selectedRecords.length > 0"
        class="bg-amber-500/10 border border-amber-500/30 p-4 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 animate-in fade-in slide-in-from-top-2 duration-200">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-xs">
            {{ selectedRecords.length }}
          </div>
          <div>
            <p class="text-xs font-black uppercase tracking-wider text-white">
              {{ selectedRecords.length }} Record(s) Selected
            </p>
            <p class="text-[10px] text-zinc-400">Perform bulk administrative review operations</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button @click="batchMarkForReview(true)" :disabled="batchReviewLoading"
            class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-black font-black text-xs uppercase tracking-wider rounded-xl flex items-center gap-1.5 transition-all shadow-md shadow-amber-500/20 cursor-pointer disabled:opacity-50">
            <AlertCircle class="w-3.5 h-3.5" />
            <span>{{ batchReviewLoading ? 'Marking...' : 'Mark for Human Review' }}</span>
          </button>
          <button @click="selectedRecords = []"
            class="px-3 py-2 bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer">
            Clear
          </button>
        </div>
      </div>

      <!-- Batch Success Notification -->
      <div v-if="batchSuccessMessage"
        class="bg-emerald-500/10 border border-emerald-500/30 p-3.5 rounded-2xl flex items-center gap-2.5 text-xs text-emerald-400 font-bold animate-in fade-in duration-200">
        <CheckSquare class="w-4 h-4 text-emerald-400 shrink-0" />
        <span>{{ batchSuccessMessage }}</span>
      </div>

      <DataTable :value="records" v-model:selection="selectedRecords" :lazy="true" :totalRecords="totalRecords" :loading="loading" :sortField="lazyParams.sortField"
        :sortOrder="lazyParams.sortOrder" @page="onLazy" @sort="onLazy" @filter="onLazy" paginator :rows="lazyParams.rows"
        :first="lazyParams.first" :rowsPerPageOptions="[10, 25, 50, 100]" dataKey="id"
        tableStyle="min-width: 60rem" class="p-datatable-dark-custom">
        <template #empty>
          <div class="py-20 text-center flex flex-col items-center">
            <div
              class="w-16 h-16 bg-zinc-800/50 rounded-full flex items-center justify-center mb-4 border border-white/5">
              <Database class="w-8 h-8 text-zinc-600" />
            </div>
            <h3 class="text-xl font-black uppercase tracking-tighter text-zinc-400 mb-1">
              <span v-if="searchQuery.trim()">No gazettes found for &ldquo;{{ searchQuery }}&rdquo;</span>
              <span v-else>No gazettes found</span>
            </h3>
            <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px] mb-4">Try adjusting your search terms or jurisdiction filter</p>
            <button
              v-if="searchQuery || selectedRecordType"
              @click="clearSearch(); selectedRecordType = ''; loadLazyRecords();"
              class="btn btn-primary px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-xl cursor-pointer">
              Reset All Filters
            </button>
          </div>
        </template>

        <!-- Selection Checkbox Column (Admin) -->
        <Column v-if="isAdmin" selectionMode="multiple" headerStyle="width: 3rem" />

        <Column field="case_number" header="Gazette Ref" sortable style="width: 15%">
          <template #body="{ data }">
            <span v-if="data.gazette_number || data.case_number"
              class="font-mono text-xs font-bold px-2.5 py-1 bg-black/60 border border-primary/20 text-primary rounded-lg inline-block shadow-sm">
              {{ data.gazette_number || data.case_number }}
              <span v-if="data.volume" class="text-[10px] text-zinc-400 ml-1">Vol {{ data.volume }}</span>
            </span>
            <span v-else class="text-xs text-zinc-500 font-bold uppercase tracking-widest">N/A</span>
          </template>
          <template #loading>
            <Skeleton width="80%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="court" header="Jurisdiction" sortable style="width: 17%">
          <template #body="{ data }">
            <div class="flex items-center gap-1.5">
              <MapPin class="w-3.5 h-3.5 text-primary shrink-0" />
              <span class="px-2.5 py-1 bg-white/5 border border-white/10 text-zinc-200 font-bold text-[10px] uppercase tracking-wider rounded-lg inline-block shadow-sm">
                {{ data.jurisdiction || data.court || 'National' }}
              </span>
            </div>
          </template>
          <template #loading>
            <Skeleton width="60%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="gazette_type" header="Gazette Type" style="width: 16%">
          <template #body="{ data }">
            <span class="text-xs font-medium text-zinc-300">
              {{ data.gazette_type || 'Government Gazette' }}
            </span>
          </template>
          <template #loading>
            <Skeleton width="75%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="document_date" header="Publication Date" sortable style="width: 14%">
          <template #body="{ data }">
            <div class="flex items-center gap-1.5">
              <Calendar class="w-3.5 h-3.5 text-zinc-500 shrink-0" />
              <span class="text-xs font-bold font-mono text-zinc-300 tracking-wider">
                {{ data.document_date || 'N/A' }}
              </span>
            </div>
          </template>
          <template #loading>
            <Skeleton width="70%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="title" header="Notice Title / Matter" style="width: 26%">
          <template #body="{ data }">
            <div
              class="font-bold text-sm text-white uppercase tracking-tight hover:text-primary transition cursor-pointer"
              @click="viewRecordDetail(data)"
              v-html="highlightMatch(data.title, searchQuery)">
            </div>
            <div v-if="data.summary" class="text-[10px] text-zinc-400 font-medium line-clamp-1 mt-1"
              v-html="highlightMatch(data.summary, searchQuery)">
            </div>
          </template>
          <template #loading>
            <Skeleton width="90%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column header="Actions" style="width: 12%" class="text-right">
          <template #body="{ data }">
            <div class="flex items-center justify-end gap-2">
              <a v-if="data.pdf_url" :href="data.pdf_url" target="_blank" rel="noopener noreferrer"
                class="p-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-xl transition-all flex items-center justify-center shrink-0 cursor-pointer"
                title="Download authentic Gazette PDF">
                <Download class="w-3.5 h-3.5" />
              </a>
              <button @click="viewRecordDetail(data)"
                class="btn btn-primary px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-xl flex items-center justify-center gap-1.5 shadow-md shadow-primary/20 cursor-pointer shrink-0">
                <Scroll class="w-3.5 h-3.5" />
                <span>Notice</span>
              </button>
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- VIEW MODE 2: DOSSIER CARDS VIEW -->
    <div v-else class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-black uppercase tracking-tight text-white flex items-center gap-2">
            <Sparkles class="w-4 h-4 text-primary" />
            Gazette Notice Explorer
          </h3>
          <p class="text-xs text-zinc-400">
            Card-based explorer of statutory regulations, notices, proclamations, and Government Gazettes.
          </p>
        </div>
      </div>

      <!-- Loading State Skeleton -->
      <div v-if="loading && records.length === 0" class="space-y-4">
        <div v-for="i in 4" :key="i" class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl space-y-4">
          <div class="flex items-center justify-between">
            <Skeleton width="30%" height="1.5rem" class="bg-zinc-800" />
            <Skeleton width="15%" height="1.5rem" class="bg-zinc-800" />
          </div>
          <Skeleton width="70%" height="1.8rem" class="bg-zinc-800" />
          <Skeleton width="100%" height="4rem" class="bg-zinc-800 rounded-xl" />
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="records.length === 0" class="bg-zinc-900/40 rounded-[2rem] border border-white/5 py-20 text-center flex flex-col items-center">
        <div class="w-16 h-16 bg-zinc-800/50 rounded-full flex items-center justify-center mb-4 border border-white/5">
          <Database class="w-8 h-8 text-zinc-600" />
        </div>
        <h3 class="text-xl font-black uppercase tracking-tighter text-zinc-400 mb-1">
          <span v-if="searchQuery.trim()">No gazettes found for &ldquo;{{ searchQuery }}&rdquo;</span>
          <span v-else>No gazettes found</span>
        </h3>
        <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px] mb-4">Try adjusting your search terms or jurisdiction filter</p>
      </div>

      <!-- Dossier Cards Grid -->
      <div v-else class="space-y-4 transition-opacity duration-200" :class="{ 'opacity-60 pointer-events-none': loading }">
        <div v-for="c in records" :key="c.id"
          class="bg-zinc-900/40 border border-white/5 hover:border-primary/40 transition-all p-6 rounded-2xl space-y-4 group">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/5 text-zinc-300 border border-white/10 flex items-center gap-1">
                <MapPin class="w-3 h-3 text-primary" /> {{ c.jurisdiction || c.court || 'National' }}
              </span>
              <span v-if="c.gazette_number || c.case_number"
                class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-primary/10 text-primary border border-primary/20">
                {{ c.gazette_number || c.case_number }}
              </span>
              <span v-if="c.gazette_type"
                class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                {{ c.gazette_type }}
              </span>
            </div>

            <div class="flex items-center gap-3 text-xs text-zinc-400">
              <span v-if="c.document_date" class="font-bold font-mono text-[11px] text-zinc-400">
                {{ c.document_date }}
              </span>
              <button @click="viewRecordDetail(c)"
                class="btn btn-primary px-3.5 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-xl flex items-center gap-1.5 shadow-md shadow-primary/20 cursor-pointer">
                <span>View Notice</span>
                <Scroll class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Title -->
          <h4 class="text-base font-bold text-white hover:text-primary transition cursor-pointer" @click="viewRecordDetail(c)"
            v-html="highlightMatch(c.title, searchQuery)">
          </h4>

          <!-- Summary / Content excerpt -->
          <div v-if="c.summary" class="bg-zinc-900/60 p-4 rounded-xl border border-white/5 text-xs text-zinc-300">
            <p class="line-clamp-2 leading-relaxed font-sans text-zinc-300"
              v-html="highlightMatch(c.summary, searchQuery)">
            </p>
          </div>

          <!-- Footer -->
          <div class="flex flex-wrap items-center justify-between text-xs text-zinc-400 pt-2 border-t border-white/5 gap-2">
            <span class="text-zinc-400 text-[11px] flex items-center gap-1">
              <FileText class="w-3.5 h-3.5 text-zinc-500" />
              {{ c.gazette_type || 'Government Gazette' }}
            </span>

            <div class="flex items-center gap-4 text-[11px]">
              <a v-if="c.pdf_url" :href="c.pdf_url" target="_blank" rel="noopener noreferrer"
                class="text-emerald-400 hover:text-emerald-300 flex items-center gap-1 transition">
                <Download class="w-3 h-3" />
                <span>Download PDF</span>
              </a>
              <a v-if="c.source_url" :href="c.source_url" target="_blank" rel="noopener noreferrer"
                class="hover:text-white flex items-center gap-1 transition text-zinc-400">
                <span>Official Notice Link</span>
                <ExternalLink class="w-3 h-3" />
              </a>
            </div>
          </div>
        </div>

        <!-- Paginator -->
        <div class="bg-zinc-900/40 rounded-2xl border border-white/5 p-4 flex justify-center">
          <Paginator :first="lazyParams.first" :rows="lazyParams.rows" :totalRecords="totalRecords"
            :rowsPerPageOptions="[10, 25, 50, 100]" @page="onPageChange" class="p-datatable-dark-custom" />
        </div>
      </div>
    </div>

    <!-- Document Detail Modal -->
    <RecordDetailModal
      :show="detailModalVisible"
      :loading="detailLoading"
      :record-detail="selectedDetail"
      category="gazettes"
      @close="detailModalVisible = false"
      @review-updated="handleReviewUpdated"
    />
  </component>
</template>
