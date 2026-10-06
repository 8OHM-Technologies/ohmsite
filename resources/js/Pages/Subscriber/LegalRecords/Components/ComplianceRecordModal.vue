<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
  ShieldAlert,
  Building2,
  Lock,
  Gavel,
  Award,
  Scale,
  Calendar,
  X,
  ExternalLink,
  Coins,
  AlertTriangle,
  Ban,
  FileText,
  BookOpen,
  CheckCircle2,
  Sparkles,
  ArrowRight,
  User,
  Copy,
  Check,
  ChevronDown,
  ChevronUp,
  Search
} from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import Skeleton from 'primevue/skeleton';

const props = defineProps<{
  show: boolean;
  loading: boolean;
  recordDetail: any;
  category?: string;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'review-updated', payload: { id: string; requires_human_review: boolean }): void;
  (e: 'cross-reference', payload: string): void;
}>();

const page = usePage();
const authUser = computed(() => page.props.auth?.user as any);
const isAdmin = computed(() => authUser.value?.role === 'admin');

// Data Resolution
const dataObj = computed(() => {
  if (!props.recordDetail) return {};
  return props.recordDetail.data || props.recordDetail;
});

const isPro = computed(() => {
  if (authUser.value?.role === 'admin') return true;
  if (authUser.value?.is_subscribed || authUser.value?.has_pro_access) return true;
  if (props.recordDetail?.is_pro === true) return true;
  if (props.recordDetail?.data?.is_pro === true) return true;
  return false;
});

// Category and Regulator Detection
const recordType = computed(() => {
  return String(dataObj.value.record_type || props.recordDetail?.record_type || '').toLowerCase();
});

const resolvedCategory = computed(() => {
  if (props.category) return props.category;
  const rt = recordType.value;
  if (rt.includes('fst')) return 'tribunal';
  if (rt.includes('fais') || rt.includes('_nfo') || rt.startsWith('nfo')) return 'ombud';
  if (rt.includes('fsca') || rt.includes('pa_') || rt.includes('popia')) return 'regulatory';
  if (dataObj.value.category && dataObj.value.category !== 'cases') return dataObj.value.category;
  if (props.recordDetail?.category && props.recordDetail?.category !== 'cases') return props.recordDetail.category;
  return 'regulatory';
});

const regulator = computed(() => {
  if (dataObj.value.regulator) return dataObj.value.regulator;
  if (props.recordDetail?.regulator) return props.recordDetail.regulator;
  const rt = recordType.value;
  if (rt.includes('fsca')) return 'Financial Sector Conduct Authority (FSCA)';
  if (rt.includes('pa_')) return 'Prudential Authority (PA)';
  if (rt.includes('popia')) return 'Information Regulator (POPIA)';
  if (rt.includes('fst')) return 'Financial Services Tribunal (FST)';
  if (rt.includes('fais')) return 'FAIS Ombud';
  if (rt.includes('nfo')) return 'National Financial Ombud (NFO)';
  return 'Regulatory Authority';
});

// Basic Attributes
const title = computed(() => {
  return dataObj.value.title || props.recordDetail?.title || 'Compliance Matter Dossier';
});

const caseNumber = computed(() => {
  return dataObj.value.case_number || props.recordDetail?.case_number || dataObj.value.dataset_number || null;
});

const documentDate = computed(() => {
  return dataObj.value.document_date || props.recordDetail?.document_date || dataObj.value.date || null;
});

const respondent = computed(() => {
  return (
    dataObj.value.respondent ||
    props.recordDetail?.respondent ||
    dataObj.value.respondent_party ||
    dataObj.value.respondent_fsp ||
    dataObj.value.respondent_insurer ||
    null
  );
});

const applicant = computed(() => {
  return (
    dataObj.value.applicant ||
    props.recordDetail?.applicant ||
    dataObj.value.complainant ||
    regulator.value
  );
});

