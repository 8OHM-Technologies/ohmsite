<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import {
  ShieldAlert,
  Calendar,
  ExternalLink,
  CheckCircle2,
  AlertTriangle,
  FileText,
  Clock,
  Building2,
  User,
  Scale,
  Lock,
  ArrowRight,
  Sparkles,
  Coins,
  Ban,
  BookOpen,
  Info
} from 'lucide-vue-next';

const props = defineProps<{
  recordDetail: any;
  isPro: boolean;
}>();

const emit = defineEmits<{
  (e: 'review-updated', payload: { id: string; requires_human_review: boolean }): void;
}>();

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
  const targetId = props.recordDetail.extracted_record_id || props.recordDetail.id;
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

const dataObj = computed(() => {
  if (!props.recordDetail) return {};
  return props.recordDetail.data || props.recordDetail;
});

const extracted = computed(() => {
  return dataObj.value.extracted_data || {};
});

const metadata = computed(() => {
  return dataObj.value.metadata || {};
});

const regulator = computed(() => {
  if (dataObj.value.regulator) return dataObj.value.regulator;
  const rt = String(dataObj.value.record_type || props.recordDetail?.record_type || '');
  if (rt.includes('fsca')) return 'Financial Sector Conduct Authority (FSCA)';
  if (rt.includes('pa_')) return 'Prudential Authority (PA)';
  if (rt.includes('popia')) return 'Information Regulator (POPIA)';
  return 'Regulatory Authority';
});

const title = computed(() => dataObj.value.title || extracted.value.action_type || dataObj.value.name || 'Regulatory Sanction & Enforcement');
const caseNumber = computed(() => dataObj.value.case_number || metadata.value.case_number || extracted.value.case_reference || null);
const respondent = computed(() => extracted.value.respondent || extracted.value.respondent_party || dataObj.value.respondent || metadata.value.respondent || 'Undisclosed Entity');
const applicant = computed(() => extracted.value.regulator || dataObj.value.applicant || regulator.value);
const documentDate = computed(() => dataObj.value.document_date || extracted.value.enforcement_date || metadata.value.document_date || null);
const actionType = computed(() => extracted.value.action_type || extracted.value.document_category || 'Enforcement Notice / Sanction');
const sourceUrl = computed(() => dataObj.value.source_url || props.recordDetail?.source_url || null);

const penaltyAmount = computed(() => {
  const p = extracted.value.penalty_amount || extracted.value.administrative_penalty_amount || extracted.value.fine_amount || dataObj.value.penalty_amount;
  if (!p) return null;
  const num = typeof p === 'number' ? p : parseFloat(String(p).replace(/[^0-9.]/g, ''));
  return isNaN(num) ? null : num;
});

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('en-ZA', { style: 'currency', currency: 'ZAR', maximumFractionDigits: 0 }).format(val);
};

const sanctions = computed(() => {
  const s = extracted.value.sanctions || extracted.value.enforcement_action || extracted.value.sanction_outcome || dataObj.value.sanctions;
  if (Array.isArray(s)) return s.filter(Boolean);
  if (typeof s === 'string' && s.trim()) return [s.trim()];
  return [];
});

const contraventions = computed(() => {
  const c = extracted.value.statutory_contraventions || extracted.value.contravention_findings || dataObj.value.contraventions;
  if (Array.isArray(c)) return c.filter(Boolean);
  if (typeof c === 'string' && c.trim()) return [c.trim()];
  return [];
});

const provisions = computed(() => {
  const p = extracted.value.statutory_sections_cited || extracted.value.keywords || dataObj.value.key_provisions;
  if (Array.isArray(p)) return p.filter(Boolean);
  if (typeof p === 'string' && p.trim()) return p.split(',').map(s => s.trim()).filter(Boolean);
  return [];
});

const summary = computed(() => {
  return extracted.value.factual_summary || dataObj.value.summary || extracted.value.executive_summary || null;
});

const debarment = computed(() => {
  return extracted.value.debarment_period || extracted.value.debarment || null;
});
</script>

