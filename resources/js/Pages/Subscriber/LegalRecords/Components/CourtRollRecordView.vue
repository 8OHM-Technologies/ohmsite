<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  Calendar,
  Clock,
  MapPin,
  Sparkles,
  Lock,
  ArrowRight,
  Search,
  FileText,
  Users,
  Building,
  List,
  ExternalLink,
  ChevronRight,
  CheckCircle2
} from 'lucide-vue-next';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps<{
  recordDetail: any;
  isPro: boolean;
}>();

const dataObj = computed(() => {
  if (!props.recordDetail) return {};
  return props.recordDetail.data || props.recordDetail;
});

const title = computed(() => dataObj.value.title || 'Court Hearing Roll');
const court = computed(() => dataObj.value.court || 'Superior Court Jurisdiction');
const rollDate = computed(() => dataObj.value.hearing_date || dataObj.value.document_date || dataObj.value.judgment_date || 'N/A');
const rollNumber = computed(() => dataObj.value.case_number || dataObj.value.citation || 'Roll Schedule');
const judges = computed(() => {
  const j = dataObj.value.judges;
  if (Array.isArray(j) && j.length > 0) return j.join(', ');
  if (typeof j === 'string' && j) return j;
  return dataObj.value.presiding_judge || 'Allocated Judicial Bench';
});
const summary = computed(() => dataObj.value.summary || null);
const fullText = computed(() => dataObj.value.full_text || dataObj.value.content || null);
const sourceUrl = computed(() => dataObj.value.source_url || props.recordDetail?.source_url || null);

// Table / Text View Switcher
const rollViewMode = ref<'table' | 'text'>('table');
const rollSearch = ref('');

export interface RollItem {
  id: string | number;
  item_no?: string | number;
  case_number?: string;
  parties?: string;
  nature?: string;
  courtroom?: string;
  judge?: string;
  status?: string;
}

// Extract or generate structured roll items
const rollEntries = computed<RollItem[]>(() => {
  const entries = dataObj.value.roll_entries;
  if (Array.isArray(entries) && entries.length > 0) {
    return entries.map((e: any, idx: number) => ({
      id: e.id || idx + 1,
      item_no: e.item_no || e.item_number || e.roll_no || idx + 1,
      case_number: e.case_number || e.case_no || e.citation || `Matter #${idx + 1}`,
      parties: e.parties || e.matter || e.title || e.applicant_respondent || 'Matter allocated on roll',
      nature: e.nature || e.hearing_type || e.application_type || 'Motion / Trial',
      courtroom: e.courtroom || e.court_room || e.slot || 'Court 1A',
      judge: e.judge || e.presiding_judge || judges.value,
      status: e.status || e.allocation_status || 'Enrolled'
    }));
  }

  // If no structured entries but full text exists, try to parse lines or create displayable rows
  const text = fullText.value || summary.value;
  if (text) {
    const lines = text.split('\n').map((l: string) => l.trim()).filter((l: string) => l.length > 5);
    if (lines.length > 0) {
      return lines.slice(0, 50).map((line: string, idx: number) => {
        // Try extracting case numbers like 1234/2024 or RC12/26 or CCT 12/26
        const caseMatch = line.match(/([A-Z0-9\/\-]{4,20})/);
        return {
          id: idx + 1,
          item_no: idx + 1,
          case_number: caseMatch ? caseMatch[0] : `Item #${idx + 1}`,
          parties: line,
          nature: 'Motion Court / Schedule',
          courtroom: 'Chambers',
          judge: judges.value,
          status: 'Enrolled'
        };
      });
    }
  }

  // Default placeholder entry if completely empty
  return [
    {
      id: 1,
      item_no: 1,
      case_number: rollNumber.value !== 'Roll Schedule' ? rollNumber.value : 'Roll Entry 1',
      parties: title.value,
      nature: 'Hearing Schedule',
      courtroom: 'Court Forum',
      judge: judges.value,
      status: 'Scheduled'
    }
  ];
});