const actionType = computed(() => {
  return (
    dataObj.value.action_type ||
    props.recordDetail?.action_type ||
    dataObj.value.document_type ||
    dataObj.value.document_category ||
    dataObj.value.division ||
    (resolvedCategory.value === 'tribunal' ? 'Section 230 Reconsideration' : (resolvedCategory.value === 'ombud' ? 'Ombud Determination' : 'Administrative Action'))
  );
});

const sourceUrl = computed(() => {
  return dataObj.value.source_url || props.recordDetail?.source_url || dataObj.value.url || null;
});

// Penalty & Awards
const penaltyAmount = computed<number | null>(() => {
  const p = dataObj.value.penalty_amount ?? props.recordDetail?.penalty_amount ?? dataObj.value.fine_amount ?? dataObj.value.award_amount;
  if (p === null || p === undefined || p === '') return null;
  const num = typeof p === 'number' ? p : parseFloat(String(p).replace(/[^0-9.]/g, ''));
  return isNaN(num) || num <= 0 ? null : num;
});

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('en-ZA', {
    style: 'currency',
    currency: 'ZAR',
    maximumFractionDigits: 0,
  }).format(val);
};

// Sanctions & Orders
const sanctions = computed<string[]>(() => {
  const s = dataObj.value.sanctions || props.recordDetail?.sanctions || dataObj.value.sanction_outcome || dataObj.value.final_order;
  if (Array.isArray(s)) return s.filter(Boolean);
  if (typeof s === 'string' && s.trim()) {
    return s.split(/\n|(?<=\.)\s+(?=\d+\.)/).map(item => item.trim()).filter(Boolean);
  }
  return [];
});

// Debarment Detection
const debarment = computed<string | null>(() => {
  if (dataObj.value.debarment) return dataObj.value.debarment;
  if (props.recordDetail?.debarment) return props.recordDetail.debarment;
  for (const s of sanctions.value) {
    if (s.toLowerCase().includes('debarment')) {
      return s;
    }
  }
  const summaryText = String(summary.value || '');
  if (summaryText.toLowerCase().includes('debarment for a period of')) {
    const match = summaryText.match(/debarment for a period of[^.]+/i);
    if (match) return match[0];
  }
  return null;
});

// Statutory Contraventions
const contraventions = computed<string[]>(() => {
  const c = dataObj.value.contraventions || props.recordDetail?.contraventions || dataObj.value.statutory_contraventions || dataObj.value.contravention_findings || dataObj.value.repudiation_grounds;
  if (Array.isArray(c)) return c.filter(Boolean);
  if (typeof c === 'string' && c.trim()) {
    return c.split(/\n|(?<=\.)\s+(?=\d+\.)/).map(item => item.trim()).filter(Boolean);
  }
  return [];
});

// Key Statutory Provisions Cited
const keyProvisions = computed<string[]>(() => {
  const p = dataObj.value.key_provisions || props.recordDetail?.key_provisions || dataObj.value.statutory_sections_cited || dataObj.value.keywords;
  if (Array.isArray(p)) return p.filter(Boolean);
  if (typeof p === 'string' && p.trim()) {
    return p.split(',').map(s => s.trim()).filter(Boolean);
  }
  return [];
});

// Factual Summary & Reasoning
const summary = computed<string | null>(() => {
  return (
    dataObj.value.summary ||
    props.recordDetail?.summary ||
    dataObj.value.factual_summary ||
    dataObj.value.ombud_findings ||
    dataObj.value.tribunal_reasoning ||
    dataObj.value.subject_matter ||
    null
  );
});

// Full Official Record Text
const fullText = computed<string | null>(() => {
  return (
    dataObj.value.full_text ||
    dataObj.value.scraped_text ||
    dataObj.value.center_content ||
    null
  );
});