<template>
  <div class="space-y-6">
    <!-- Regulatory Header Card -->
    <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-6 relative overflow-hidden backdrop-blur-sm">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-2">
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-black tracking-wider uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1.5">
              <ShieldAlert class="w-3.5 h-3.5" />
              {{ regulator }}
            </span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/5 text-zinc-400">
              {{ actionType }}
            </span>
          </div>
          <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">{{ title }}</h2>
          <div class="flex flex-wrap items-center gap-4 text-xs text-zinc-400">
            <div class="flex items-center gap-1.5 text-zinc-300">
              <Building2 class="w-4 h-4 text-amber-400/80" />
              <span class="text-zinc-500">Subject:</span>
              <strong class="font-semibold text-white">{{ respondent }}</strong>
            </div>
            <div v-if="documentDate" class="flex items-center gap-1.5">
              <Calendar class="w-4 h-4 text-zinc-500" />
              <span>{{ documentDate }}</span>
            </div>
            <div v-if="caseNumber" class="flex items-center gap-1.5 font-mono text-[11px] bg-black/40 px-2 py-0.5 rounded border border-white/5">
              <span>Ref: {{ caseNumber }}</span>
            </div>
          </div>
        </div>

        <!-- Penalty Spotlight -->
        <div v-if="penaltyAmount" class="shrink-0 bg-rose-500/10 border border-rose-500/20 rounded-xl p-4 text-right">
          <div class="text-[10px] uppercase font-bold tracking-wider text-rose-400 flex items-center gap-1 justify-end">
            <Coins class="w-3 h-3" /> Administrative Penalty
          </div>
          <div class="text-2xl sm:text-3xl font-black text-rose-200 mt-1 font-mono">
            {{ formatCurrency(penaltyAmount) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Sanctions & Debarment Summary Grid -->
    <div v-if="sanctions.length > 0 || debarment" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-if="sanctions.length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
          <AlertTriangle class="w-4 h-4 text-amber-400" /> Enacted Sanctions
        </h4>
        <ul class="space-y-2">
          <li v-for="(s, idx) in sanctions" :key="idx" class="text-sm text-zinc-300 flex items-start gap-2">
            <span class="text-amber-400 font-bold shrink-0">•</span>
            <span>{{ s }}</span>
          </li>
        </ul>
      </div>

      <div v-if="debarment" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
          <Ban class="w-4 h-4 text-rose-400" /> Debarment Order
        </h4>
        <p class="text-sm text-zinc-300 font-medium">
          {{ debarment }}
        </p>
      </div>
    </div>

    <!-- Pro Content Section / Locked Blurs -->
    <div class="relative">
      <div :class="[!isPro ? 'filter blur-sm select-none opacity-40 pointer-events-none' : '']" class="space-y-6">
        <!-- Contraventions Found -->
        <div v-if="contraventions.length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
          <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
            <Scale class="w-4 h-4 text-primary" /> Statutory Contraventions & Findings
          </h4>
          <div class="grid grid-cols-1 gap-2">
            <div v-for="(c, idx) in contraventions" :key="idx" class="bg-black/30 border border-white/5 rounded-xl p-3 text-sm text-zinc-300 flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-full bg-rose-500/10 text-rose-400 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">§</span>
              <span>{{ c }}</span>
            </div>
          </div>
        </div>

        <!-- Factual Summary -->
        <div v-if="summary" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
          <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
            <FileText class="w-4 h-4 text-emerald-400" /> Factual Finding & Executive Summary
          </h4>
          <p class="text-sm text-zinc-300 leading-relaxed whitespace-pre-line">
            {{ summary }}
          </p>
        </div>

        <!-- Provisions & Sections Cited -->
        <div v-if="provisions.length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
          <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
            <BookOpen class="w-4 h-4 text-indigo-400" /> Statutory Sections & Provisions Cited
          </h4>
          <div class="flex flex-wrap gap-2">
            <span v-for="(p, idx) in provisions" :key="idx" class="px-2.5 py-1 rounded-lg text-xs font-mono bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
              {{ p }}
            </span>
          </div>
        </div>
      </div>

      <!-- Locked Overlay for Standard Subscribers -->
      <div v-if="!isPro" class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 backdrop-blur-md rounded-2xl p-6 text-center z-20 border border-amber-500/20">
        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-3 shadow-lg shadow-amber-500/10">
          <Lock class="w-6 h-6" />
        </div>
        <h3 class="text-base font-bold text-white mb-1">Subscriber Pro Feature</h3>
        <p class="text-xs text-zinc-400 max-w-md mb-4">
          Complete regulatory contraventions, forensic enforcement findings, and statutory sections require an active Pro subscription.
        </p>
        <a href="/services" class="px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-amber-500 to-amber-600 text-black hover:brightness-110 transition-all flex items-center gap-1.5 shadow-lg shadow-amber-500/20">
          <Sparkles class="w-3.5 h-3.5" /> Upgrade to Pro Access <ArrowRight class="w-3.5 h-3.5" />
        </a>
      </div>
    </div>

    <!-- Footer Controls -->
    <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-white/5 text-xs text-zinc-400">
      <div class="flex items-center gap-2">
        <a v-if="sourceUrl" :href="sourceUrl" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white/5 border border-white/5">
          <ExternalLink class="w-3.5 h-3.5" /> Original Regulatory Source
        </a>
      </div>

      <div class="flex items-center gap-2">
        <button v-if="!isConfirmingReport && !reportSuccess" @click="triggerReport" class="hover:text-rose-400 transition-colors flex items-center gap-1 text-[11px] text-zinc-500">
          <AlertTriangle class="w-3 h-3" /> Report Error in Record
        </button>
        <div v-else-if="isConfirmingReport" class="flex items-center gap-2 bg-rose-500/10 border border-rose-500/20 rounded-lg px-2.5 py-1">
          <span class="text-rose-300 text-[11px]">Confirm report?</span>
          <button @click="confirmReport" :disabled="isReporting" class="text-rose-400 font-bold hover:underline text-[11px]">Yes</button>
          <button @click="cancelReport" class="text-zinc-400 hover:underline text-[11px]">Cancel</button>
        </div>
        <span v-else-if="reportSuccess" class="text-emerald-400 flex items-center gap-1 text-[11px]">
          <CheckCircle2 class="w-3 h-3" /> Error Reported
        </span>
      </div>
    </div>
  </div>
</template>
