<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
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
  Database,
  Building2,
  Calendar,
  ExternalLink,
  Coins,
  ShieldAlert,
  Gavel,
  Award,
  Sparkles,
  Lock,
  ArrowRight,
  Filter,
  CheckCircle2,
  BookOpen,
  SlidersHorizontal,
  ChevronRight,
  FileText,
  User,
  AlertTriangle,
  History,
  Layers
} from 'lucide-vue-next';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Skeleton from 'primevue/skeleton';
import Paginator from 'primevue/paginator';

interface AuthUser {
  id: number;
  role: string;
  is_subscribed?: boolean;
  has_pro_access?: boolean;
}

const props = defineProps<{
  initialSummary?: any;
}>();

const page = usePage();
const user = computed(() => (page.props.auth?.user as unknown as AuthUser | null) || null);
const isAdmin = computed(() => user.value && user.value.role === 'admin');
const isPro = computed(() => {
  if (isAdmin.value) return true;
  if (user.value?.is_subscribed || user.value?.has_pro_access) return true;
  return false;
});
const LayoutComponent = computed(() => isAdmin.value ? AdminLayout : SubscriberLayout);

// Active Tab Mode: 'precedents' | 'cross_reference' | 'entity_profile'
const activeTab = ref<'precedents' | 'cross_reference' | 'entity_profile'>('precedents');

// --- Precedent Search State ---
const searchQuery = ref('');
const selectedRegulator = ref('All');
const selectedCategory = ref('All');
const selectedMinPenalty = ref<number | null>(null);

const records = ref<any[]>([]);
const totalRecords = ref(0);
const loading = ref(false);
const currentPage = ref(0);
const rowsPerPage = ref(20);

// Modal Detail State
const selectedRecord = ref<any>(null);
const modalLoading = ref(false);
const showDetailModal = ref(false);

const openDetailModal = async (record: any) => {
  showDetailModal.value = true;
  modalLoading.value = true;
  selectedRecord.value = record;

  try {
    const res = await axios.get(`/legal-records/record/${record.id}`);
    selectedRecord.value = res.data;
  } catch (err) {
    console.error('Failed to load full record detail:', err);
  } finally {
    modalLoading.value = false;
  }
};

const closeDetailModal = () => {
  showDetailModal.value = false;
  selectedRecord.value = null;
};

// Fetch Precedents from Backend
let searchTimeout: any = null;
const fetchPrecedents = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/legal-records/precedents/data', {
      params: {
        q: searchQuery.value || undefined,
        regulator: selectedRegulator.value !== 'All' ? selectedRegulator.value : undefined,
        category: selectedCategory.value !== 'All' ? selectedCategory.value : undefined,
        min_penalty: selectedMinPenalty.value ?? undefined,
        offset: currentPage.value * rowsPerPage.value,
        limit: rowsPerPage.value,
      },
    });

    records.value = res.data.records || [];
    totalRecords.value = res.data.total || 0;
  } catch (err) {
    console.error('Failed to fetch compliance precedents:', err);
    records.value = [];
    totalRecords.value = 0;
  } finally {
    loading.value = false;
  }
};

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    currentPage.value = 0;
    fetchPrecedents();
  }, 350);
};

const onPageChange = (event: any) => {
  currentPage.value = event.page;
  rowsPerPage.value = event.rows;
  fetchPrecedents();
};

const setPenaltyFilter = (min: number | null) => {
  selectedMinPenalty.value = min;
  currentPage.value = 0;
  fetchPrecedents();
};

const formatCurrency = (val: number | null) => {
  if (val === null || val === undefined || isNaN(val)) return '—';
  return new Intl.NumberFormat('en-ZA', { style: 'currency', currency: 'ZAR', maximumFractionDigits: 0 }).format(val);
};

// --- Statutory Cross-Reference State ---
const crossRefSection = ref('Section 167');
const crossRefData = ref<any>(null);
const crossRefLoading = ref(false);