const showFullText = ref(false);
const textCopied = ref(false);
const copyFullText = async () => {
  if (!fullText.value) return;
  try {
    await navigator.clipboard.writeText(fullText.value);
    textCopied.value = true;
    setTimeout(() => {
      textCopied.value = false;
    }, 2000);
  } catch (err) {
    console.error('Failed to copy text:', err);
  }
};

// Admin Human Review State
const requiresHumanReview = ref(false);
const reviewSubmitting = ref(false);

watch(
  () => props.recordDetail,
  (newVal) => {
    if (newVal) {
      requiresHumanReview.value = Boolean(
        newVal.requires_human_review ||
        newVal.data?.requires_human_review ||
        false
      );
    } else {
      requiresHumanReview.value = false;
    }
  },
  { immediate: true }
);

const toggleHumanReview = async () => {
  if (!props.recordDetail || reviewSubmitting.value) return;
  const targetId = props.recordDetail.extracted_record_id || props.recordDetail.id || dataObj.value.id;
  if (!targetId) return;

  reviewSubmitting.value = true;
  try {
    const response = await axios.post(`/admin/legal-records/${targetId}/human-review`, {
      requires_human_review: !requiresHumanReview.value,
    });
    requiresHumanReview.value = response.data.requires_human_review;
    if (props.recordDetail) {
      props.recordDetail.requires_human_review = response.data.requires_human_review;
      if (props.recordDetail.data) {
        props.recordDetail.data.requires_human_review = response.data.requires_human_review;
      }
    }
    emit('review-updated', {
      id: String(props.recordDetail.id),
      requires_human_review: response.data.requires_human_review,
    });
  } catch (error) {
    console.error('Failed to toggle human review:', error);
  } finally {
    reviewSubmitting.value = false;
  }
};

// User Error Reporting
const isConfirmingReport = ref(false);
const isReporting = ref(false);
const reportSuccess = ref(false);
const reportErrorMsg = ref('');

const triggerReport = () => {
  if (isReporting.value || reportSuccess.value) return;
  isConfirmingReport.value = true;
};

const cancelReport = () => {
  isConfirmingReport.value = false;
};

const confirmReport = async () => {
  if (!props.recordDetail || isReporting.value) return;
  const targetId = props.recordDetail.extracted_record_id || props.recordDetail.id || dataObj.value.id;
  if (!targetId) return;

  isReporting.value = true;
  reportErrorMsg.value = '';

  try {
    const response = await axios.post(`/legal-records/record/${targetId}/report-error`);
    reportSuccess.value = true;
    isConfirmingReport.value = false;

    if (props.recordDetail) {
      props.recordDetail.requires_human_review = true;
      props.recordDetail.review_reason = response.data.review_reason || 'Reported by user as containing errors.';
      if (props.recordDetail.data) {
        props.recordDetail.data.requires_human_review = true;
        props.recordDetail.data.review_reason = response.data.review_reason || 'Reported by user as containing errors.';
      }
    }

    emit('review-updated', {
      id: String(props.recordDetail.id),
      requires_human_review: true,
    });
  } catch (error: any) {
    console.error('Failed to report record errors:', error);
    reportErrorMsg.value = error.response?.data?.message || 'Failed to submit report. Please try again.';
  } finally {
    isReporting.value = false;
  }
};
</script>

