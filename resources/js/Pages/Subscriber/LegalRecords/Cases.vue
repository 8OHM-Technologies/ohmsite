<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SubscriberLayout from '@/Layouts/SubscriberLayout.vue';
import RecordDetailModal from './Components/RecordDetailModal.vue';
import axios from 'axios';
import {
  Scale,
  Search,
  Eye,
  RefreshCw,
  Database
} from 'lucide-vue-next';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
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

const detailModalVisible = ref(false);
const detailLoading = ref(false);
const selectedDetail = ref<any>(null);

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
    const response = await axios.get('/legal-records/data', {
      params: {
        offset: first,
        limit: rows,
        category: 'cases',
        search: searchQuery.value,
        record_type: selectedRecordType.value,
        sort_field: sortField,
        sort_order: sortOrder
      }
    });

    records.value = response.data.records;
    totalRecords.value = response.data.total;
  } catch (error) {
    console.error('Failed to fetch case law records:', error);
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

const COURT_NAMES_MAP: Record<string, string> = {
  'ZACC': 'Constitutional Court of South Africa',
  'ZASCA': 'Supreme Court of Appeal of South Africa',
  'ZAGPPHC': 'Gauteng High Court, Pretoria',
  'ZAGPJHC': 'Gauteng High Court, Johannesburg',
  'ZAWCHC': 'Western Cape High Court, Cape Town',
  'ZAFSHC': 'Free State High Court, Bloemfontein',
  'ZAKZNDHC': 'KwaZulu-Natal High Court, Durban',
  'ZAKZNHC': 'KwaZulu-Natal High Court, Pietermaritzburg',
  'ZAECGHC': 'Eastern Cape High Court, Grahamstown',
  'ZAECPEHC': 'Eastern Cape High Court, Port Elizabeth',
  'ZAECELHC': 'Eastern Cape High Court, East London',
  'ZAECBHC': 'Eastern Cape High Court, Bhisho',
  'ZALMPPHC': 'Limpopo High Court, Polokwane',
  'ZANWHC': 'North West High Court, Mahikeng',
  'ZANCHC': 'Northern Cape High Court, Kimberley',
  'ZALC': 'Labour Court of South Africa',
  'ZALAC': 'Labour Appeal Court of South Africa',
  'ZACAC': 'Competition Appeal Court of South Africa',
  'ZAEQC': 'Equality Court of South Africa',
  'ZALCC': 'Land Claims Court of South Africa',
  'ZATC': 'Tax Court of South Africa',
  'ZAECC': 'Electoral Court of South Africa',
  'ZALCJHB': 'Labour Court, Johannesburg',
  'ZALCPE': 'Labour Court, Port Elizabeth',
  'ZALCCT': 'Labour Court, Cape Town',
  'ZALCD': 'Labour Court, Durban',
  'ZALMPTHC': 'Limpopo High Court, Thohoyandou',
  'ZAMPMHC': 'Mpumalanga High Court, Middelburg',
  'ZAMPMBHC': 'Mpumalanga High Court, Mbombela',
  'ZAKZDHC': 'KwaZulu-Natal High Court, Durban',
  'ZAKZPHC': 'KwaZulu-Natal High Court, Pietermaritzburg',
  'ZAGPHC': 'Gauteng High Court',
  'ZAKZHC': 'KwaZulu-Natal High Court',
  'ZAECHC': 'Eastern Cape High Court'
};

const formatCourtName = (court: any): string => {
  if (!court) return '';
  const cStr = String(court).trim();
  const cUpper = cStr.toUpperCase();
  if (COURT_NAMES_MAP[cUpper]) {
    return COURT_NAMES_MAP[cUpper];
  }
  return cStr;
};

onMounted(() => {
  loadLazyRecords();
});
</script>

<template>
  <Head title="8OHM | Case Law & Court Judgments" />

  <component :is="LayoutComponent">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 lg:mb-12 gap-6">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-admin-modern/10 border border-admin-modern/20 flex items-center justify-center text-admin-modern shrink-0">
            <Scale class="w-5 h-5" />
          </div>
          <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tighter text-primary">
            Case Law &amp; Judgments
          </h1>
        </div>
        <div>
          <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">
            Open Access South African Case Law from the Constitutional Court, Supreme Court of Appeal, High Courts, and CCMA Awards
          </p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <span
          class="inline-flex items-center gap-2 px-4 py-3 bg-zinc-900 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-admin-modern shadow-md">
          <span class="w-2 h-2 rounded-full bg-admin-modern animate-pulse"></span>
          {{ totalRecords.toLocaleString() }} Active Records
        </span>
      </div>
    </div>

    <!-- Filter & Search Controls Container -->
    <div
      class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden p-6 sm:p-8 space-y-6 mb-8">
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">

        <!-- Global Search Field -->
        <div class="relative flex-1 max-w-2xl">
          <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
          <input type="text" v-model="searchQuery" @input="onSearchInput"
            placeholder="Search by Case #, Applicant, Respondent, Court, or Keywords..."
            class="w-full bg-black/60 border border-white/10 rounded-xl py-3.5 pl-11 pr-4 text-xs font-bold text-white focus:ring-1 focus:ring-admin-modern/50 focus:border-admin-modern/50 placeholder:text-zinc-500 shadow-inner" />
        </div>

        <!-- Court / Source Dropdown Selection -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative min-w-[260px]">
            <select :value="selectedRecordType" @change="setRecordType(($event.target as HTMLSelectElement).value)"
              class="w-full bg-black/60 border border-white/10 rounded-xl py-3 px-4 text-xs font-bold text-white focus:ring-1 focus:ring-admin-modern/50 focus:border-admin-modern/50">
              <option value="">All Courts &amp; CCMA</option>
              <option v-for="filter in filters" :key="filter.target_name" :value="filter.target_name">
                {{ filter.vanity_name }}
              </option>
            </select>
          </div>

          <button @click="loadLazyRecords()"
            class="p-3 bg-zinc-800 border border-white/10 text-zinc-300 hover:text-white hover:bg-zinc-700 rounded-xl transition-all flex items-center justify-center"
            title="Refresh Dataset">
            <RefreshCw class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- PrimeVue DataTable Container -->
    <div class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden p-6 sm:p-8">
      <DataTable :value="records" :lazy="true" :totalRecords="totalRecords" :loading="loading" @page="onLazy"
        @sort="onLazy" @filter="onLazy" paginator :rows="25" :rowsPerPageOptions="[10, 25, 50, 100]" dataKey="id"
        stateStorage="local" stateKey="legal-records-cases-datatable-state" tableStyle="min-width: 60rem"
        class="p-datatable-dark-custom">
        <template #empty>
          <div class="py-20 text-center flex flex-col items-center">
            <div
              class="w-16 h-16 bg-zinc-800/50 rounded-full flex items-center justify-center mb-4 border border-white/5">
              <Database class="w-8 h-8 text-zinc-600" />
            </div>
            <h3 class="text-xl font-black uppercase tracking-tighter text-zinc-400 mb-1">No case records found</h3>
            <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">Try adjusting your search terms or filters</p>
          </div>
        </template>

        <Column field="case_number" header="Case Reference" sortable style="width: 18%">
          <template #body="{ data }">
            <span v-if="data.case_number"
              class="font-mono text-xs font-bold px-3 py-1.5 bg-black/60 border border-white/10 text-white rounded-lg inline-block shadow-sm">
              {{ data.case_number }}
            </span>
            <span v-else class="text-xs text-zinc-500 font-bold uppercase tracking-widest">N/A</span>
          </template>
          <template #loading>
            <Skeleton width="80%" height="1.5rem" class="bg-zinc-800" />
          </template>
        </Column>

        <Column field="court" header="Court / Forum" sortable style="width: 18%">
          <template #body="{ data }">
            <span
              class="px-3 py-1 bg-admin-modern/10 border border-admin-modern/30 text-admin-modern font-black text-[10px] uppercase tracking-wider rounded-lg inline-block shadow-sm">
              {{ formatCourtName(data.court) }}
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

        <Column field="title" header="Title / Matter" style="width: 38%">
          <template #body="{ data }">
            <div
              class="font-black text-sm text-white uppercase tracking-tight hover:text-admin-modern transition cursor-pointer"
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

        <Column header="Actions" style="width: 12%" class="text-right">
          <template #body="{ data }">
            <button @click="viewRecordDetail(data)"
              class="w-full sm:w-auto bg-white text-black px-4 py-2 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-admin-modern hover:text-black transition-all shadow-md active:scale-95 flex items-center justify-center gap-1.5">
              <Eye class="w-3.5 h-3.5" />
              View
            </button>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Document Detail Modal -->
    <RecordDetailModal
      :show="detailModalVisible"
      :loading="detailLoading"
      :record-detail="selectedDetail"
      @close="detailModalVisible = false"
    />
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
</style>