const quickSections = [
  { label: 'Insurance Act § 167 (Financial Penalties)', value: 'Section 167' },
  { label: 'PPR Rule 17 (Claims Management & Repudiation)', value: 'Rule 17' },
  { label: 'FSRA § 230 (Tribunal Reconsiderations)', value: 'Section 230' },
  { label: 'FAIS Code § 2 (General Duty of Fairness)', value: 'Section 2' },
  { label: 'POPIA § 89 (Data Subject Remedies)', value: 'Section 89' },
  { label: 'LTIA § 54 (Claims Prescription)', value: 'Section 54' },
];

const fetchCrossReference = async (sectionToLookup?: string) => {
  const query = sectionToLookup || crossRefSection.value;
  if (!query.trim()) return;

  crossRefSection.value = query;
  crossRefLoading.value = true;
  try {
    const res = await axios.get('/legal-records/precedents/cross-reference', {
      params: { statute_section: query },
    });
    crossRefData.value = res.data;
  } catch (err) {
    console.error('Failed to cross-reference statute section:', err);
    crossRefData.value = null;
  } finally {
    crossRefLoading.value = false;
  }
};

// --- Entity Compliance Profile State ---
const entityQuery = ref('');
const entityProfileData = ref<any>(null);
const entityProfileLoading = ref(false);

const popularEntities = ['Discovery', 'Sanlam', 'Old Mutual', 'Liberty', 'Momentum', 'Hollard', 'Guardrisk'];

const fetchEntityProfile = async (nameToLookup?: string) => {
  const name = nameToLookup || entityQuery.value;
  if (!name.trim()) return;

  entityQuery.value = name;
  entityProfileLoading.value = true;
  try {
    const res = await axios.get('/legal-records/precedents/entity-profile', {
      params: { entity_name: name },
    });
    entityProfileData.value = res.data;
  } catch (err) {
    console.error('Failed to fetch entity profile:', err);
    entityProfileData.value = null;
  } finally {
    entityProfileLoading.value = false;
  }
};

onMounted(() => {
  fetchPrecedents();
});
</script>

