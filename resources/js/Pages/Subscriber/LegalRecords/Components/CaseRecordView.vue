<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import {
  Scale,
  Calendar,
  ExternalLink,
  CheckCircle2,
  AlertTriangle,
  Bookmark,
  Compass,
  FileText,
  Clock,
  MapPin,
  Users,
  BookOpen,
  Lock,
  ArrowRight,
  Sparkles,
  AlertCircle
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

const requiresHumanReview = computed(() => Boolean(
  props.recordDetail?.requires_human_review ||
  props.recordDetail?.data?.requires_human_review ||
  dataObj.value?.requires_human_review
));

const reviewReason = computed(() => 
  props.recordDetail?.review_reason ||
  props.recordDetail?.data?.review_reason ||
  dataObj.value?.review_reason ||
  null
);

const title = computed(() => dataObj.value.title || dataObj.value.name || 'Legal Record Dossier');
const caseNumber = computed(() => dataObj.value.case_number || dataObj.value.award_number || null);
const court = computed(() => dataObj.value.court || 'Court Authority');
const courtLocation = computed(() => dataObj.value.court_location || 'National Jurisdiction');
const judgmentDate = computed(() => dataObj.value.judgment_date || dataObj.value.award_date || dataObj.value.document_date || props.recordDetail?.document_date || 'N/A');
const hearingDate = computed(() => dataObj.value.hearing_date || dataObj.value.hearing_start || 'N/A');
const durationDays = computed(() => dataObj.value.duration_days ?? null);
const applicant = computed(() => dataObj.value.applicant || 'N/A');
const respondent = computed(() => dataObj.value.respondent || dataObj.value.employer || 'N/A');
const judges = computed(() => {
  const j = dataObj.value.judges;
  if (Array.isArray(j)) return j;
  if (typeof j === 'string' && j) return [j];
  return [];
});
const reportable = computed(() => Boolean(dataObj.value.reportable));
const ratioDecidendi = computed(() => dataObj.value.ratio_decidendi || null);
const summary = computed(() => dataObj.value.summary || dataObj.value.ai_summary || null);
const obiterDicta = computed(() => dataObj.value.obiter_dicta || null);
const order = computed(() => dataObj.value.order || dataObj.value.holding || dataObj.value.result || null);
const dismissalReason = computed(() => dataObj.value.reason_for_dismissal || dataObj.value.subjects || null);
const precedentsCited = computed(() => {
  const p = dataObj.value.precedents_cited;
  return Array.isArray(p) ? p : [];
});
const precedentsCount = computed(() => dataObj.value.precedents_count ?? precedentsCited.value.length);
</script>

<template>
  <div class="space-y-6">
    <!-- Human Review Alert Banner (if flagged) -->
    <div v-if="requiresHumanReview"
      class="bg-amber-500/10 border border-amber-500/30 p-4 sm:p-5 rounded-2xl flex items-start sm:items-center gap-4">
      <div class="w-9 h-9 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
        <AlertCircle class="w-5 h-5" />
      </div>
      <div class="space-y-0.5 flex-1 min-w-0">
        <div class="flex items-center gap-2 text-amber-400 font-black uppercase text-xs tracking-wider">
          Flagged for Human Review
        </div>
        <p v-if="reviewReason" class="text-xs text-zinc-300">
          <strong>Review Note:</strong> {{ reviewReason }}
        </p>
        <p v-else class="text-xs text-zinc-400">
          This record has been marked for quality review and manual verification by an administrator.
        </p>
      </div>
    </div>

    <!-- Standard Tier Upgrade Notice Banner (if not Pro) -->
    <div v-if="!isPro"
      class="bg-gradient-to-r from-amber-500/10 via-primary/10 to-transparent border border-primary/30 p-4 sm:p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="space-y-1">
        <div class="flex items-center gap-2 text-primary font-black uppercase text-xs tracking-wider">
          <Sparkles class="w-4 h-4" /> Standard Preview: Case Intelligence Locked
        </div>
        <p class="text-xs text-zinc-300">
          Subscribe now to unlock advanced case intelligence like the Ratio decidendi, Obiter Dicta and more.
        </p>
      </div>
      <a href="/#pricing"
        class="btn btn-primary px-4 py-2 text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-primary/20 flex items-center gap-1.5 shrink-0">
        <span>Unlock Now</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </a>
    </div>

    <!-- Case Metadata 4-Grid -->
    <div class="relative rounded-2xl overflow-hidden">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5">
          <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Judgment Date</span>
          <span class="font-bold text-white mt-1 block">{{ judgmentDate }}</span>
        </div>
        <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5">
          <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Hearing Date</span>
          <span class="font-bold text-white mt-1 block">{{ hearingDate }}</span>
        </div>
        <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5">
          <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Adjudication
            Duration</span>
          <span class="font-bold text-primary mt-1 block">{{ durationDays !== null ? durationDays + ' days' :
            'N/A' }}</span>
        </div>
        <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5">
          <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Location</span>
          <span class="font-bold text-white mt-1 block truncate">{{ courtLocation }}</span>
        </div>
      </div>
    </div>

    <!-- Judicial Bench & Litigants -->
    <div class="relative rounded-2xl overflow-hidden">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs"
        :class="{ 'filter blur-[2px] select-none opacity-60 pointer-events-none': !isPro }">
        <div class="bg-zinc-900/40 p-4 rounded-2xl border border-white/5 space-y-1">
          <span class="text-[10px] text-primary font-bold uppercase tracking-wider flex items-center gap-1.5">
            <Users class="w-3.5 h-3.5" /> Judicial Bench
          </span>
          <p class="font-medium text-white">
            {{ judges.length ? judges.join(', ') : 'Superior Court Appellate Bench / CCMA Commissioner' }}
          </p>
        </div>
        <div class="bg-zinc-900/40 p-4 rounded-2xl border border-white/5 space-y-1">
          <span class="text-[10px] text-primary font-bold uppercase tracking-wider flex items-center gap-1.5">
            <Scale class="w-3.5 h-3.5" /> Litigant Parties
          </span>
          <p class="font-medium text-white truncate"><strong>Applicant:</strong> {{ applicant }}</p>
          <p class="font-medium text-zinc-300 truncate"><strong>Respondent:</strong> {{ respondent }}
          </p>
        </div>
      </div>
    </div>

    <!-- Core Legal Intelligence Sections -->
    <div class="space-y-4">
      <!-- 1. Executive Summary (ALWAYS FULLY VISIBLE & CLEAN) -->
      <div v-if="summary" class="bg-zinc-900/60 border border-white/10 p-5 sm:p-6 rounded-2xl space-y-2">
        <div class="flex items-center gap-2">
          <FileText class="w-4 h-4 text-primary" />
          <span class="text-xs font-black uppercase tracking-wider text-primary">Executive Summary &amp;
            Overview</span>
        </div>
        <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line font-medium">
          {{ summary }}
        </p>
      </div>

      <!-- 2. Ratio Decidendi (BLURRED + CTA IF STANDARD TIER) -->
      <div v-if="ratioDecidendi || !isPro"
        class="relative rounded-2xl overflow-hidden border border-amber-500/20 bg-amber-500/[0.04]">
        <div class="p-6 space-y-2"
          :class="{ 'filter blur-[4px] select-none opacity-40 pointer-events-none space-y-12': !isPro }">
          <div class="flex items-center gap-2">
            <Bookmark class="w-4 h-4 text-amber-400" />
            <span class="text-xs font-black uppercase tracking-wider text-amber-400">Ratio Decidendi (Binding
              Legal Principle)</span>
          </div>
          <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line">
            {{ ratioDecidendi || 'The binding legal principles governing this matter are reserved for Pro subscribers. Upgrade to Pro Case Law to inspect unredacted ratios and judicial findings.' }}
          </p>
        </div>

        <!-- Locked Overlay for Non-Subscribers -->
        <div v-if="!isPro"
          class="absolute inset-0 bg-black/60 backdrop-blur-[2px] flex flex-col items-center justify-center p-6 text-center space-y-2">
          <div
            class="w-9 h-9 rounded-full bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
            <Lock class="w-4 h-4" />
          </div>
          <h4 class="text-xs font-black uppercase tracking-wider text-white">Ratio Decidendi Intelligence Locked
          </h4>
          <p class="text-[11px] text-zinc-400 max-w-md">
            Extracted binding legal principles and headnotes are exclusive to Pro Analytics and Pro Case Law
            subscribers.
          </p>
          <a href="/#pricing"
            class="btn btn-primary px-4 py-2 text-[11px] font-black uppercase tracking-wider rounded-xl shadow-lg shadow-primary/20 flex items-center gap-1.5 mt-2">
            <span>Unlock Pro Case Law</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </a>
        </div>
      </div>

      <!-- Reason for Dismissal / Subjects (for CCMA/Labour) -->
      <div v-if="dismissalReason && !ratioDecidendi"
        class="bg-rose-500/[0.04] border border-rose-500/20 p-5 rounded-2xl space-y-2">
        <div class="flex items-center gap-2">
          <AlertTriangle class="w-4 h-4 text-rose-400" />
          <span class="text-xs font-black uppercase tracking-wider text-rose-400">Dispute Classification &amp;
            Ground</span>
        </div>
        <p class="text-xs text-zinc-200 leading-relaxed">
          {{ dismissalReason }}
        </p>
      </div>

      <!-- 3. Obiter Dicta -->
      <div v-if="obiterDicta || (!isPro && isPro !== null)"
        class="relative rounded-2xl overflow-hidden border border-purple-500/20 bg-purple-500/[0.04]">
        <div class="p-6 space-y-2"
          :class="{ 'filter blur-[4px] select-none opacity-40 pointer-events-none space-y-12': !isPro }">
          <div class="flex items-center gap-2">
            <Compass class="w-4 h-4 text-purple-400" />
            <span class="text-xs font-black uppercase tracking-wider text-purple-400">Obiter Dicta (Judicial
              Observations)</span>
          </div>
          <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line">
            {{ obiterDicta || 'Judicial observations, obiter commentary, and procedural notes are reserved for Pro Subscribers.' }}
          </p>
        </div>
        <div v-if="!isPro"
          class="absolute inset-0 bg-black/60 backdrop-blur-[2px] flex flex-col items-center justify-center p-4 text-center">
          <span class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5">
            <Lock class="w-3.5 h-3.5 text-purple-400" /> Obiter Dicta Locked
          </span>
        </div>
      </div>

      <!-- 4. Formal Judicial Order & Relief -->
      <div v-if="order || (!isPro && isPro !== null)"
        class="relative rounded-2xl overflow-hidden border border-emerald-500/20 bg-emerald-500/[0.04]">
        <div class="p-6 space-y-2"
          :class="{ 'filter blur-[4px] select-none opacity-40 pointer-events-none space-y-12': !isPro }">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-emerald-400" />
            <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Formal Judicial Order &amp;
              Relief Granted</span>
          </div>
          <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line font-mono text-[11px]">
            {{ order || 'Formal court orders, costs determinations, and relief granted are locked. Upgrade to Pro to inspect complete orders.' }}
          </p>
        </div>
        <div v-if="!isPro"
          class="absolute inset-0 bg-black/60 backdrop-blur-[2px] flex flex-col items-center justify-center p-4 text-center">
          <span class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5">
            <Lock class="w-3.5 h-3.5 text-emerald-400" /> Formal Court Order Locked
          </span>
        </div>
      </div>
    </div>

    <!-- Footnotes & Precedents Cited Table -->
    <div class="relative rounded-2xl overflow-hidden bg-zinc-900/40 border border-white/5 p-5 space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2">
          <BookOpen class="w-4 h-4 text-primary" />
          Footnotes &amp; Cited Precedents ({{ isPro ? precedentsCount : (precedentsCount || 'Pro') }})
        </span>
      </div>

      <div v-if="isPro && precedentsCited && precedentsCited.length"
        class="max-h-80 overflow-y-auto custom-scrollbar">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-white/10 text-zinc-400 font-bold uppercase text-[9px]">
              <th class="py-2 px-2">Authority / Reference</th>
              <th class="py-2 px-2">Treatment</th>
              <th class="py-2 px-2 text-right">Reference</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5 text-zinc-300">
            <tr v-for="(p, pIdx) in precedentsCited" :key="p.raw_citation || p.case_name_citation || pIdx"
              class="hover:bg-white/[0.02]">
              <td class="py-2.5 px-2">
                <div class="font-medium text-white text-xs">
                  {{ p.case_name || p.raw_citation || p.case_name_citation || 'Unspecified Legal Reference' }}
                </div>
                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                  <span v-if="p.neutral_citation"
                    class="px-1.5 py-0.5 rounded bg-primary/10 text-primary border border-primary/20 font-mono text-[9px]">
                    {{ p.neutral_citation }}
                  </span>
                  <span v-if="p.case_number"
                    class="px-1.5 py-0.5 rounded bg-white/5 text-zinc-300 font-mono text-[9px]">
                    ({{ p.case_number }})
                  </span>
                  <span v-for="c in (p.commercial_citations || [])" :key="c"
                    class="px-1.5 py-0.5 rounded bg-zinc-800/80 text-zinc-400 font-mono text-[9px] border border-white/5">
                    {{ c }}
                  </span>
                  <span v-if="p.decision_date"
                    class="text-[9px] text-zinc-500 inline-flex items-center gap-1 font-mono">
                    <Calendar class="w-2.5 h-2.5 text-zinc-400" />
                    {{ p.decision_date }}
                  </span>
                </div>
                <p v-if="p.reasoning && p.reasoning !== 'null' && p.reasoning !== 'None'"
                  class="text-[10px] text-zinc-400 mt-1.5 italic line-clamp-2">
                  {{ p.reasoning }}
                </p>
              </td>
              <td class="py-2.5 px-2 align-top">
                <span class="px-2 py-0.5 rounded text-[9px] font-bold inline-block mt-0.5"
                  :class="p.treatment === 'Applied/Followed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (p.treatment === 'Distinguished/Overruled' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-primary/10 text-primary border border-primary/20')">
                  {{ p.treatment && p.treatment !== 'null' ? p.treatment : 'Referred' }}
                </span>
              </td>
              <td class="py-2.5 px-2 text-right align-top">
                <a v-if="p.url && p.url !== 'null'" :href="p.url" target="_blank" rel="noopener noreferrer"
                  class="text-primary hover:underline inline-flex items-center gap-1 text-[10px] font-medium mt-0.5">
                  LawCite
                  <ExternalLink class="w-3 h-3" />
                </a>
                <span v-else class="text-zinc-600 text-[10px]">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Precedents Locked State for Standard Tier -->
      <div v-else-if="!isPro"
        class="py-8 flex flex-col items-center justify-center text-center space-y-3 bg-black/40 rounded-xl border border-white/5 p-6">
        <Lock class="w-6 h-6 text-primary" />
        <div class="space-y-1">
          <h5 class="text-xs font-bold uppercase tracking-wider text-white">Footnotes &amp; Precedent Network Locked</h5>
          <p class="text-[11px] text-zinc-400 max-w-sm">
            Trace footnotes, cited authorities, judicial treatments (Applied, Distinguished, Overruled), and direct LawCite
            references with a Pro subscription.
          </p>
        </div>
        <a href="/#pricing"
          class="btn btn-primary px-4 py-2 text-[10px] font-black uppercase tracking-wider rounded-xl shadow-md shadow-primary/20 flex items-center gap-1">
          <span>Unlock Footnotes &amp; Precedents</span>
          <ArrowRight class="w-3 h-3" />
        </a>
      </div>
    </div>

    <!-- AI Intelligence Disclaimer & Quality Error Reporting Box -->
    <div class="bg-gradient-to-br from-zinc-900/80 via-zinc-900/50 to-amber-950/20 border border-white/10 rounded-2xl p-5 sm:p-6 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5 flex-1 min-w-0">
          <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0 mt-0.5">
            <AlertTriangle class="w-4 h-4" />
          </div>
          <div class="space-y-1">
            <h4 class="font-black uppercase tracking-wider text-zinc-200 text-xs flex items-center gap-2">
              <span>AI Disclaimer &amp; Verification Notice</span>
            </h4>
            <p class="text-zinc-400 text-xs leading-relaxed">
              Dossier headnotes, summaries, and extracted legal principles are generated with AI assistance. AI makes mistakes and automated extracts may contain errors or inaccuracies. Please verify against the original court documents before relying on this information.
            </p>
          </div>
        </div>

        <!-- Action / Reporting Area -->
        <div class="shrink-0 flex items-center gap-2 self-stretch sm:self-center justify-end">
          <!-- State 1: Reported Success -->
          <div v-if="reportSuccess || requiresHumanReview"
            class="px-3.5 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[11px] font-bold flex items-center gap-1.5 shadow-sm">
            <CheckCircle2 class="w-3.5 h-3.5 shrink-0" />
            <span>{{ reportSuccess ? 'Report Submitted for Quality Review' : 'Marked for Quality Review' }}</span>
          </div>

          <!-- State 2: One-Click Confirmation State -->
          <div v-else-if="isConfirmingReport"
            class="bg-black/80 border border-amber-500/40 p-2 rounded-xl flex items-center gap-2 animate-in fade-in zoom-in-95 duration-150">
            <span class="text-[10px] text-zinc-300 font-bold px-1.5 hidden sm:inline">Confirm Report?</span>
            <button
              @click="confirmReport"
              :disabled="isReporting"
              type="button"
              class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white font-black text-[10px] uppercase tracking-wider rounded-lg flex items-center gap-1 transition-all shadow cursor-pointer disabled:opacity-50"
            >
              <AlertCircle class="w-3 h-3" />
              <span>{{ isReporting ? 'Reporting...' : 'Yes, Report Errors' }}</span>
            </button>
            <button
              @click="cancelReport"
              type="button"
              class="px-2.5 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white text-[10px] font-bold rounded-lg transition-all cursor-pointer"
            >
              Cancel
            </button>
          </div>

          <!-- State 3: Initial Report Button -->
          <button
            v-else
            @click="triggerReport"
            type="button"
            class="px-3.5 py-2 rounded-xl text-[11px] font-black uppercase tracking-wider bg-white/5 hover:bg-amber-500/20 text-zinc-300 hover:text-amber-300 border border-white/10 hover:border-amber-500/30 transition-all flex items-center gap-1.5 cursor-pointer shadow-sm"
            title="Report this record if it contains AI parsing errors or incorrect data"
          >
            <AlertCircle class="w-3.5 h-3.5 text-amber-400" />
            <span>Report Record Errors</span>
          </button>
        </div>
      </div>

      <!-- Error feedback if report failed -->
      <div v-if="reportErrorMsg" class="text-rose-400 text-[11px] font-bold">
        {{ reportErrorMsg }}
      </div>
    </div>
  </div>
</template>
