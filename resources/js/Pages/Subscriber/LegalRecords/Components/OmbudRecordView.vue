<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import {
  Scale,
  Calendar,
  ExternalLink,
  CheckCircle2,
  AlertTriangle,
  FileText,
  Building2,
  User,
  Lock,
  ArrowRight,
  Sparkles,
  Coins,
  BookOpen,
  Award,
  HelpCircle,
  Check
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

const ombudName = computed(() => {
  const rt = String(dataObj.value.record_type || props.recordDetail?.record_type || '');
  if (rt.includes('fais')) return 'FAIS Ombud (Financial Advisory and Intermediary Services)';
  if (rt.includes('nfo')) return 'National Financial Ombud (NFO)';
  return 'Financial Sector Ombud';
});

const title = computed(() => dataObj.value.title || extracted.value.title || `Ombud Determination: ${caseNumber.value || 'Insurance Claim Dispute'}`);
const caseNumber = computed(() => dataObj.value.case_number || metadata.value.case_number || extracted.value.case_reference || null);
const complainant = computed(() => extracted.value.complainant || dataObj.value.complainant || dataObj.value.applicant || 'Complainant');
const respondentFsp = computed(() => extracted.value.respondent_fsp || extracted.value.respondent_insurer || dataObj.value.respondent || 'Financial Service Provider');
const documentDate = computed(() => dataObj.value.document_date || extracted.value.determination_date || metadata.value.document_date || null);
const sourceUrl = computed(() => dataObj.value.source_url || props.recordDetail?.source_url || null);

const awardAmount = computed(() => {
  const a = extracted.value.award_amount || extracted.value.compensation_amount || dataObj.value.penalty_amount;
  if (!a) return null;
  const num = typeof a === 'number' ? a : parseFloat(String(a).replace(/[^0-9.]/g, ''));
  return isNaN(num) ? null : num;
});

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('en-ZA', { style: 'currency', currency: 'ZAR', maximumFractionDigits: 0 }).format(val);
};

const repudiationGrounds = computed(() => {
  const r = extracted.value.repudiation_grounds || extracted.value.grounds_of_complaint || dataObj.value.contraventions;
  if (Array.isArray(r)) return r.filter(Boolean);
  if (typeof r === 'string' && r.trim()) return [r.trim()];
  return [];
});

const finalOrder = computed(() => {
  const o = extracted.value.final_order || extracted.value.determination_outcome || dataObj.value.sanctions;
  if (Array.isArray(o)) return o.filter(Boolean);
  if (typeof o === 'string' && o.trim()) return [o.trim()];
  return [];
});

const findings = computed(() => {
  return extracted.value.ombud_findings || extracted.value.reasoning || dataObj.value.summary || null;
});

const provisions = computed(() => {
  const p = extracted.value.statutory_sections_cited || extracted.value.relevant_provisions || dataObj.value.key_provisions;
  if (Array.isArray(p)) return p.filter(Boolean);
  if (typeof p === 'string' && p.trim()) return p.split(',').map(s => s.trim()).filter(Boolean);
  return [];
});
</script>