<template>
  <component :is="LayoutComponent">
    <Head title="Legal Precedent & Compliance Search" />

    <div class="space-y-6 sm:space-y-8 w-full">
      <!-- Header Banner -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-zinc-900 via-zinc-950 to-black border border-white/10 p-6 sm:p-8 shadow-2xl">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-3 max-w-2xl">
            <div class="flex items-center gap-2">
              <span class="px-3 py-1 rounded-full text-xs font-black tracking-wider uppercase bg-primary/10 text-primary border border-primary/20 flex items-center gap-1.5 shadow-sm">
                <Scale class="w-3.5 h-3.5" /> Legal Precedent &amp; Compliance Engine
              </span>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-white/5 text-zinc-400 border border-white/5">
                DuckDB Vectorized
              </span>
            </div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-tight leading-tight">
              Regulatory Precedent Search
            </h1>
            <p class="text-sm text-zinc-400 leading-relaxed">
              Instant cross-referencing across FSCA administrative sanctions, Prudential Authority standards, Financial Services Tribunal reconsiderations, FAIS &amp; NFO Ombud determinations, and POPIA notices.
            </p>
          </div>

          <!-- Quick Navigation Pill Switcher -->
          <div class="flex flex-wrap md:flex-col gap-2 shrink-0">
            <button
              @click="activeTab = 'precedents'"
              :class="[activeTab === 'precedents' ? 'bg-primary text-black font-bold shadow-lg shadow-primary/20' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10']"
              class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer">
              <Search class="w-4 h-4" /> Precedent Search
            </button>
            <button
              @click="activeTab = 'cross_reference'; if (!crossRefData) fetchCrossReference();"
              :class="[activeTab === 'cross_reference' ? 'bg-primary text-black font-bold shadow-lg shadow-primary/20' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10']"
              class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer">
              <Layers class="w-4 h-4" /> Statutory Cross-Reference
            </button>
            <button
              @click="activeTab = 'entity_profile'"
              :class="[activeTab === 'entity_profile' ? 'bg-primary text-black font-bold shadow-lg shadow-primary/20' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10']"
              class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer">
              <Building2 class="w-4 h-4" /> Entity Enforcement History
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 1: PRECEDENT SEARCH                    -->
      <!-- ========================================== -->
      <div v-if="activeTab === 'precedents'" class="space-y-6">
        <!-- Search & Facet Control Panel -->
        <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-5 space-y-4 backdrop-blur-md">
          <!-- Main Search Input -->
          <div class="relative">
            <Search class="w-5 h-5 text-zinc-500 absolute left-4 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="text"
              placeholder="Search by keywords, respondent name, contravention (e.g. 'non-disclosure', 'unauthorized trade', 'Discovery')..."
              class="w-full pl-12 pr-4 py-3 bg-black/40 border border-white/10 rounded-xl text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all"
            />
          </div>

          <!-- Faceted Filter Row -->
          <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-white/5">
            <div class="flex flex-wrap items-center gap-3">
              <!-- Regulator Dropdown -->
              <div class="flex items-center gap-2">
                <span class="text-xs text-zinc-500 font-medium">Regulator:</span>
                <select
                  v-model="selectedRegulator"
                  @change="currentPage = 0; fetchPrecedents()"
                  class="bg-zinc-800/80 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-zinc-200 focus:outline-none focus:border-primary">
                  <option value="All">All Regulatory Bodies</option>
                  <option value="FSCA">FSCA (Conduct Authority)</option>
                  <option value="Prudential Authority">Prudential Authority (PA)</option>
                  <option value="Information Regulator">Information Regulator (POPIA)</option>
                  <option value="Financial Services Tribunal">Financial Services Tribunal</option>
                  <option value="FAIS Ombud">FAIS Ombud</option>
                  <option value="National Financial Ombud">National Financial Ombud</option>
                </select>
              </div>

              <!-- Category Dropdown -->
              <div class="flex items-center gap-2">
                <span class="text-xs text-zinc-500 font-medium">Category:</span>
                <select
                  v-model="selectedCategory"
                  @change="currentPage = 0; fetchPrecedents()"
                  class="bg-zinc-800/80 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-zinc-200 focus:outline-none focus:border-primary">
                  <option value="All">All Categories</option>
                  <option value="regulatory">Regulatory Enforcement &amp; Sanctions</option>
                  <option value="tribunal">Tribunal Reconsiderations</option>
                  <option value="ombud">Ombud Dispute Determinations</option>
                </select>
              </div>
            </div>

            <!-- Penalty Threshold Filter Pills -->
            <div class="flex items-center gap-1.5">
              <span class="text-xs text-zinc-500 font-medium mr-1">Penalty:</span>
              <button
                @click="setPenaltyFilter(null)"
                :class="[selectedMinPenalty === null ? 'bg-primary/20 text-primary border-primary/40 font-bold' : 'bg-white/5 text-zinc-400 hover:text-white border-white/5']"
                class="px-2.5 py-1 rounded-lg text-[11px] border transition-all cursor-pointer">
                All
              </button>
              <button
                @click="setPenaltyFilter(100000)"
                :class="[selectedMinPenalty === 100000 ? 'bg-amber-500/20 text-amber-400 border-amber-500/40 font-bold' : 'bg-white/5 text-zinc-400 hover:text-white border-white/5']"
                class="px-2.5 py-1 rounded-lg text-[11px] border transition-all cursor-pointer">
                &gt; R100k
              </button>
              <button
                @click="setPenaltyFilter(500000)"
                :class="[selectedMinPenalty === 500000 ? 'bg-rose-500/20 text-rose-400 border-rose-500/40 font-bold' : 'bg-white/5 text-zinc-400 hover:text-white border-white/5']"
                class="px-2.5 py-1 rounded-lg text-[11px] border transition-all cursor-pointer">
                &gt; R500k
              </button>
              <button
                @click="setPenaltyFilter(1000000)"
                :class="[selectedMinPenalty === 1000000 ? 'bg-rose-500/30 text-rose-300 border-rose-500/50 font-bold' : 'bg-white/5 text-zinc-400 hover:text-white border-white/5']"
                class="px-2.5 py-1 rounded-lg text-[11px] border transition-all cursor-pointer">
                &gt; R1M
              </button>
            </div>
          </div>
        </div>

        <!-- Precedent Results Table -->
        <div class="bg-zinc-900/60 border border-white/5 rounded-3xl overflow-hidden backdrop-blur-md shadow-xl">
          <div class="p-4 sm:px-6 flex items-center justify-between border-b border-white/5 bg-zinc-900/40">
            <div class="flex items-center gap-3">
              <h3 class="text-sm font-bold text-white tracking-wide uppercase flex items-center gap-2">
                <Database class="w-4 h-4 text-primary" /> Precedent &amp; Sanction Records
              </h3>
              <span class="text-xs text-zinc-400 font-mono">
                ({{ totalRecords.toLocaleString() }} found)
              </span>
            </div>

            <button
              @click="fetchPrecedents"
              class="p-2 text-zinc-400 hover:text-white rounded-lg bg-white/5 hover:bg-white/10 transition-all cursor-pointer"
              title="Refresh Records">
              <RefreshCw :class="{ 'animate-spin': loading }" class="w-4 h-4" />
            </button>
          </div>

          <!-- PrimeVue DataTable -->
          <div class="overflow-x-auto">
            <DataTable
              :value="records"
              :loading="loading"
              responsiveLayout="scroll"
              class="p-datatable-sm w-full text-left p-datatable-dark-custom"
              rowHover>
              <template #empty>
                <div class="p-12 text-center text-zinc-500 space-y-2">
                  <FileText class="w-8 h-8 mx-auto text-zinc-600 mb-2" />
                  <p class="text-sm font-semibold text-zinc-400">No regulatory precedents match the current filters.</p>
                  <p class="text-xs">Try clearing the search query or adjusting the regulator filter.</p>
                </div>
              </template>

              <template #loading>
                <div class="p-8 space-y-3">
                  <Skeleton width="100%" height="2.5rem" class="bg-zinc-800/60 rounded-xl" />
                  <Skeleton width="100%" height="2.5rem" class="bg-zinc-800/60 rounded-xl" />
                  <Skeleton width="100%" height="2.5rem" class="bg-zinc-800/60 rounded-xl" />
                </div>
              </template>

              <!-- Date Column -->
              <Column header="Date" style="min-width: 7rem">
                <template #body="{ data }">
                  <div class="text-xs text-zinc-400 font-mono flex items-center gap-1.5">
                    <Calendar class="w-3.5 h-3.5 text-zinc-500 shrink-0" />
                    <span>{{ data.document_date || '—' }}</span>
                  </div>
                </template>
              </Column>

              <!-- Regulator Badge -->
              <Column header="Authority / Regulator" style="min-width: 12rem">
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <span
                      v-if="data.regulator === 'FSCA'"
                      class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
                      <ShieldAlert class="w-3 h-3" /> FSCA
                    </span>
                    <span
                      v-else-if="data.regulator === 'Prudential Authority'"
                      class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center gap-1">
                      <Building2 class="w-3 h-3" /> Prudential Authority
                    </span>
                    <span
                      v-else-if="data.regulator === 'Information Regulator'"
                      class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center gap-1">
                      <Lock class="w-3 h-3" /> POPIA Regulator
                    </span>
                    <span
                      v-else-if="data.regulator === 'Financial Services Tribunal'"
                      class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20 flex items-center gap-1">
                      <Gavel class="w-3 h-3" /> Tribunal
                    </span>
                    <span
                      v-else
                      class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1">
                      <Award class="w-3 h-3" /> {{ data.regulator }}
                    </span>
                  </div>
                </template>
              </Column>

              <!-- Matter / Respondent Title -->
              <Column header="Matter & Respondent" style="min-width: 18rem">
                <template #body="{ data }">
                  <div class="space-y-1">
                    <div class="text-xs font-bold text-white leading-snug line-clamp-1 hover:text-primary transition-colors cursor-pointer" @click="openDetailModal(data)">
                      {{ data.title }}
                    </div>
                    <div class="text-[11px] text-zinc-400 flex items-center gap-2">
                      <span v-if="data.respondent" class="text-zinc-300 font-medium">
                        Target: {{ data.respondent }}
                      </span>
                      <span v-if="data.case_number" class="font-mono text-[10px] bg-black/40 px-1.5 py-0.5 rounded border border-white/5 text-zinc-500">
                        {{ data.case_number }}
                      </span>
                    </div>
                  </div>
                </template>
              </Column>

              <!-- Financial Penalty / Award -->
              <Column header="Penalty / Award" style="min-width: 9rem">
                <template #body="{ data }">
                  <div v-if="data.penalty_amount" class="text-xs font-black font-mono text-rose-300 bg-rose-500/10 border border-rose-500/20 px-2 py-1 rounded-lg inline-flex items-center gap-1">
                    <Coins class="w-3 h-3 text-rose-400" />
                    {{ formatCurrency(data.penalty_amount) }}
                  </div>
                  <span v-else class="text-xs text-zinc-600 font-mono">—</span>
                </template>
              </Column>

              <!-- Action Type & Summary -->
              <Column header="Action / Outcome" style="min-width: 14rem">
                <template #body="{ data }">
                  <div class="space-y-0.5">
                    <span class="text-xs text-zinc-300 font-medium">{{ data.action_type || 'Regulatory Determination' }}</span>
                    <p v-if="data.summary" class="text-[11px] text-zinc-500 line-clamp-1">
                      {{ data.summary }}
                    </p>
                  </div>
                </template>
              </Column>

              <!-- Actions Column -->
              <Column header="Actions" style="min-width: 7rem" alignFrozen="right">
                <template #body="{ data }">
                  <button
                    @click="openDetailModal(data)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white/5 hover:bg-primary hover:text-black text-zinc-300 transition-all flex items-center gap-1.5 cursor-pointer">
                    <Eye class="w-3.5 h-3.5" /> View
                  </button>
                </template>
              </Column>
            </DataTable>
          </div>

          <!-- Paginator -->
          <div class="p-4 border-t border-white/5 bg-zinc-900/40">
            <Paginator
              :rows="rowsPerPage"
              :totalRecords="totalRecords"
              :first="currentPage * rowsPerPage"
              :rowsPerPageOptions="[10, 20, 50, 100]"
              @page="onPageChange"
              template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords} records"
              class="p-datatable-dark-custom"
            />
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 2: STATUTORY CROSS-REFERENCE           -->
      <!-- ========================================== -->
      <div v-else-if="activeTab === 'cross_reference'" class="space-y-6">
        <!-- Section Lookup Search Box -->
        <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-6 space-y-4 backdrop-blur-md">
          <div class="max-w-2xl space-y-2">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <Layers class="w-5 h-5 text-primary" /> Statute Section Cross-Referencer
            </h3>
            <p class="text-xs text-zinc-400">
              Enter any legislative section or regulatory rule to discover how the FSCA, Financial Services Tribunal, FAIS Ombud, and High Courts interpret, contravene, or enforce it.
            </p>
          </div>

          <!-- Input with Action -->
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
              <BookOpen class="w-5 h-5 text-zinc-500 absolute left-4 top-1/2 -translate-y-1/2" />
              <input
                v-model="crossRefSection"
                @keyup.enter="fetchCrossReference()"
                type="text"
                placeholder="e.g. 'Section 167', 'Rule 17', 'Section 89'..."
                class="w-full pl-12 pr-4 py-3 bg-black/40 border border-white/10 rounded-xl text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-primary transition-all"
              />
            </div>
            <button
              @click="fetchCrossReference()"
              class="px-6 py-3 rounded-xl text-xs font-bold bg-primary text-black hover:brightness-110 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-primary/20 shrink-0">
              <Search class="w-4 h-4" /> Cross-Reference Section
            </button>
          </div>

          <!-- Quick Chips -->
          <div class="pt-2 flex flex-wrap items-center gap-2">
            <span class="text-xs text-zinc-500 mr-1">Foundational Sections:</span>
            <button
              v-for="chip in quickSections"
              :key="chip.value"
              @click="fetchCrossReference(chip.value)"
              class="px-3 py-1 rounded-lg text-xs font-medium bg-white/5 hover:bg-white/10 text-zinc-300 border border-white/5 transition-all cursor-pointer">
              {{ chip.label }}
            </button>
          </div>
        </div>

        <!-- Cross Reference Results -->
        <div v-if="crossRefLoading" class="p-12 text-center text-zinc-400 space-y-4">
          <RefreshCw class="w-8 h-8 mx-auto animate-spin text-primary" />
          <p class="text-sm">Cross-referencing statute section across all enforcement archives...</p>
        </div>

        <div v-else-if="crossRefData" class="space-y-6">
          <!-- Overview KPI Metrics -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-5 space-y-1">
              <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Statutory Section</div>
              <div class="text-xl font-bold text-white">{{ crossRefData.statute_section }}</div>
            </div>
            <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-5 space-y-1">
              <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Total Enforcement Records</div>
              <div class="text-xl font-bold text-primary font-mono">{{ crossRefData.total_occurrences }}</div>
            </div>
            <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-5 space-y-1">
              <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Authorities Regulating</div>
              <div class="text-xl font-bold text-amber-400 font-mono">{{ Object.keys(crossRefData.breakdown_by_regulator || {}).length }}</div>
            </div>
          </div>

          <!-- Breakdown by Regulator Pills -->
          <div v-if="Object.keys(crossRefData.breakdown_by_regulator || {}).length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
            <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Regulator Action Breakdown</h4>
            <div class="flex flex-wrap gap-3">
              <div
                v-for="(count, reg) in crossRefData.breakdown_by_regulator"
                :key="reg"
                class="px-3.5 py-2 rounded-xl bg-black/40 border border-white/5 flex items-center gap-3">
                <span class="text-xs text-zinc-300 font-medium">{{ reg }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-primary/20 text-primary">
                  {{ count }}
                </span>
              </div>
            </div>
          </div>

          <!-- Common Contraventions Discovered -->
          <div v-if="(crossRefData.common_contraventions || []).length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
            <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
              <AlertTriangle class="w-4 h-4 text-amber-400" /> Frequent Contraventions Under This Section
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div
                v-for="(contra, idx) in crossRefData.common_contraventions"
                :key="idx"
                class="bg-black/30 border border-white/5 rounded-xl p-3 text-xs text-zinc-300 flex items-start gap-2">
                <span class="text-amber-400 font-bold">•</span>
                <span>{{ contra }}</span>
              </div>
            </div>
          </div>

          <!-- Citing Precedents List -->
          <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-6 space-y-4">
            <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
              <BookOpen class="w-4 h-4 text-primary" /> Key Precedent Decisions Citing {{ crossRefData.statute_section }}
            </h4>

            <div v-if="(crossRefData.records || []).length > 0" class="space-y-3">
              <div
                v-for="rec in crossRefData.records"
                :key="rec.id"
                class="bg-black/40 border border-white/5 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-white/20 transition-all">
                <div class="space-y-1 flex-1">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white/5 text-zinc-400">
                      {{ rec.regulator }}
                    </span>
                    <span class="text-xs text-zinc-500 font-mono">{{ rec.document_date || '—' }}</span>
                  </div>
                  <h5 class="text-sm font-bold text-white hover:text-primary transition-colors cursor-pointer" @click="openDetailModal(rec)">
                    {{ rec.title }}
                  </h5>
                  <p v-if="rec.summary" class="text-xs text-zinc-400 line-clamp-2">
                    {{ rec.summary }}
                  </p>
                </div>

                <div class="flex sm:flex-col items-center sm:items-end gap-2 shrink-0">
                  <div v-if="rec.penalty_amount" class="text-xs font-mono font-bold text-rose-300">
                    {{ formatCurrency(rec.penalty_amount) }}
                  </div>
                  <button
                    @click="openDetailModal(rec)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white/5 hover:bg-primary hover:text-black text-zinc-300 transition-all cursor-pointer">
                    View Dossier
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="text-xs text-zinc-500 italic">
              No individual precedent records linked yet for this section.
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 3: ENTITY COMPLIANCE PROFILE           -->
      <!-- ========================================== -->
      <div v-else-if="activeTab === 'entity_profile'" class="space-y-6">
        <!-- Entity Search Box -->
        <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-6 space-y-4 backdrop-blur-md">
          <div class="max-w-2xl space-y-2">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <Building2 class="w-5 h-5 text-primary" /> Institution &amp; Individual Compliance Profile
            </h3>
            <p class="text-xs text-zinc-400">
              Investigate the enforcement track record, total penalties, and dispute determinations for any insurer, broker, FSP, or director.
            </p>
          </div>

          <!-- Input with Action -->
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
              <Building2 class="w-5 h-5 text-zinc-500 absolute left-4 top-1/2 -translate-y-1/2" />
              <input
                v-model="entityQuery"
                @keyup.enter="fetchEntityProfile()"
                type="text"
                placeholder="Enter financial institution or individual name (e.g. 'Discovery', 'Sanlam', 'Old Mutual')..."
                class="w-full pl-12 pr-4 py-3 bg-black/40 border border-white/10 rounded-xl text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-primary transition-all"
              />
            </div>
            <button
              @click="fetchEntityProfile()"
              class="px-6 py-3 rounded-xl text-xs font-bold bg-primary text-black hover:brightness-110 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-primary/20 shrink-0">
              <Search class="w-4 h-4" /> Search Entity Profile
            </button>
          </div>

          <!-- Popular Entities Chips -->
          <div class="pt-2 flex flex-wrap items-center gap-2">
            <span class="text-xs text-zinc-500 mr-1">Popular Entities:</span>
            <button
              v-for="eName in popularEntities"
              :key="eName"
              @click="fetchEntityProfile(eName)"
              class="px-3 py-1 rounded-lg text-xs font-medium bg-white/5 hover:bg-white/10 text-zinc-300 border border-white/5 transition-all cursor-pointer">
              {{ eName }}
            </button>
          </div>
        </div>

        <!-- Entity Profile Data Display -->
        <div v-if="entityProfileLoading" class="p-12 text-center text-zinc-400 space-y-4">
          <RefreshCw class="w-8 h-8 mx-auto animate-spin text-primary" />
          <p class="text-sm">Aggregating historical compliance timeline...</p>
        </div>

        <div v-else-if="entityProfileData" class="space-y-6">
          <!-- Overview Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-5 space-y-1">
              <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Subject Entity</div>
              <div class="text-xl font-bold text-white">{{ entityProfileData.entity_name }}</div>
            </div>
            <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-5 space-y-1">
              <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Total Sanctions / Actions</div>
              <div class="text-xl font-bold text-primary font-mono">{{ entityProfileData.total_actions }}</div>
            </div>
            <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-5 space-y-1">
              <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Cumulative Penalties Levied</div>
              <div class="text-xl font-bold text-rose-300 font-mono">{{ formatCurrency(entityProfileData.total_penalties) }}</div>
            </div>
          </div>

          <!-- Chronological Actions Timeline -->
          <div class="bg-zinc-900/60 border border-white/5 rounded-3xl p-6 space-y-6">
            <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
              <History class="w-4 h-4 text-primary" /> Enforcement &amp; Ruling Timeline
            </h4>

            <div v-if="(entityProfileData.actions_timeline || []).length > 0" class="space-y-4 relative before:absolute before:inset-0 before:left-3 before:w-0.5 before:bg-white/10">
              <div
                v-for="item in entityProfileData.actions_timeline"
                :key="item.id"
                class="relative pl-8 space-y-1">
                <div class="absolute left-2 top-1.5 w-2.5 h-2.5 rounded-full bg-primary ring-4 ring-zinc-950"></div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-mono text-zinc-500">{{ item.date || 'Undated' }}</span>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white/5 text-zinc-400">{{ item.regulator }}</span>
                  <span v-if="item.penalty" class="text-xs font-mono font-bold text-rose-300 bg-rose-500/10 px-1.5 py-0.5 rounded">
                    {{ formatCurrency(item.penalty) }}
                  </span>
                </div>
                <h5 class="text-sm font-bold text-white hover:text-primary transition-colors cursor-pointer" @click="openDetailModal(item)">
                  {{ item.title }}
                </h5>
                <p v-if="item.summary" class="text-xs text-zinc-400">
                  {{ item.summary }}
                </p>
              </div>
            </div>
            <div v-else class="text-xs text-zinc-500 italic p-4">
              No historical regulatory sanctions recorded for "{{ entityProfileData.entity_name }}".
            </div>
          </div>
        </div>
      </div>

      <!-- Record Detail Modal (Specialized Regulatory / Tribunal / Ombud Dossier) -->
      <RecordDetailModal
        :show="showDetailModal"
        :loading="modalLoading"
        :record-detail="selectedRecord"
        @close="closeDetailModal"
      />
    </div>
  </component>
</template>

<style>
.p-datatable-dark-custom,
.p-datatable-dark-custom .p-datatable-wrapper,
.p-datatable-dark-custom .p-datatable-table-container,
.p-datatable-dark-custom .p-datatable-table {
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
  letter-spacing: 0.15em !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
  border-top: none !important;
  border-left: none !important;
  border-right: none !important;
  padding: 1rem 1.25rem !important;
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
  padding: 1.15rem 1.25rem !important;
  border: none !important;
  background: transparent !important;
  color: #d4d4d8 !important;
}

.p-datatable-dark-custom .p-datatable-emptymessage td {
  background: transparent !important;
  border: none !important;
}

.p-datatable-dark-custom.p-paginator,
.p-datatable-dark-custom .p-paginator {
  background: transparent !important;
  border: none !important;
  padding: 1rem 0 !important;
  color: #e4e4e7 !important;
}

.p-datatable-dark-custom.p-paginator .p-paginator-first,
.p-datatable-dark-custom .p-paginator .p-paginator-first,
.p-datatable-dark-custom.p-paginator .p-paginator-prev,
.p-datatable-dark-custom .p-paginator .p-paginator-prev,
.p-datatable-dark-custom.p-paginator .p-paginator-next,
.p-datatable-dark-custom .p-paginator .p-paginator-next,
.p-datatable-dark-custom.p-paginator .p-paginator-last,
.p-datatable-dark-custom .p-paginator .p-paginator-last,
.p-datatable-dark-custom.p-paginator .p-paginator-page,
.p-datatable-dark-custom .p-paginator .p-paginator-page {
  background: rgba(39, 39, 42, 0.8) !important;
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  border-radius: 0.75rem !important;
  margin: 0 0.125rem !important;
  min-width: 2.25rem !important;
  height: 2.25rem !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 0.75rem !important;
}

.p-datatable-dark-custom.p-paginator .p-paginator-page.p-highlight,
.p-datatable-dark-custom .p-paginator .p-paginator-page.p-highlight {
  background: var(--color-primary, #ff8800) !important;
  color: #000000 !important;
  font-weight: 900 !important;
  border-color: var(--color-primary, #ff8800) !important;
}

.p-datatable-dark-custom.p-paginator svg,
.p-datatable-dark-custom .p-paginator svg,
.p-datatable-dark-custom.p-paginator .p-icon,
.p-datatable-dark-custom .p-paginator .p-icon {
  fill: #ffffff !important;
  color: #ffffff !important;
  width: 0.875rem !important;
  height: 0.875rem !important;
}

.p-datatable-dark-custom.p-paginator .p-paginator-rpp-select,
.p-datatable-dark-custom .p-paginator .p-paginator-rpp-select,
.p-datatable-dark-custom.p-paginator .p-dropdown,
.p-datatable-dark-custom .p-paginator .p-dropdown,
.p-datatable-dark-custom.p-paginator .p-select,
.p-datatable-dark-custom .p-paginator .p-select {
  background: rgba(39, 39, 42, 0.8) !important;
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  border-radius: 0.5rem !important;
}

.p-datatable-dark-custom.p-paginator .p-paginator-current,
.p-datatable-dark-custom .p-paginator-current {
  color: #71717a !important;
  font-size: 0.75rem !important;
}
</style>
