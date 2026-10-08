<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
  Scale,
  Calendar,
  X,
  ExternalLink,
  BookOpen,
  Lock,
  ArrowRight,
  Sparkles,
  AlertCircle,
  Check,
  ShieldAlert,
  Gavel,
  Award,
  Scroll
} from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import Skeleton from 'primevue/skeleton';
import CaseRecordView from './CaseRecordView.vue';
import JournalRecordView from './JournalRecordView.vue';
import CourtRollRecordView from './CourtRollRecordView.vue';
import GazetteRecordView from './GazetteRecordView.vue';
import RegulatoryRecordView from './RegulatoryRecordView.vue';
import OmbudRecordView from './OmbudRecordView.vue';
import TribunalRecordView from './TribunalRecordView.vue';

const props = defineProps<{
  show: boolean;
  loading: boolean;
  recordDetail: any;
  category?: string;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'review-updated', payload: { id: string; requires_human_review: boolean }): void;
}>();

const page = usePage();
const authUser = computed(() => page.props.auth?.user as any);
const isAdmin = computed(() => authUser.value?.role === 'admin');

const requiresHumanReview = ref(false);
const reviewSubmitting = ref(false);

watch(() => props.recordDetail, (newVal) => {
  if (newVal) {
    requiresHumanReview.value = Boolean(
      newVal.requires_human_review || 
      newVal.data?.requires_human_review || 
      false
    );
  } else {
    requiresHumanReview.value = false;
  }
}, { immediate: true });

const toggleHumanReview = async () => {
  if (!props.recordDetail || reviewSubmitting.value) return;
  const targetId = props.recordDetail.extracted_record_id || props.recordDetail.id;
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
      requires_human_review: response.data.requires_human_review
    });
  } catch (error) {
    console.error('Failed to toggle human review:', error);
  } finally {
    reviewSubmitting.value = false;
  }
};

const handleReviewUpdatedFromChild = (payload: { id: string; requires_human_review: boolean }) => {
  requiresHumanReview.value = payload.requires_human_review;
  if (props.recordDetail) {
    props.recordDetail.requires_human_review = payload.requires_human_review;
    if (props.recordDetail.data) {
      props.recordDetail.data.requires_human_review = payload.requires_human_review;
    }
  }
  emit('review-updated', payload);
};

const isPro = computed(() => {
  if (authUser.value?.role === 'admin') return true;
  if (authUser.value?.is_subscribed || authUser.value?.has_pro_access) return true;
  if (props.recordDetail?.is_pro === true) return true;
  if (props.recordDetail?.data?.is_pro === true) return true;
  return false;
});

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

const resolvedCategory = computed(() => {
  if (props.category) {
    if (props.category === 'gaz' || props.category === 'gazettes') return 'gazettes';
    return props.category;
  }
  const rt = String(dataObj.value.record_type || props.recordDetail?.record_type || '').toLowerCase();
  if (rt.includes('gaz')) return 'gazettes';
  if (rt.includes('journal')) return 'journals';
  if (rt.includes('roll')) return 'court_rolls';
  if (rt.includes('fsca') || rt.includes('pa_') || rt.includes('popia')) return 'regulatory';
  if (rt.includes('fst')) return 'tribunal';
  if (rt.includes('fais') || rt.includes('_nfo') || rt.startsWith('nfo')) return 'ombud';
  if (dataObj.value.category) {
    if (dataObj.value.category === 'gaz') return 'gazettes';
    return dataObj.value.category;
  }
  if (props.recordDetail?.category) {
    if (props.recordDetail.category === 'gaz') return 'gazettes';
    return props.recordDetail.category;
  }
  return 'cases';
});

const title = computed(() => dataObj.value.title || dataObj.value.name || 'Legal Record Dossier');
const caseNumber = computed(() => dataObj.value.case_number || dataObj.value.award_number || dataObj.value.citation || null);
const court = computed(() => formatCourtName(dataObj.value.court) || dataObj.value.court || dataObj.value.author || (resolvedCategory.value === 'regulatory' ? 'Regulatory Authority' : (resolvedCategory.value === 'tribunal' ? 'Financial Services Tribunal' : (resolvedCategory.value === 'ombud' ? 'Ombud Authority' : 'Court Authority'))));
const reportable = computed(() => Boolean(dataObj.value.reportable));
const sourceUrl = computed(() => dataObj.value.source_url || props.recordDetail?.source_url || null);
</script>

