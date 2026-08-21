<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SubscriberLayout from '@/Layouts/SubscriberLayout.vue';
import RecordDetailModal from './Components/RecordDetailModal.vue';
import axios from 'axios';
import {
  BookOpen,
  Search,
  RefreshCw,
  Database,
  LayoutGrid,
  List,
  ExternalLink,
  Bookmark,
  Sparkles,
  Lock,
  ArrowRight
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
  source_table: string;
  record_type: string;
  document_date: string | null;
  court: string;
  case_number: string | null;
  title: string;
  source_url: string | null;
  applicant: string | null;
  respondent: string | null;
  subjects: string | null;
  outcome: string | null;
  summary: string | null;
}

const records = ref<RecordSummary[]>([]);
const totalRecords = ref(0);
const loading = ref(false);
const searchQuery = ref('');
const selectedRecordType = ref('');
const viewMode = ref<'cards' | 'table'>('cards');

const detailModalVisible = ref(false);
const detailLoading = ref(false);
const selectedDetail = ref<any>(null);

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
    const response = await axios.get('/legal-records/data', {
      params: {
        offset: first,
        limit: rows,
        category: 'journals',
        search: searchQuery.value,
        record_type: selectedRecordType.value,
        sort_field: sortField,
        sort_order: sortOrder
      }
    });

    records.value = response.data.records;
    totalRecords.value = response.data.total;
  } catch (error) {
    console.error('Failed to fetch journal records:', error);
  } finally {
    loading.value = false;
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
    console.error('Failed to load record details:', error);
  } finally {
    detailLoading.value = false;
  }
};

onMounted(() => {
  loadLazyRecords();
});
</script>

