<script setup lang="ts">
import { computed } from 'vue';
import {
  Scale,
  Calendar,
  X,
  ExternalLink,
  CheckCircle2,
  AlertTriangle,
  Bookmark,
  Compass,
  FileText,
  Clock,
  MapPin,
  Users,
  BookOpen
} from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import Skeleton from 'primevue/skeleton';

const props = defineProps<{
  show: boolean;
  loading: boolean;
  recordDetail: any;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

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

const dataObj = computed(() => {
  if (!props.recordDetail) return {};
  return props.recordDetail.data || props.recordDetail;
});

const title = computed(() => dataObj.value.title || dataObj.value.name || 'Legal Record Dossier');
const caseNumber = computed(() => dataObj.value.case_number || dataObj.value.award_number || null);
const court = computed(() => formatCourtName(dataObj.value.court) || dataObj.value.court || 'Court Authority');
const courtLocation = computed(() => dataObj.value.court_location || 'National Jurisdiction');
const judgmentDate = computed(() => dataObj.value.judgment_date || dataObj.value.award_date || dataObj.value.document_date || props.recordDetail?.document_date || 'N/A');
const hearingDate = computed(() => dataObj.value.hearing_date || dataObj.value.hearing_start || 'N/A');
const durationDays = computed(() => dataObj.value.duration_days ?? null);
const applicant = computed(() => dataObj.value.applicant || dataObj.value.employee || 'N/A');
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
const sourceUrl = computed(() => dataObj.value.source_url || props.recordDetail?.source_url || null);
</script>

<template>
  <Modal :show="show" @close="emit('close')" maxWidth="4xl">
    <div class="relative bg-zinc-950 text-white overflow-hidden max-h-[92vh] flex flex-col rounded-3xl border border-white/10 shadow-2xl">
      <!-- Top Header -->
      <div class="flex items-start justify-between gap-4 p-6 sm:p-8 border-b border-white/10 bg-zinc-900/60 backdrop-blur-md sticky top-0 z-10">
        <div class="space-y-2 flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span v-if="caseNumber"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-primary/10 text-primary border border-primary/20">
              {{ caseNumber }}
            </span>
            <span
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/5 text-zinc-300 border border-white/10">
              {{ court }}
            </span>
            <span v-if="reportable"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              Reportable Precedent
            </span>
          </div>
          <h2 class="text-base sm:text-lg font-black text-white leading-snug break-words">
            {{ title }}
          </h2>
        </div>
        <button @click="emit('close')"
          class="p-2 text-zinc-400 hover:text-white rounded-xl bg-white/5 hover:bg-white/10 transition-all shrink-0 cursor-pointer"
          title="Close Dossier">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Scrollable content area -->
      <div class="flex-1 overflow-y-auto p-6 sm:p-8 custom-scrollbar space-y-6">
        <!-- Loading State -->
        <div v-if="loading" class="space-y-4">
          <Skeleton width="100%" height="4rem" class="bg-zinc-800/60 rounded-2xl" />
          <Skeleton width="100%" height="6rem" class="bg-zinc-800/60 rounded-2xl" />
          <Skeleton width="100%" height="12rem" class="bg-zinc-800/60 rounded-2xl" />
        </div>

        <!-- Loaded Content -->
        <div v-else-if="recordDetail" class="space-y-6">
          <!-- Case Metadata 4-Grid -->
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
              <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Adjudication Duration</span>
              <span class="font-bold text-primary mt-1 block">{{ durationDays !== null ? durationDays + ' days' : 'N/A' }}</span>
            </div>
            <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5">
              <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block">Location</span>
              <span class="font-bold text-white mt-1 block truncate">{{ courtLocation }}</span>
            </div>
          </div>

          <!-- Judicial Bench & Litigants -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
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
              <p class="font-medium text-white truncate"><strong>Applicant / Employee:</strong> {{ applicant }}</p>
              <p class="font-medium text-zinc-300 truncate"><strong>Respondent / Employer:</strong> {{ respondent }}</p>
            </div>
          </div>

          <!-- Core Legal Intelligence Sections -->
          <div class="space-y-4">
            <!-- Ratio Decidendi -->
            <div v-if="ratioDecidendi" class="bg-amber-500/[0.04] border border-amber-500/20 p-5 rounded-2xl space-y-2">
              <div class="flex items-center gap-2">
                <Bookmark class="w-4 h-4 text-amber-400" />
                <span class="text-xs font-black uppercase tracking-wider text-amber-400">Ratio Decidendi (Binding Legal Principle)</span>
              </div>
              <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line">
                {{ ratioDecidendi }}
              </p>
            </div>

            <!-- Executive Summary -->
            <div v-if="summary" class="bg-zinc-900/60 border border-white/5 p-5 rounded-2xl space-y-2">
              <div class="flex items-center gap-2">
                <FileText class="w-4 h-4 text-primary" />
                <span class="text-xs font-black uppercase tracking-wider text-primary">Executive Summary</span>
              </div>
              <p class="text-xs text-zinc-300 leading-relaxed whitespace-pre-line">
                {{ summary }}
              </p>
            </div>

            <!-- Reason for Dismissal / Subjects (for CCMA/Labour) -->
            <div v-if="dismissalReason && !ratioDecidendi" class="bg-rose-500/[0.04] border border-rose-500/20 p-5 rounded-2xl space-y-2">
              <div class="flex items-center gap-2">
                <AlertTriangle class="w-4 h-4 text-rose-400" />
                <span class="text-xs font-black uppercase tracking-wider text-rose-400">Dispute Classification &amp; Ground</span>
              </div>
              <p class="text-xs text-zinc-200 leading-relaxed">
                {{ dismissalReason }}
              </p>
            </div>

            <!-- Obiter Dicta -->
            <div v-if="obiterDicta" class="bg-purple-500/[0.04] border border-purple-500/20 p-5 rounded-2xl space-y-2">
              <div class="flex items-center gap-2">
                <Compass class="w-4 h-4 text-purple-400" />
                <span class="text-xs font-black uppercase tracking-wider text-purple-400">Obiter Dicta (Judicial Observations)</span>
              </div>
              <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line">
                {{ obiterDicta }}
              </p>
            </div>

            <!-- Order / Relief -->
            <div v-if="order" class="bg-emerald-500/[0.04] border border-emerald-500/20 p-5 rounded-2xl space-y-2">
              <div class="flex items-center gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Formal Judicial Order &amp; Relief Granted</span>
              </div>
              <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line font-mono text-[11px]">
                {{ order }}
              </p>
            </div>
          </div>

          <!-- Precedents Cited Table -->
          <div v-if="precedentsCited && precedentsCited.length" class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2">
                <BookOpen class="w-4 h-4 text-primary" />
                Cited Legal Precedents &amp; Authorities ({{ precedentsCount }})
              </span>
            </div>

            <div class="max-h-60 overflow-y-auto custom-scrollbar">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-white/10 text-zinc-400 font-bold uppercase text-[9px]">
                    <th class="py-2 px-2">Authority / Citation</th>
                    <th class="py-2 px-2">Treatment</th>
                    <th class="py-2 px-2 text-right">Reference</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-zinc-300">
                  <tr v-for="p in precedentsCited" :key="p.case_name_citation || p.citation" class="hover:bg-white/[0.02]">
                    <td class="py-2 px-2 font-medium text-white">{{ p.case_name_citation || p.citation }}</td>
                    <td class="py-2 px-2">
                      <span class="px-2 py-0.5 rounded text-[9px] font-bold"
                        :class="p.treatment === 'Applied/Followed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-primary/10 text-primary border border-primary/20'">
                        {{ p.treatment || 'Referred' }}
                      </span>
                    </td>
                    <td class="py-2 px-2 text-right">
                      <a v-if="p.url" :href="p.url" target="_blank" rel="noopener noreferrer"
                        class="text-primary hover:underline inline-flex items-center gap-1 text-[10px]">
                        LawCite <ExternalLink class="w-3 h-3" />
                      </a>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Actions Footer -->
      <div class="p-4 sm:px-8 border-t border-white/10 bg-zinc-900/60 flex items-center justify-between">
        <div>
          <a v-if="sourceUrl" :href="sourceUrl" target="_blank" rel="noopener noreferrer"
            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/15 text-white flex items-center gap-2 transition-all">
            <span>Open Source Record</span>
            <ExternalLink class="w-3.5 h-3.5" />
          </a>
        </div>
        <button @click="emit('close')" class="btn btn-primary px-5 py-2.5 rounded-xl text-xs font-black cursor-pointer">
          Close Dossier
        </button>
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