<template>
  <Modal :show="show" @close="emit('close')" maxWidth="5xl">
    <div
      class="relative bg-zinc-950 text-white overflow-hidden max-h-[92vh] flex flex-col rounded-3xl border border-white/10 shadow-2xl">
      <!-- Sticky Top Header -->
      <div
        class="flex items-start justify-between gap-4 p-6 sm:p-8 border-b border-white/10 bg-zinc-900/80 backdrop-blur-md sticky top-0 z-20">
        <div class="space-y-2.5 flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <!-- Regulator Badge -->
            <span
              v-if="regulator.includes('FSCA')"
              class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1.5 shadow-sm">
              <ShieldAlert class="w-3.5 h-3.5" /> FSCA Regulatory Enforcement
            </span>
            <span
              v-else-if="regulator.includes('Prudential') || regulator.includes('PA')"
              class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center gap-1.5 shadow-sm">
              <Building2 class="w-3.5 h-3.5" /> Prudential Authority Standard
            </span>
            <span
              v-else-if="regulator.includes('Information Regulator') || regulator.includes('POPIA')"
              class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center gap-1.5 shadow-sm">
              <Lock class="w-3.5 h-3.5" /> POPIA Information Regulator
            </span>
            <span
              v-else-if="resolvedCategory === 'tribunal' || regulator.includes('Tribunal')"
              class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase bg-purple-500/10 text-purple-400 border border-purple-500/20 flex items-center gap-1.5 shadow-sm">
              <Gavel class="w-3.5 h-3.5" /> Financial Services Tribunal
            </span>
            <span
              v-else-if="resolvedCategory === 'ombud' || regulator.includes('Ombud')"
              class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5 shadow-sm">
              <Award class="w-3.5 h-3.5" /> {{ regulator }}
            </span>
            <span
              v-else
              class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase bg-primary/10 text-primary border border-primary/20 flex items-center gap-1.5 shadow-sm">
              <Scale class="w-3.5 h-3.5" /> {{ regulator }}
            </span>

            <!-- Action Type Badge -->
            <span
              class="px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-white/5 text-zinc-300 border border-white/5">
              {{ actionType }}
            </span>

            <!-- Reference Number -->
            <span
              v-if="caseNumber"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-black/40 text-zinc-400 border border-white/5">
              Ref: {{ caseNumber }}
            </span>

            <!-- Document Date -->
            <span
              v-if="documentDate"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-medium bg-white/5 text-zinc-400 flex items-center gap-1">
              <Calendar class="w-3 h-3 text-zinc-500" /> {{ documentDate }}
            </span>

            <!-- Access Tier Badge -->
            <span
              v-if="isPro"
              class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1">
              <Sparkles class="w-3 h-3" /> Pro Dossier Unlocked
            </span>
            <span
              v-else
              class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
              <Lock class="w-3 h-3" /> Standard Preview
            </span>
          </div>

          <h2 class="text-lg sm:text-xl font-black text-white leading-snug break-words">
            {{ title }}
          </h2>
        </div>

        <button
          @click="emit('close')"
          class="p-2 text-zinc-400 hover:text-white rounded-xl bg-white/5 hover:bg-white/10 transition-all shrink-0 cursor-pointer"
          title="Close Dossier">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Scrollable Content Area -->
      <div class="flex-1 overflow-y-auto p-6 sm:p-8 custom-scrollbar space-y-6">
        <!-- Loading State Skeleton -->
        <div v-if="loading && !recordDetail" class="space-y-4">
          <Skeleton width="100%" height="4.5rem" class="bg-zinc-800/60 rounded-2xl" />
          <Skeleton width="100%" height="8rem" class="bg-zinc-800/60 rounded-2xl" />
          <Skeleton width="100%" height="12rem" class="bg-zinc-800/60 rounded-2xl" />
        </div>

        <!-- Loaded Content Area -->
        <div v-else-if="recordDetail" class="space-y-6">
          <!-- 1. Key Parties & Sanctions Ribbon Banner -->
          <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-5 backdrop-blur-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Party 1: Subject / Respondent -->
            <div class="space-y-1">
              <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 flex items-center gap-1.5">
                <Building2 v-if="resolvedCategory !== 'ombud'" class="w-3.5 h-3.5 text-amber-400" />
                <User v-else class="w-3.5 h-3.5 text-amber-400" />
                <span>{{ resolvedCategory === 'ombud' ? 'Respondent FSP / Insurer' : (resolvedCategory === 'tribunal' ? 'Applicant / Respondent' : 'Subject / Sanctioned Entity') }}</span>
              </div>
              <div class="text-sm font-black text-white break-words">
                {{ respondent || 'Regulatory Subject Entity' }}
              </div>
            </div>

            <!-- Party 2: Initiator / Regulator -->
            <div class="space-y-1">
              <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 flex items-center gap-1.5">
                <ShieldAlert class="w-3.5 h-3.5 text-primary" />
                <span>{{ resolvedCategory === 'ombud' ? 'Complainant / Consumer' : 'Enforcing / Determining Authority' }}</span>
              </div>
              <div class="text-sm font-semibold text-zinc-300 break-words">
                {{ applicant }}
              </div>
            </div>

            <!-- Spotlight: Penalty / Award Amount -->
            <div class="sm:col-span-2 lg:col-span-1 flex items-center lg:justify-end">
              <div
                v-if="penaltyAmount"
                class="w-full lg:w-auto bg-rose-500/10 border border-rose-500/20 rounded-xl p-3.5 text-left lg:text-right">
                <div class="text-[10px] uppercase font-bold tracking-wider text-rose-400 flex items-center gap-1 lg:justify-end">
                  <Coins class="w-3.5 h-3.5" />
                  {{ resolvedCategory === 'ombud' ? 'Financial Compensation Award' : 'Administrative Penalty' }}
                </div>
                <div class="text-xl sm:text-2xl font-black text-rose-200 font-mono mt-0.5">
                  {{ formatCurrency(penaltyAmount) }}
                </div>
              </div>
              <div
                v-else
                class="w-full lg:w-auto bg-white/5 border border-white/5 rounded-xl p-3 text-left lg:text-right">
                <div class="text-[10px] uppercase font-bold tracking-wider text-zinc-400">
                  Regulatory Remedy
                </div>
                <div class="text-xs font-bold text-zinc-300 mt-1">
                  Non-Monetary Directive / Statutory Compliance
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Debarment Order Alert (Prominent if present) -->
          <div
            v-if="debarment"
            class="bg-gradient-to-r from-rose-950/40 to-black border border-rose-500/30 rounded-2xl p-5 flex items-start gap-3.5 shadow-lg shadow-rose-950/20">
            <div class="w-9 h-9 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0 mt-0.5">
              <Ban class="w-5 h-5" />
            </div>
            <div class="space-y-1">
              <h4 class="text-xs font-black text-rose-300 uppercase tracking-wider">
                Formal Regulatory Debarment Order
              </h4>
              <p class="text-sm text-zinc-200 leading-relaxed font-medium">
                {{ debarment }}
              </p>
            </div>
          </div>

          <!-- 3. Sanctions & Corrective Orders -->
          <div v-if="sanctions.length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
            <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
              <AlertTriangle class="w-4 h-4 text-amber-400" />
              {{ resolvedCategory === 'tribunal' ? 'Tribunal Rulings & Orders' : (resolvedCategory === 'ombud' ? 'Ombud Final Determination Orders' : 'Enacted Sanctions & Orders') }}
            </h4>
            <div class="grid grid-cols-1 gap-2">
              <div
                v-for="(s, idx) in sanctions"
                :key="idx"
                class="bg-black/40 border border-white/5 rounded-xl p-3 text-sm text-zinc-300 flex items-start gap-2.5">
                <span class="w-5 h-5 rounded-full bg-amber-500/10 text-amber-400 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                  {{ idx + 1 }}
                </span>
                <span class="leading-relaxed">{{ s }}</span>
              </div>
            </div>
          </div>

          <!-- 4. Gated Forensic Section (Contraventions, Findings, Full Text) -->
          <div class="relative">
            <div
              :class="[!isPro ? 'filter blur-sm select-none opacity-40 pointer-events-none' : '']"
              class="space-y-6">
              <!-- Statutory Contraventions & Violations -->
              <div v-if="contraventions.length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
                <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
                  <Scale class="w-4 h-4 text-primary" />
                  {{ resolvedCategory === 'ombud' ? 'Repudiation Grounds Overturned / Investigated' : (resolvedCategory === 'tribunal' ? 'Grounds for Reconsideration' : 'Statutory Contraventions & Non-Compliance Findings') }}
                </h4>
                <div class="grid grid-cols-1 gap-2">
                  <div
                    v-for="(c, idx) in contraventions"
                    :key="idx"
                    class="bg-black/40 border border-white/5 rounded-xl p-3 text-sm text-zinc-300 flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-rose-500/10 text-rose-400 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                      §
                    </span>
                    <span class="leading-relaxed">{{ c }}</span>
                  </div>
                </div>
              </div>

              <!-- Executive Factual Summary & Legal Reasoning -->
              <div v-if="summary" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
                <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
                  <FileText class="w-4 h-4 text-emerald-400" />
                  {{ resolvedCategory === 'tribunal' ? 'Tribunal Analysis & Reasoning' : (resolvedCategory === 'ombud' ? 'Ombud Factual Findings & Rulings' : 'Factual Background & Executive Summary') }}
                </h4>
                <p class="text-sm text-zinc-300 leading-relaxed whitespace-pre-line">
                  {{ summary }}
                </p>
              </div>

              <!-- Key Statutory Provisions Cited -->
              <div v-if="keyProvisions.length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
                <div class="flex items-center justify-between">
                  <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
                    <BookOpen class="w-4 h-4 text-indigo-400" />
                    Statutory Provisions &amp; Sections Cited
                  </h4>
                  <span class="text-[11px] text-zinc-500">Click a section to cross-reference</span>
                </div>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="(prov, idx) in keyProvisions"
                    :key="idx"
                    @click="emit('cross-reference', prov)"
                    class="px-3 py-1.5 rounded-lg text-xs font-mono font-medium bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 border border-indigo-500/20 flex items-center gap-1.5 transition-all cursor-pointer"
                    title="Cross reference this section in Compliance Engine">
                    <span>{{ prov }}</span>
                    <Search class="w-3 h-3 text-indigo-400 opacity-60" />
                  </button>
                </div>
              </div>

              <!-- Full Official Record / Scraped Text Reader (Expandable) -->
              <div v-if="fullText" class="bg-zinc-900/40 border border-white/5 rounded-2xl overflow-hidden">
                <div
                  @click="showFullText = !showFullText"
                  class="p-4 flex items-center justify-between cursor-pointer hover:bg-white/[0.02] transition-colors">
                  <div class="flex items-center gap-2">
                    <FileText class="w-4 h-4 text-zinc-400" />
                    <span class="text-xs font-bold text-white uppercase tracking-wider">
                      Official Source Notice Text
                    </span>
                    <span class="text-[10px] font-mono text-zinc-500 bg-black/40 px-2 py-0.5 rounded border border-white/5">
                      {{ fullText.length.toLocaleString() }} chars
                    </span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-zinc-400">
                    <span>{{ showFullText ? 'Collapse Text' : 'Expand Full Text' }}</span>
                    <component :is="showFullText ? ChevronUp : ChevronDown" class="w-4 h-4" />
                  </div>
                </div>

                <div v-if="showFullText" class="p-5 border-t border-white/5 bg-black/60 space-y-3">
                  <div class="flex justify-end">
                    <button
                      @click="copyFullText"
                      class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white/5 hover:bg-white/10 text-zinc-300 flex items-center gap-1.5 transition-all cursor-pointer">
                      <component :is="textCopied ? Check : Copy" class="w-3.5 h-3.5" :class="{ 'text-emerald-400': textCopied }" />
                      <span>{{ textCopied ? 'Copied' : 'Copy Text' }}</span>
                    </button>
                  </div>
                  <div class="max-h-96 overflow-y-auto text-xs text-zinc-300 font-mono leading-relaxed whitespace-pre-wrap p-3 bg-zinc-950/80 rounded-xl border border-white/5 custom-scrollbar">
                    {{ fullText }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Pro Gate Blur Overlay -->
            <div
              v-if="!isPro"
              class="absolute inset-0 flex flex-col items-center justify-center bg-black/70 backdrop-blur-md rounded-2xl p-6 text-center z-10 border border-amber-500/20">
              <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-3 shadow-lg shadow-amber-500/10">
                <Lock class="w-6 h-6" />
              </div>
              <h3 class="text-base font-bold text-white mb-1">Subscriber Pro Feature</h3>
              <p class="text-xs text-zinc-400 max-w-md mb-4 leading-relaxed">
                Complete regulatory contraventions, forensic enforcement findings, and official full-text source notices require an active Pro subscription.
              </p>
              <a
                href="/#pricing"
                class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider bg-gradient-to-r from-amber-500 to-amber-600 text-black hover:brightness-110 transition-all flex items-center gap-2 shadow-lg shadow-amber-500/20">
                <Sparkles class="w-4 h-4" /> Upgrade to Pro Access <ArrowRight class="w-4 h-4" />
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Sticky Modal Actions Footer -->
      <div
        class="p-4 sm:px-8 border-t border-white/10 bg-zinc-900/80 backdrop-blur-md flex flex-wrap items-center justify-between gap-3 sticky bottom-0 z-20">
        <div>
          <a
            v-if="sourceUrl"
            :href="sourceUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/15 text-white flex items-center gap-2 transition-all cursor-pointer">
            <span>Open Original Regulatory Source</span>
            <ExternalLink class="w-3.5 h-3.5" />
          </a>
        </div>

        <div class="flex items-center gap-3">
          <!-- User Report Error in Record -->
          <div class="flex items-center gap-2">
            <button
              v-if="!isConfirmingReport && !reportSuccess"
              @click="triggerReport"
              type="button"
              class="px-3.5 py-2 rounded-xl text-xs font-semibold text-zinc-400 hover:text-rose-400 bg-white/5 hover:bg-white/10 transition-colors flex items-center gap-1.5 cursor-pointer">
              <AlertTriangle class="w-3.5 h-3.5" /> Report Error
            </button>
            <div
              v-else-if="isConfirmingReport"
              class="flex items-center gap-2 bg-rose-500/10 border border-rose-500/20 rounded-xl px-3 py-1.5">
              <span class="text-rose-300 text-xs font-medium">Flag record?</span>
              <button
                @click="confirmReport"
                :disabled="isReporting"
                class="text-rose-400 font-bold hover:underline text-xs cursor-pointer">
                Confirm
              </button>
              <button
                @click="cancelReport"
                class="text-zinc-400 hover:underline text-xs cursor-pointer">
                Cancel
              </button>
            </div>
            <span
              v-else-if="reportSuccess"
              class="text-emerald-400 flex items-center gap-1.5 text-xs font-medium px-2 py-1">
              <CheckCircle2 class="w-4 h-4" /> Error Reported
            </span>
          </div>

          <!-- Admin Mark for Human Review -->
          <button
            v-if="isAdmin"
            @click="toggleHumanReview"
            :disabled="reviewSubmitting"
            type="button"
            class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2 transition-all cursor-pointer shadow-md disabled:opacity-50"
            :class="requiresHumanReview ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 hover:bg-amber-500/30 shadow-amber-500/10' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white border border-white/10'">
            <AlertTriangle class="w-4 h-4" :class="requiresHumanReview ? 'text-amber-400' : 'text-zinc-400'" />
            <span>{{ requiresHumanReview ? 'Marked for Review' : 'Mark for Review' }}</span>
          </button>

          <!-- Close Button -->
          <button
            @click="emit('close')"
            class="btn btn-primary px-5 py-2.5 rounded-xl text-xs font-black cursor-pointer shadow-lg shadow-primary/20">
            Close Dossier
          </button>
        </div>
      </div>
    </div>
  </Modal>
</template>

<style scoped>
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