<template>

  <Head title="8OHM | Law Journals &amp; Gazettes" />

  <component :is="LayoutComponent">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 lg:mb-12 gap-6">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <div
            class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
            <BookOpen class="w-5 h-5" />
          </div>
          <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tighter text-primary">
            Journals &amp; Gazettes
          </h1>
        </div>
        <div>
          <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">
            Academic Law Reviews, Official Government Gazettes, and Scholarly Legal Articles
          </p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <!-- View Mode Switcher -->
        <div class="flex items-center bg-black/60 border border-white/10 rounded-xl p-1">
          <button @click="viewMode = 'cards'"
            class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all cursor-pointer"
            :class="viewMode === 'cards' ? 'btn btn-primary font-bold shadow-md shadow-primary/20' : 'text-zinc-400 hover:text-white'">
            <LayoutGrid class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Dossier Cards</span>
          </button>
          <button @click="viewMode = 'table'"
            class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all cursor-pointer"
            :class="viewMode === 'table' ? 'btn btn-primary font-bold shadow-md shadow-primary/20' : 'text-zinc-400 hover:text-white'">
            <List class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Table Grid</span>
          </button>
        </div>

        <span
          class="inline-flex items-center gap-2 px-4 py-3 bg-zinc-900 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-primary shadow-md">
          <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
          {{ totalRecords.toLocaleString() }} Active Records
        </span>
      </div>
    </div>

    <!-- Standard Tier Upgrade Notice Banner (if not Pro) -->
    <div v-if="!isPro"
      class="bg-gradient-to-r from-primary/10 via-amber-500/10 to-transparent border border-primary/30 p-6 rounded-[2rem] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 shadow-xl mb-8">
      <div class="space-y-1.5 max-w-2xl">
        <div class="flex items-center gap-2 text-primary font-black uppercase text-xs tracking-wider">
          <Sparkles class="w-4 h-4" /> Standard Registered Preview Mode
        </div>
        <p class="text-xs text-zinc-300 leading-relaxed">
          You are viewing basic journal abstracts and publication notices. Full-text analytical indexing and complete
          source dossiers require an active Pro subscription.
        </p>
      </div>
      <a href="/#pricing"
        class="btn btn-primary px-5 py-3 text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-primary/20 flex items-center gap-2 shrink-0">
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
          <input type="text" v-model="searchQuery" @input="onSearchInput"
            placeholder="Search by Title, Publisher, Volume, or Article Keywords..."
            class="w-full bg-black/60 border border-white/10 rounded-xl py-3.5 pl-11 pr-4 text-xs font-bold text-white focus:ring-1 focus:ring-primary/50 focus:border-primary/50 placeholder:text-zinc-500 shadow-inner" />
        </div>

        <!-- Journal / Gazette Dropdown Selection -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative min-w-[260px]">
            <select :value="selectedRecordType" @change="setRecordType(($event.target as HTMLSelectElement).value)"
              class="w-full bg-black/60 border border-white/10 rounded-xl py-3 px-4 text-xs font-bold text-white focus:ring-1 focus:ring-primary/50 focus:border-primary/50">
              <option value="">All Journals &amp; Gazettes</option>
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

    <!-- VIEW MODE 1: DOSSIER CARDS VIEW -->
    <div v-if="viewMode === 'cards'" class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-black uppercase tracking-tight text-white flex items-center gap-2">
            <Sparkles class="w-4 h-4 text-primary" />
            Publication Explorer &amp; Index
          </h3>
          <p class="text-xs text-zinc-400">
            Scholarly articles, gazette notices, and legal publications with abstract summaries and source references.
          </p>
        </div>
      </div>

      <!-- Loading State Skeleton -->
      <div v-if="loading" class="space-y-4">
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
      <div v-else-if="records.length === 0"
        class="bg-zinc-900/40 rounded-[2rem] border border-white/5 py-20 text-center flex flex-col items-center">
        <div class="w-16 h-16 bg-zinc-800/50 rounded-full flex items-center justify-center mb-4 border border-white/5">
          <Database class="w-8 h-8 text-zinc-600" />
        </div>
        <h3 class="text-xl font-black uppercase tracking-tighter text-zinc-400 mb-1">No publications found</h3>
        <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">Try adjusting your search terms or
          filter</p>
      </div>

      <!-- Dossier Cards Grid -->
      <div v-else class="space-y-4">
        <div v-for="c in records" :key="c.id"
          class="bg-zinc-900/40 border border-white/5 hover:border-primary/40 transition-all p-6 rounded-2xl space-y-4 group">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
              <span v-if="c.case_number"
                class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-primary/10 text-primary border border-primary/20">
                {{ c.case_number }}
              </span>
              <span
                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/5 text-zinc-300 border border-white/10">
                {{ c.applicant || c.court || c.record_type }}
              </span>
            </div>

            <div class="flex items-center gap-3 text-xs text-zinc-400">
              <span v-if="c.document_date" class="font-bold font-mono text-[11px] text-zinc-400">
                {{ c.document_date }}
              </span>
              <button @click="viewRecordDetail(c)"
                class="btn btn-primary px-3.5 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-xl flex items-center gap-1.5 shadow-md shadow-primary/20 cursor-pointer">
                <span>Read Publication</span>
                <BookOpen class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Title -->
          <h4 class="text-base font-bold text-white hover:text-primary transition cursor-pointer"
            @click="viewRecordDetail(c)">
            {{ c.title }}
          </h4>

          <!-- Summary -->
          <div v-if="c.summary" class="bg-zinc-900/60 p-4 rounded-xl border border-white/5 text-xs text-zinc-300">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1 mb-1.5">
              <Bookmark class="w-3.5 h-3.5" />
              Abstract &amp; Summary
            </span>
            <p class="line-clamp-2 leading-relaxed font-sans text-zinc-300">
              {{ c.summary }}
            </p>
          </div>

          <!-- Footer -->
          <div
            class="flex flex-wrap items-center justify-between text-xs text-zinc-400 pt-2 border-t border-white/5 gap-2">
            <span class="text-zinc-400 text-[11px]">
              Publisher / Source: <strong class="text-white">{{ c.applicant || c.court }}</strong>
            </span>
            <a v-if="c.source_url" :href="c.source_url" target="_blank" rel="noopener noreferrer"
              class="hover:text-white flex items-center gap-1 transition text-zinc-400 text-[11px]">
              <span>Original Source</span>
              <ExternalLink class="w-3 h-3" />
            </a>
          </div>
        </div>

        <!-- Paginator -->
        <div class="bg-zinc-900/40 rounded-2xl border border-white/5 p-4 flex justify-center">
          <Paginator :first="lazyParams.first" :rows="lazyParams.rows" :totalRecords="totalRecords"
            :rowsPerPageOptions="[10, 25, 50, 100]" @page="onPageChange" class="p-datatable-dark-custom" />
        </div>
      </div>
    </div>

    <!-- VIEW MODE 2: PRIME VUE DATATABLE -->
    <div v-else
      class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden p-6 sm:p-8">
      <DataTable :value="records" :lazy="true" :totalRecords="totalRecords" :loading="loading"
        :sortField="lazyParams.sortField" :sortOrder="lazyParams.sortOrder" @page="onLazy" @sort="onLazy"
        @filter="onLazy" paginator :rows="lazyParams.rows" :first="lazyParams.first"
        :rowsPerPageOptions="[10, 25, 50, 100]" dataKey="id" tableStyle="min-width: 60rem"
        class="p-datatable-dark-custom">
        <template #empty>
          <div class="py-20 text-center flex flex-col items-center">
            <div
              class="w-16 h-16 bg-zinc-800/50 rounded-full flex items-center justify-center mb-4 border border-white/5">
              <Database class="w-8 h-8 text-zinc-600" />
            </div>
            <h3 class="text-xl font-black uppercase tracking-tighter text-zinc-400 mb-1">No publications found</h3>
            <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">Try adjusting your search terms or
              filters</p>
          </div>
        </template>

        <Column field="case_number" header="Citation / Volume" sortable style="width: 18%">
          <template #body="{ data }">
            <span v-if="data.case_number"
              class="font-mono text-xs font-bold px-3 py-1.5 bg-black/60 border border-primary/20 text-primary rounded-lg inline-block shadow-sm">
              {{ data.case_number }}
            </span>
            <span v-else class="text-xs text-zinc-500 font-bold uppercase tracking-widest">N/A</span>
          </template>
          <template #loading>
            <Skeleton width="80%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="court" header="Journal / Forum" sortable style="width: 20%">
          <template #body="{ data }">
            <span
              class="px-3 py-1 bg-white/5 border border-white/10 text-zinc-200 font-bold text-[10px] uppercase tracking-wider rounded-lg inline-block shadow-sm">
              {{ data.applicant || data.court || data.record_type }}
            </span>
          </template>
          <template #loading>
            <Skeleton width="60%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="document_date" header="Publication Date" sortable style="width: 12%">
          <template #body="{ data }">
            <span class="text-xs font-bold font-mono text-zinc-300 tracking-wider">
              {{ data.document_date || 'N/A' }}
            </span>
          </template>
          <template #loading>
            <Skeleton width="70%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="title" header="Article Title / Publication Name" style="width: 36%">
          <template #body="{ data }">
            <div
              class="font-bold text-sm text-white uppercase tracking-tight hover:text-primary transition cursor-pointer"
              @click="viewRecordDetail(data)">
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

        <Column header="Actions" style="width: 14%" class="text-right">
          <template #body="{ data }">
            <button @click="viewRecordDetail(data)"
              class="btn btn-primary px-3.5 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-xl flex items-center justify-center gap-1.5 shadow-md shadow-primary/20 cursor-pointer">
              <BookOpen class="w-3.5 h-3.5" />
              <span>Read</span>
            </button>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Document Detail Modal -->
    <RecordDetailModal :show="detailModalVisible" :loading="detailLoading" :record-detail="selectedDetail"
      category="journals" @close="detailModalVisible = false" />
  </component>
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