<template>
  <Modal :show="show" @close="emit('close')" maxWidth="4xl">
    <div
      class="relative bg-zinc-950 text-white overflow-hidden max-h-[92vh] flex flex-col rounded-3xl border border-white/10 shadow-2xl">
      <!-- Top Header -->
      <div
        class="flex items-start justify-between gap-4 p-6 sm:p-8 border-b border-white/10 bg-zinc-900/60 backdrop-blur-md sticky top-0 z-10">
        <div class="space-y-2 flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <!-- Icon Badge based on Category -->
            <span
              v-if="resolvedCategory === 'regulatory'"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
              <ShieldAlert class="w-3 h-3" /> Regulatory Enforcement
            </span>
            <span
              v-else-if="resolvedCategory === 'tribunal'"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20 flex items-center gap-1">
              <Gavel class="w-3 h-3" /> Tribunal Reconsideration
            </span>
            <span
              v-else-if="resolvedCategory === 'ombud'"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1">
              <Award class="w-3 h-3" /> Ombud Determination
            </span>
            <span
              v-else-if="resolvedCategory === 'gazettes'"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20 flex items-center gap-1">
              <Scroll class="w-3 h-3" /> Government Gazette
            </span>
            <span
              v-else-if="resolvedCategory === 'journals'"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20 flex items-center gap-1">
              <BookOpen class="w-3 h-3" /> Law Review / Journal
            </span>
            <span
              v-else-if="resolvedCategory === 'court_rolls' || resolvedCategory === 'other'"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20 flex items-center gap-1">
              <Calendar class="w-3 h-3" /> Court Hearing Roll
            </span>
            <span
              v-else
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20 flex items-center gap-1">
              <Scale class="w-3 h-3" /> Case Law Judgment
            </span>

            <span v-if="caseNumber"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-white/5 text-zinc-300 border border-white/10">
              {{ caseNumber }}
            </span>
            <span
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/5 text-zinc-300 border border-white/10">
              {{ court }}
            </span>
            <span v-if="reportable && resolvedCategory === 'cases'"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              Reportable Precedent
            </span>
            <span v-if="!isPro"
              class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
              <Lock class="w-3 h-3" /> Standard Preview
            </span>
          </div>
          <h2 class="text-base sm:text-lg font-black text-white leading-snug break-words">
            {{ title }}
          </h2>
        </div>
        <button @click="emit('close')"
          class="p-2 text-zinc-400 hover:text-white rounded-xl bg-white/5 hover:bg-white/10 transition-all shrink-0 cursor-pointer"
          title="Close">
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

        <!-- Loaded Content by Category -->
        <div v-else-if="recordDetail">
          <!-- 1. Regulatory Enforcement Sanction View -->
          <RegulatoryRecordView
            v-if="resolvedCategory === 'regulatory'"
            :record-detail="recordDetail"
            :is-pro="isPro"
            @review-updated="handleReviewUpdatedFromChild"
          />

          <!-- 2. Ombud Determination Dispute View -->
          <OmbudRecordView
            v-else-if="resolvedCategory === 'ombud'"
            :record-detail="recordDetail"
            :is-pro="isPro"
            @review-updated="handleReviewUpdatedFromChild"
          />

          <!-- 3. Financial Services Tribunal Decision View -->
          <TribunalRecordView
            v-else-if="resolvedCategory === 'tribunal'"
            :record-detail="recordDetail"
            :is-pro="isPro"
            @review-updated="handleReviewUpdatedFromChild"
          />

          <!-- 4. Gazettes Formatted Text Reader View -->
          <GazetteRecordView
            v-else-if="resolvedCategory === 'gazettes'"
            :record-detail="recordDetail"
            :is-pro="isPro"
          />

          <!-- 5. Journals Formatted Text Reader View -->
          <JournalRecordView
            v-else-if="resolvedCategory === 'journals'"
            :record-detail="recordDetail"
            :is-pro="isPro"
          />

          <!-- 6. Court Rolls Table Format Schedule View -->
          <CourtRollRecordView
            v-else-if="resolvedCategory === 'court_rolls' || resolvedCategory === 'other'"
            :record-detail="recordDetail"
            :is-pro="isPro"
          />

          <!-- 6. Standard Case Law Dossier View -->
          <CaseRecordView
            v-else
            :record-detail="recordDetail"
            :is-pro="isPro"
            @review-updated="handleReviewUpdatedFromChild"
          />
        </div>
      </div>

      <!-- Modal Actions Footer -->
      <div
        class="p-4 sm:px-8 border-t border-white/10 bg-zinc-900/60 flex flex-wrap items-center justify-between gap-3">
        <div>
          <a v-if="sourceUrl" :href="sourceUrl" target="_blank" rel="noopener noreferrer"
            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/15 text-white flex items-center gap-2 transition-all">
            <span>
              {{ resolvedCategory === 'gazettes' ? 'Open Source Gazette' : (resolvedCategory === 'journals' ? 'Open Source Publication' : (resolvedCategory === 'court_rolls' || resolvedCategory === 'other' ? 'Open Source Schedule' : 'Open Source Record')) }}
            </span>
            <ExternalLink class="w-3.5 h-3.5" />
          </a>
          <!-- <a v-if="!isPro" href="/#pricing"
            class="btn btn-primary px-4 py-2.5 rounded-xl text-xs font-black flex items-center gap-2 shadow-lg shadow-primary/20">
            <Sparkles class="w-3.5 h-3.5" />
            <span>Unlock Now</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </a> -->
        </div>

        <div class="flex items-center gap-3">
          <!-- Admin Mark for Human Review Button -->
          <button
            v-if="isAdmin"
            @click="toggleHumanReview"
            :disabled="reviewSubmitting"
            type="button"
            class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2 transition-all cursor-pointer shadow-md disabled:opacity-50"
            :class="requiresHumanReview ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 hover:bg-amber-500/30 shadow-amber-500/10' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white border border-white/10'"
          >
            <AlertCircle class="w-4 h-4" :class="requiresHumanReview ? 'text-amber-400' : 'text-zinc-400'" />
            <span>{{ requiresHumanReview ? 'Marked for Human Review' : 'Mark for Human Review' }}</span>
          </button>

          <button @click="emit('close')" class="btn btn-primary px-5 py-2.5 rounded-xl text-xs font-black cursor-pointer">
            {{ resolvedCategory === 'gazettes' ? 'Close Gazette' : (resolvedCategory === 'journals' ? 'Close Publication' : (resolvedCategory === 'court_rolls' || resolvedCategory === 'other' ? 'Close Schedule' : 'Close Dossier')) }}
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