const filteredRollEntries = computed(() => {
  if (!rollSearch.value.trim()) return rollEntries.value;
  const q = rollSearch.value.toLowerCase();
  return rollEntries.value.filter((item) =>
    String(item.case_number || '').toLowerCase().includes(q) ||
    String(item.parties || '').toLowerCase().includes(q) ||
    String(item.nature || '').toLowerCase().includes(q) ||
    String(item.judge || '').toLowerCase().includes(q) ||
    String(item.courtroom || '').toLowerCase().includes(q)
  );
});
</script>

<template>
  <div class="space-y-6">
    <!-- Standard Tier Upgrade Notice Banner (if not Pro) -->
    <div v-if="!isPro"
      class="bg-gradient-to-r from-amber-500/10 via-primary/10 to-transparent border border-primary/30 p-4 sm:p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="space-y-1">
        <div class="flex items-center gap-2 text-primary font-black uppercase text-xs tracking-wider">
          <Sparkles class="w-4 h-4" /> Standard Preview: Court Roll Details Limited
        </div>
        <p class="text-xs text-zinc-300">
          Subscribe now to view all enrolled matters, allocated times, and unredacted motion cause lists.
        </p>
      </div>
      <a href="/#pricing"
        class="btn btn-primary px-4 py-2 text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-primary/20 flex items-center gap-1.5 shrink-0">
        <span>Unlock Now</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </a>
    </div>

    <!-- Court Roll Metadata Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
      <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5 space-y-1">
        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block flex items-center gap-1">
          <Building class="w-3 h-3 text-primary" /> Court / Forum
        </span>
        <span class="font-bold text-white block truncate">{{ court }}</span>
      </div>

      <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5 space-y-1">
        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block flex items-center gap-1">
          <Calendar class="w-3 h-3 text-primary" /> Roll Hearing Date
        </span>
        <span class="font-bold text-white block">{{ rollDate }}</span>
      </div>

      <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5 space-y-1">
        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block flex items-center gap-1">
          <Users class="w-3 h-3 text-primary" /> Presiding Bench
        </span>
        <span class="font-bold text-zinc-200 block truncate">{{ judges }}</span>
      </div>

      <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5 space-y-1">
        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block flex items-center gap-1">
          <Clock class="w-3 h-3 text-primary" /> Enrolled Matters
        </span>
        <span class="font-bold text-primary block font-mono">
          {{ isPro ? rollEntries.length + ' Listed' : 'Preview Mode' }}
        </span>
      </div>
    </div>

    <!-- Summary / Remarks (if present) -->
    <div v-if="summary" class="bg-zinc-900/60 border border-white/10 p-5 rounded-2xl space-y-1.5">
      <span class="text-[10px] font-bold uppercase tracking-wider text-primary flex items-center gap-1.5">
        <FileText class="w-3.5 h-3.5" /> Notice &amp; Roll Directions
      </span>
      <p class="text-xs text-zinc-200 leading-relaxed font-medium">
        {{ summary }}
      </p>
    </div>

    <!-- Court Roll Schedule Table Container -->
    <div class="bg-zinc-900/40 border border-white/5 rounded-2xl overflow-hidden space-y-0">
      <!-- Table Controls Bar -->
      <div
        class="p-4 sm:px-6 border-b border-white/5 bg-zinc-900/70 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <!-- Search within Roll -->
        <div class="relative flex-1 max-w-md">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-zinc-500" />
          <input type="text" v-model="rollSearch" placeholder="Filter matters, case numbers, or parties..."
            class="w-full bg-black/50 border border-white/10 rounded-xl py-2 pl-9 pr-4 text-xs font-medium text-white focus:ring-1 focus:ring-primary/50 focus:border-primary/50 placeholder:text-zinc-500" />
        </div>

        <!-- View Switcher (Table vs Raw Text) -->
        <div class="flex items-center gap-2">
          <button @click="rollViewMode = 'table'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all cursor-pointer"
            :class="rollViewMode === 'table' ? 'bg-primary text-black font-black' : 'text-zinc-400 hover:text-white bg-black/40 border border-white/10'">
            <List class="w-3.5 h-3.5" />
            <span>Table Format</span>
          </button>
          <button v-if="fullText" @click="rollViewMode = 'text'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all cursor-pointer"
            :class="rollViewMode === 'text' ? 'bg-primary text-black font-black' : 'text-zinc-400 hover:text-white bg-black/40 border border-white/10'">
            <FileText class="w-3.5 h-3.5" />
            <span>Raw Schedule</span>
          </button>
        </div>
      </div>

      <!-- VIEW 1: TABLE FORMAT -->
      <div v-if="rollViewMode === 'table'" class="p-2 sm:p-4 overflow-x-auto relative">
        <DataTable :value="filteredRollEntries" dataKey="id" class="p-datatable-dark-custom text-xs"
          tableStyle="min-width: 50rem">
          <template #empty>
            <div class="py-12 text-center text-zinc-400 text-xs">
              No matching roll matters found.
            </div>
          </template>

          <Column field="item_no" header="#" style="width: 8%">
            <template #body="{ data }">
              <span class="font-mono font-bold text-zinc-400 text-[11px]">{{ data.item_no }}</span>
            </template>
          </Column>

          <Column field="case_number" header="Case Reference" style="width: 22%">
            <template #body="{ data }">
              <span
                class="font-mono font-bold text-primary bg-primary/10 border border-primary/20 px-2 py-0.5 rounded text-[11px] inline-block">
                {{ data.case_number }}
              </span>
            </template>
          </Column>

          <Column field="parties" header="Matter / Parties" style="width: 38%">
            <template #body="{ data }">
              <span class="font-medium text-white break-words line-clamp-2 leading-relaxed">
                {{ data.parties }}
              </span>
            </template>
          </Column>

          <Column field="nature" header="Hearing Nature" style="width: 18%">
            <template #body="{ data }">
              <span class="text-zinc-300 text-[11px]">
                {{ data.nature }}
              </span>
            </template>
          </Column>

          <Column field="courtroom" header="Court Room" style="width: 14%">
            <template #body="{ data }">
              <span class="px-2 py-0.5 bg-white/5 border border-white/10 text-zinc-300 rounded text-[10px] font-bold">
                {{ data.courtroom }}
              </span>
            </template>
          </Column>
        </DataTable>

        <!-- Standard Tier Masking / Upgrade Overlay -->
        <div v-if="!isPro"
          class="absolute inset-x-0 bottom-0 top-32 bg-gradient-to-t from-zinc-950 via-zinc-950/90 to-transparent flex flex-col items-center justify-end pb-8 p-6 text-center space-y-3">
          <div
            class="w-10 h-10 rounded-full bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
            <Lock class="w-5 h-5" />
          </div>
          <h4 class="text-sm font-black uppercase tracking-wider text-white">
            Complete Motion Roll Locked
          </h4>
          <p class="text-xs text-zinc-400 max-w-md">
            Upgrade to a Pro subscription to view all allocated cases, courtroom schedules, and counsel details.
          </p>
          <a href="/#pricing"
            class="btn btn-primary px-5 py-2.5 text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-primary/20 flex items-center gap-2 mt-2">
            <span>Unlock Full Court Roll</span>
            <ArrowRight class="w-4 h-4" />
          </a>
        </div>
      </div>

      <!-- VIEW 2: RAW TEXT SCHEDULE -->
      <div v-else
        class="p-6 font-mono text-xs text-zinc-300 bg-black/40 whitespace-pre-wrap leading-relaxed max-h-[500px] overflow-y-auto custom-scrollbar">
        {{ fullText }}
      </div>
    </div>
  </div>
</template>
