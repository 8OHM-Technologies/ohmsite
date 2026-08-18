<script setup lang="ts">
import { computed } from 'vue';
import {
  Scale,
  Calendar,
  X,
  ExternalLink,
  CheckCircle2,
  AlertTriangle,
  FileCheck
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

const COURT_LOCATIONS_MAP: Record<string, string> = {
  'ZACC': 'Johannesburg',
  'ZASCA': 'Bloemfontein',
  'ZAGPPHC': 'Pretoria',
  'ZAGPJHC': 'Johannesburg',
  'ZAWCHC': 'Cape Town',
  'ZAFSHC': 'Bloemfontein',
  'ZAKZNDHC': 'Durban',
  'ZAKZNHC': 'Pietermaritzburg',
  'ZAECGHC': 'Grahamstown',
  'ZAECPEHC': 'Port Elizabeth',
  'ZAECELHC': 'East London',
  'ZAECBHC': 'Bhisho',
  'ZALMPPHC': 'Polokwane',
  'ZANWHC': 'Mahikeng',
  'ZANCHC': 'Kimberley',
  'ZALC': 'Johannesburg',
  'ZALAC': 'Johannesburg',
  'ZACAC': 'Johannesburg',
  'ZAEQC': 'Johannesburg',
  'ZALCC': 'Johannesburg',
  'ZATC': 'Johannesburg',
  'ZAECC': 'Bloemfontein',
  'ZALCJHB': 'Johannesburg',
  'ZALCPE': 'Port Elizabeth',
  'ZALCCT': 'Cape Town',
  'ZALCD': 'Durban',
  'ZALMPTHC': 'Thohoyandou',
  'ZAMPMHC': 'Middelburg',
  'ZAMPMBHC': 'Mbombela',
  'ZAKZDHC': 'Durban',
  'ZAKZPHC': 'Pietermaritzburg',
  'ZAGPHC': 'Pretoria',
  'ZAKZHC': 'Pietermaritzburg',
  'ZAECHC': 'Grahamstown'
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

const getCourtLocation = (recordData: any): string => {
  if (!recordData) return '';
  if (recordData.court_location) return String(recordData.court_location);
  if (recordData.court) {
    const courtUpper = String(recordData.court).toUpperCase().trim();
    if (COURT_LOCATIONS_MAP[courtUpper]) {
      return COURT_LOCATIONS_MAP[courtUpper];
    }
  }
  return '';
};

const EXCLUDED_KEYS = new Set([
  'auth_ok',
  'content_loaded',
  'detail_url',
  'detail_title',
  'index_scraped_at',
  'preview_image_url',
  'details_scraped_at',
  'scraped_at',
  'source_url',
  'url',
  'worker_id',
  'case_id',
  'metadata'
]);

const SPECIAL_LAYOUT_KEYS = new Set([
  'result',
  'order',
  'holding',
  'reason_for_dismissal',
  'dismissal_reason',
  'reasons_for_dismissal',
  'summary',
  'ai_summary',
  'headnotes',
  'abstract',
  'full_text',
  'center_content',
  'text',
  'content',
  'raw_text',
  'judgment_text',
  'judgment',
  'title',
  'name',
  'heading'
]);

const getDocumentType = (record: any): 'case' | 'gazette' | 'journal' | 'court_roll' => {
  if (!record) return 'case';
  const url = (record.source_url || '').toLowerCase();
  const type = (record.record_type || '').toLowerCase();

  if (url.includes('/gaz/') || url.includes('gazette') || type.includes('gazette') || type.includes('gaz')) {
    return 'gazette';
  }
  if (url.includes('/journals/') || url.includes('journal') || type.includes('journal')) {
    return 'journal';
  }
  if (url.includes('/rolls/') || url.includes('/other/') || url.includes('courtroll') || url.includes('roll') || type.includes('roll')) {
    return 'court_roll';
  }
  return 'case';
};

const getDocumentBodyText = (recordData: any) => {
  if (!recordData) return '';
  return (
    recordData.full_text ||
    recordData.center_content ||
    recordData.text ||
    recordData.content ||
    recordData.raw_text ||
    recordData.judgment_text ||
    recordData.judgment ||
    ''
  );
};

const getDocumentSummary = (recordData: any) => {
  if (!recordData) return '';
  return recordData.ai_summary || recordData.summary || recordData.headnotes || recordData.abstract || '';
};

const getDocumentHolding = (recordData: any) => {
  if (!recordData) return '';
  return recordData.result || recordData.order || recordData.holding || '';
};

const getDocumentDismissal = (recordData: any) => {
  if (!recordData) return '';
  return recordData.reason_for_dismissal || recordData.dismissal_reason || recordData.reasons_for_dismissal || '';
};

const formatValue = (val: any) => {
  if (val === null || val === undefined) return '';
  if (typeof val === 'object') return JSON.stringify(val, null, 2);
  return String(val);
};

const getFilteredMetadata = (recordData: any) => {
  if (!recordData) return [];

  const courtForumValues: string[] = [];
  const courtForumKeysToExclude = new Set<string>();

  for (const key of Object.keys(recordData)) {
    const val = recordData[key];
    if (val === null || val === undefined || val === '') continue;
    const kNorm = key.toLowerCase().replace(/_/g, ' ').trim();
    if (
      kNorm === 'court' ||
      kNorm === 'forum' ||
      kNorm === 'court forum' ||
      kNorm === 'court/forum' ||
      kNorm === 'forum/court' ||
      kNorm === 'court / forum'
    ) {
      courtForumKeysToExclude.add(key);
      const valStr = formatCourtName(val);
      if (valStr && !courtForumValues.includes(valStr)) {
        courtForumValues.push(valStr);
      }
    }
  }

  const items: { label: string; value: any }[] = [];

  if (courtForumValues.length > 0) {
    items.push({
      label: 'Court/Forum',
      value: courtForumValues.join(' / '),
    });
  }

  for (const key of Object.keys(recordData)) {
    const val = recordData[key];
    if (val === null || val === undefined || val === '') continue;
    if (EXCLUDED_KEYS.has(key) || SPECIAL_LAYOUT_KEYS.has(key) || courtForumKeysToExclude.has(key)) {
      continue;
    }

    const label = key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    items.push({ label, value: val });
  }

  return items.sort((a, b) => a.label.toLowerCase().localeCompare(b.label.toLowerCase()));
};
</script>

<template>
  <Modal :show="show" @close="emit('close')" maxWidth="5xl">
    <div class="relative bg-zinc-950 text-white overflow-hidden max-h-[95vh] flex flex-col">
      <!-- Top Sticky Header -->
      <div
        class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-zinc-900/60 backdrop-blur-md sticky top-0 z-10">
        <div class="flex items-center gap-2">
          <Scale class="w-5 h-5 text-admin-modern" />
          <span class="text-xs font-black uppercase tracking-widest text-zinc-400">Legal Document Intelligence</span>
        </div>
        <button @click="emit('close')"
          class="p-1.5 bg-zinc-800/80 border border-white/10 text-zinc-400 hover:text-white hover:bg-zinc-700 rounded-lg transition-all"
          title="Close Document">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Scrollable content area -->
      <div class="flex-1 overflow-y-auto p-6 sm:p-8 custom-scrollbar">
        <!-- Loading State -->
        <div v-if="loading" class="space-y-6">
          <Skeleton width="60%" height="2rem" class="bg-zinc-800/60" />
          <Skeleton width="40%" height="1.2rem" class="bg-zinc-800/60" />
          <Skeleton width="100%" height="16rem" class="bg-zinc-800/60 rounded-xl" />
        </div>

        <!-- Document Detail Loaded -->
        <div v-else-if="recordDetail" class="space-y-6">

          <!-- 1. COURT ROLLS LAYOUT (Compact, schedule style) -->
          <div v-if="getDocumentType(recordDetail) === 'court_roll'" class="space-y-4">
            <div class="flex items-start gap-4 p-5 rounded-2xl bg-zinc-900/60 border border-white/5 shadow-inner">
              <div
                class="w-10 h-10 rounded-xl bg-zinc-800 flex items-center justify-center border border-white/10 text-admin-modern shrink-0">
                <Calendar class="w-5 h-5" />
              </div>
              <div class="space-y-2 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    class="px-2 py-0.5 bg-admin-modern/10 border border-admin-modern/30 text-admin-modern text-[9px] font-black uppercase tracking-widest rounded">Court
                    Roll</span>
                  <span v-if="recordDetail?.document_date"
                    class="text-zinc-500 text-[10px] font-bold uppercase tracking-wider">{{
                      recordDetail.document_date
                    }}</span>
                  <span v-if="getCourtLocation(recordDetail?.data)"
                    class="text-zinc-500 text-[10px] font-bold uppercase tracking-wider">&bull; {{
                      getCourtLocation(recordDetail.data) }}</span>
                </div>
                <h2 class="text-lg font-black uppercase tracking-tight text-white leading-snug">
                  {{ recordDetail?.data?.title || recordDetail?.data?.name || 'Court Roll Schedule' }}
                </h2>
                <p class="text-xs font-bold text-zinc-400">
                  <span class="text-zinc-600 uppercase tracking-widest text-[9px] mr-1">Forum:</span>
                  {{ formatCourtName(recordDetail?.data?.court) || recordDetail?.record_type }}
                </p>
              </div>
            </div>

            <!-- Compact Text Block -->
            <div v-if="getDocumentBodyText(recordDetail?.data)"
              class="p-6 rounded-2xl bg-black/60 border border-white/10 font-serif text-sm leading-relaxed text-zinc-300 whitespace-pre-line max-h-[350px] overflow-y-auto custom-scrollbar">
              {{ getDocumentBodyText(recordDetail.data) }}
            </div>

            <!-- Clean minimal detail list -->
            <div
              class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-zinc-900/30 p-4 rounded-xl border border-white/5">
              <div v-for="item in getFilteredMetadata(recordDetail?.data)" :key="item.label"
                class="flex items-center justify-between py-1 border-b border-white/5 last:border-0">
                <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">{{ item.label }}</span>
                <span class="font-mono text-zinc-300 font-bold truncate max-w-[200px]">{{ formatValue(item.value) }}</span>
              </div>
              <div v-if="recordDetail?.source_url"
                class="flex items-center justify-between py-1 col-span-1 sm:col-span-2 border-t border-white/5 mt-1 pt-2">
                <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Source Link</span>
                <a :href="recordDetail.source_url" target="_blank"
                  class="text-admin-modern hover:underline flex items-center gap-1 font-bold">
                  Go to Original
                  <ExternalLink class="w-3 h-3" />
                </a>
              </div>
            </div>
          </div>

          <!-- 2. GAZETTE & JOURNAL LAYOUT (Formatted, premium printed text document) -->
          <div v-else-if="['gazette', 'journal'].includes(getDocumentType(recordDetail))"
            class="max-w-3xl mx-auto space-y-8 py-2">

            <!-- Document Header Block -->
            <div class="border-b-2 border-white/10 pb-6 text-center space-y-4">
              <div class="flex items-center justify-center gap-2">
                <span
                  class="px-3 py-1 bg-white text-black text-[9px] font-black uppercase tracking-[0.2em] rounded-full">
                  {{ getDocumentType(recordDetail) === 'gazette' ? 'Official Gazette Notice' : 'Academic Law Journal' }}
                </span>
              </div>

              <h1
                class="font-serif text-3xl sm:text-4xl font-black text-white tracking-tight leading-snug max-w-2xl mx-auto">
                {{ recordDetail?.data?.title || recordDetail?.data?.name || 'Publication Document' }}
              </h1>

              <!-- Formal Sub-Header -->
              <div
                class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-[10px] font-black uppercase tracking-widest text-zinc-400">
                <span v-if="recordDetail?.data?.publisher || recordDetail?.data?.journal_name"
                  class="flex items-center gap-1">
                  <span class="text-zinc-600">Publisher:</span>
                  {{ recordDetail.data.publisher || recordDetail.data.journal_name }}
                </span>
                <span v-if="getCourtLocation(recordDetail?.data)" class="flex items-center gap-1">
                  <span class="text-zinc-600">Location:</span>
                  {{ getCourtLocation(recordDetail.data) }}
                </span>
                <span v-if="recordDetail?.data?.gazette_number || recordDetail?.data?.volume"
                  class="flex items-center gap-1">
                  <span class="text-zinc-600">Reference:</span>
                  <code>{{ recordDetail.data.gazette_number || recordDetail.data.volume }}</code>
                </span>
                <span v-if="recordDetail?.document_date" class="flex items-center gap-1">
                  <span class="text-zinc-600">Date:</span>
                  {{ recordDetail.document_date }}
                </span>
              </div>

              <!-- Abstract / Summary Callout in reader layout -->
              <div v-if="getDocumentSummary(recordDetail?.data)"
                class="text-left bg-zinc-900/40 border border-white/5 p-5 rounded-2xl max-w-2xl mx-auto">
                <p class="text-[9px] font-black uppercase tracking-widest text-zinc-500 mb-2">Abstract / Executive
                  Summary
                </p>
                <p class="text-xs leading-relaxed text-zinc-300 font-medium whitespace-pre-line">{{
                  getDocumentSummary(recordDetail.data) }}</p>
              </div>
            </div>

            <!-- Main Reading Body (No table, premium formatted document look and feel) -->
            <div v-if="getDocumentBodyText(recordDetail?.data)"
              class="prose prose-invert font-serif max-w-none text-zinc-200 leading-relaxed text-base space-y-6 whitespace-pre-line px-2 sm:px-6">
              {{ getDocumentBodyText(recordDetail.data) }}
            </div>

            <!-- End of Document Footer / Details section (subtle, clean) -->
            <div class="border-t border-white/10 pt-6 mt-10">
              <div
                class="flex flex-col sm:flex-row items-center justify-between gap-4 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                <div class="flex flex-wrap gap-2">
                  <span v-for="item in getFilteredMetadata(recordDetail?.data)" :key="item.label"
                    class="px-2.5 py-1 bg-zinc-900/60 border border-white/5 rounded-lg text-zinc-400">
                    <strong>{{ item.label }}:</strong> {{ formatValue(item.value) }}
                  </span>
                </div>
                <a v-if="recordDetail?.source_url" :href="recordDetail.source_url" target="_blank"
                  class="text-admin-modern hover:underline flex items-center gap-1 shrink-0 font-black tracking-widest text-[9px]">
                  Go to Source
                  <ExternalLink class="w-3.5 h-3.5" />
                </a>
              </div>
            </div>
          </div>

          <!-- 3. CASE LAW / COURT JUDGMENTS LAYOUT (Default detailed case view) -->
          <div v-else class="space-y-6">
            <!-- Title & Metadata Header -->
            <div class="border-b border-white/10 pb-6">
              <h2 class="text-2xl font-black uppercase tracking-tight text-white mb-3">
                {{ recordDetail?.data?.title || recordDetail?.data?.name || 'Legal Record Details' }}
              </h2>
              <div class="flex flex-wrap items-center gap-4 text-xs font-bold text-zinc-300">
                <span v-if="recordDetail?.data?.court" class="flex items-center gap-1">
                  <span class="text-zinc-500 uppercase tracking-widest text-[9px]">Forum:</span> {{
                    formatCourtName(recordDetail.data.court) }}
                </span>
                <span v-if="getCourtLocation(recordDetail?.data)" class="flex items-center gap-1">
                  <span class="text-zinc-500 uppercase tracking-widest text-[9px]">Location:</span> {{
                    getCourtLocation(recordDetail.data) }}
                </span>
                <span v-if="recordDetail?.data?.case_number" class="flex items-center gap-1">
                  <span class="text-zinc-500 uppercase tracking-widest text-[9px]">Case #:</span> {{
                    recordDetail.data.case_number }}
                </span>
                <span v-if="recordDetail?.document_date" class="flex items-center gap-1">
                  <span class="text-zinc-500 uppercase tracking-widest text-[9px]">Date:</span> {{
                    recordDetail.document_date }}
                </span>
                <a v-if="recordDetail?.source_url" :href="recordDetail.source_url" target="_blank"
                  class="text-admin-modern hover:underline flex items-center gap-1">
                  Original Source
                  <ExternalLink class="w-3.5 h-3.5" />
                </a>
              </div>
            </div>

            <!-- Highlight Callout Boxes (Result / Holding / Order) -->
            <div v-if="getDocumentHolding(recordDetail.data)"
              class="p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-medium space-y-1">
              <div class="flex items-center gap-2 text-emerald-400 font-black uppercase text-[10px] tracking-widest">
                <CheckCircle2 class="w-4 h-4" /> Holding & Final Order
              </div>
              <p class="leading-relaxed">{{ getDocumentHolding(recordDetail.data) }}</p>
            </div>

            <div v-if="getDocumentDismissal(recordDetail.data)"
              class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-medium space-y-1">
              <div class="flex items-center gap-2 text-rose-400 font-black uppercase text-[10px] tracking-widest">
                <AlertTriangle class="w-4 h-4" /> Reason for Dismissal
              </div>
              <p class="leading-relaxed">{{ getDocumentDismissal(recordDetail.data) }}</p>
            </div>

            <div v-if="getDocumentSummary(recordDetail.data)"
              class="p-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-medium space-y-1">
              <div class="flex items-center gap-2 text-amber-400 font-black uppercase text-[10px] tracking-widest">
                <FileCheck class="w-4 h-4" /> Summary & Headnotes
              </div>
              <p class="whitespace-pre-line leading-relaxed">{{ getDocumentSummary(recordDetail.data) }}</p>
            </div>

            <!-- Full Judgment Text Viewer -->
            <div v-if="getDocumentBodyText(recordDetail.data)" class="space-y-2">
              <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Full Judgment Text</h3>
              <div
                class="p-6 rounded-2xl bg-black/60 border border-white/10 font-serif text-sm leading-relaxed text-zinc-200 whitespace-pre-line max-h-[500px] overflow-y-auto custom-scrollbar">
                {{ getDocumentBodyText(recordDetail.data) }}
              </div>
            </div>

            <!-- Metadata Attributes Grid (Alphabetically sorted, filtered) -->
            <div v-if="getFilteredMetadata(recordDetail.data).length > 0" class="space-y-2">
              <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Document Metadata Attributes</h3>
              <div
                class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs bg-black/40 p-5 rounded-2xl border border-white/10">
                <div v-for="item in getFilteredMetadata(recordDetail.data)" :key="item.label"
                  class="flex flex-col gap-0.5">
                  <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">{{ item.label }}</span>
                  <span class="font-mono text-zinc-200 break-all">{{ formatValue(item.value) }}</span>
                </div>
              </div>
            </div>
          </div>

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