<template>
  <div class="space-y-6">
    <!-- Ombud Header Card -->
    <div class="bg-zinc-900/60 border border-white/5 rounded-2xl p-6 relative overflow-hidden backdrop-blur-sm">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-2">
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-black tracking-wider uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
              <Award class="w-3.5 h-3.5" />
              {{ ombudName }}
            </span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/5 text-zinc-400">
              Dispute Determination
            </span>
          </div>
          <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">{{ title }}</h2>
          <div class="flex flex-wrap items-center gap-4 text-xs text-zinc-400">
            <div class="flex items-center gap-1.5 text-zinc-300">
              <User class="w-4 h-4 text-emerald-400/80" />
              <span class="text-zinc-500">Complainant:</span>
              <strong class="font-semibold text-white">{{ complainant }}</strong>
            </div>
            <div class="flex items-center gap-1.5 text-zinc-300">
              <Building2 class="w-4 h-4 text-amber-400/80" />
              <span class="text-zinc-500">Respondent FSP:</span>
              <strong class="font-semibold text-white">{{ respondentFsp }}</strong>
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

        <!-- Award Amount Spotlight -->
        <div v-if="awardAmount" class="shrink-0 bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 text-right">
          <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-400 flex items-center gap-1 justify-end">
            <Coins class="w-3 h-3" /> Compensation Award
          </div>
          <div class="text-2xl sm:text-3xl font-black text-emerald-200 mt-1 font-mono">
            {{ formatCurrency(awardAmount) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Repudiation Grounds & Final Order Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Repudiation Grounds -->
      <div v-if="repudiationGrounds.length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
          <HelpCircle class="w-4 h-4 text-amber-400" /> Insurer Repudiation Grounds
        </h4>
        <ul class="space-y-2">
          <li v-for="(g, idx) in repudiationGrounds" :key="idx" class="text-sm text-zinc-300 flex items-start gap-2">
            <span class="text-amber-400 font-bold shrink-0">•</span>
            <span>{{ g }}</span>
          </li>
        </ul>
      </div>

      <!-- Final Ombud Order -->
      <div v-if="finalOrder.length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
          <CheckCircle2 class="w-4 h-4 text-emerald-400" /> Ombud Ruling & Final Order
        </h4>
        <ul class="space-y-2">
          <li v-for="(o, idx) in finalOrder" :key="idx" class="text-sm text-zinc-300 flex items-start gap-2">
            <span class="text-emerald-400 font-bold shrink-0">✓</span>
            <span>{{ o }}</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Pro Content Section / Locked Blurs -->
    <div class="relative">
      <div :class="[!isPro ? 'filter blur-sm select-none opacity-40 pointer-events-none' : '']" class="space-y-6">
        <!-- Findings & Reasoning -->
        <div v-if="findings" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
          <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
            <Scale class="w-4 h-4 text-emerald-400" /> Ombud Findings & Legal Reasoning
          </h4>
          <p class="text-sm text-zinc-300 leading-relaxed whitespace-pre-line">
            {{ findings }}
          </p>
        </div>

        <!-- Statutory & Regulatory Sections Cited -->
        <div v-if="provisions.length > 0" class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3">
          <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
            <BookOpen class="w-4 h-4 text-indigo-400" /> Statutory & Regulatory Provisions Cited
          </h4>
          <div class="flex flex-wrap gap-2">
            <span v-for="(p, idx) in provisions" :key="idx" class="px-2.5 py-1 rounded-lg text-xs font-mono bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
              {{ p }}
            </span>
          </div>
        </div>
      </div>

      <!-- Locked Overlay for Standard Subscribers -->
      <div v-if="!isPro" class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 backdrop-blur-md rounded-2xl p-6 text-center z-20 border border-emerald-500/20">
        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-3 shadow-lg shadow-emerald-500/10">
          <Lock class="w-6 h-6" />
        </div>
        <h3 class="text-base font-bold text-white mb-1">Subscriber Pro Feature</h3>
        <p class="text-xs text-zinc-400 max-w-md mb-4">
          Complete ombud legal reasoning, Policyholder Protection Rules application, and full determination orders require an active Pro subscription.
        </p>
        <a href="/services" class="px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-emerald-500 to-emerald-600 text-black hover:brightness-110 transition-all flex items-center gap-1.5 shadow-lg shadow-emerald-500/20">
          <Sparkles class="w-3.5 h-3.5" /> Upgrade to Pro Access <ArrowRight class="w-3.5 h-3.5" />
        </a>
      </div>
    </div>

    <!-- Footer Controls -->
    <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-white/5 text-xs text-zinc-400">
      <div class="flex items-center gap-2">
        <a v-if="sourceUrl" :href="sourceUrl" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white/5 border border-white/5">
          <ExternalLink class="w-3.5 h-3.5" /> Original Determination Source
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
