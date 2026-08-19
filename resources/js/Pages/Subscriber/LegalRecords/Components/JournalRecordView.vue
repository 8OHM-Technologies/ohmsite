<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  BookOpen,
  Calendar,
  ExternalLink,
  Bookmark,
  Sparkles,
  Lock,
  ArrowRight,
  FileText,
  Copy,
  Check,
  Building,
  User,
  Type
} from 'lucide-vue-next';

const props = defineProps<{
  recordDetail: any;
  isPro: boolean;
}>();

const dataObj = computed(() => {
  if (!props.recordDetail) return {};
  return props.recordDetail.data || props.recordDetail;
});

const title = computed(() => dataObj.value.title || 'Publication Dossier');
const publisher = computed(() => dataObj.value.author || dataObj.value.applicant || dataObj.value.court || 'Law Journal / Official Publisher');
const citation = computed(() => dataObj.value.citation || dataObj.value.case_number || null);
const publicationDate = computed(() => dataObj.value.document_date || dataObj.value.judgment_date || 'N/A');
const summary = computed(() => dataObj.value.summary || dataObj.value.abstract || null);
const fullText = computed(() => dataObj.value.full_text || dataObj.value.content || null);
const sourceUrl = computed(() => dataObj.value.source_url || props.recordDetail?.source_url || null);
const category = computed(() => dataObj.value.category || (dataObj.value.record_type?.includes('gaz') ? 'gaz' : 'journals'));

// Reader controls
const fontSize = ref<'sm' | 'base' | 'lg'>('base');
const copied = ref(false);

const formattedParagraphs = computed(() => {
  const text = fullText.value;
  if (!text) return [];
  // Split on double newlines or single newlines followed by paragraph structure
  return text
    .split(/\n\s*\n/)
    .map((p: string) => p.trim())
    .filter((p: string) => p.length > 0);
});

const copyContent = async () => {
  const textToCopy = fullText.value || summary.value || '';
  if (!textToCopy) return;
  try {
    await navigator.clipboard.writeText(textToCopy);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2000);
  } catch (e) {
    console.error('Failed to copy text', e);
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Standard Tier Upgrade Notice Banner (if not Pro) -->
    <div v-if="!isPro"
      class="bg-gradient-to-r from-amber-500/10 via-primary/10 to-transparent border border-primary/30 p-4 sm:p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="space-y-1">
        <div class="flex items-center gap-2 text-primary font-black uppercase text-xs tracking-wider">
          <Sparkles class="w-4 h-4" /> Standard Preview: Complete Text Index Locked
        </div>
        <p class="text-xs text-zinc-300">
          Subscribe now to read unredacted full-text articles, official notices, and indexed legal commentary.
        </p>
      </div>
      <a href="/#pricing"
        class="btn btn-primary px-4 py-2 text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-primary/20 flex items-center gap-1.5 shrink-0">
        <span>Upgrade to Pro</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </a>
    </div>

    <!-- Publication Metadata Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
      <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5 space-y-1">
        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block flex items-center gap-1">
          <Building class="w-3 h-3 text-primary" /> Publisher / Journal
        </span>
        <span class="font-bold text-white block truncate">{{ publisher }}</span>
      </div>

      <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5 space-y-1">
        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block flex items-center gap-1">
          <Calendar class="w-3 h-3 text-primary" /> Publication Date
        </span>
        <span class="font-bold text-white block">{{ publicationDate }}</span>
      </div>

      <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5 space-y-1">
        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block flex items-center gap-1">
          <BookOpen class="w-3 h-3 text-primary" /> Citation / Volume
        </span>
        <span class="font-bold text-primary block truncate font-mono text-[11px]">
          {{ citation || 'Indexed Publication' }}
        </span>
      </div>

      <div class="bg-zinc-900/50 p-3.5 rounded-2xl border border-white/5 space-y-1">
        <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider block flex items-center gap-1">
          <FileText class="w-3 h-3 text-primary" /> Document Type
        </span>
        <span class="font-bold text-zinc-300 block capitalize">
          {{ category === 'gaz' ? 'Government Gazette Notice' : 'Scholarly Law Review' }}
        </span>
      </div>
    </div>

    <!-- Abstract & Summary (if present) -->
    <div v-if="summary" class="bg-zinc-900/60 border border-white/10 p-5 sm:p-6 rounded-2xl space-y-2">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Bookmark class="w-4 h-4 text-amber-400" />
          <span class="text-xs font-black uppercase tracking-wider text-amber-400">
            Abstract &amp; Executive Summary
          </span>
        </div>
      </div>
      <p class="text-xs text-zinc-200 leading-relaxed whitespace-pre-line font-medium">
        {{ summary }}
      </p>
    </div>

    <!-- Formatted Publication Text Reading Canvas -->
    <div class="bg-zinc-900/40 border border-white/5 rounded-2xl overflow-hidden">
      <!-- Reader Toolbar -->
      <div class="flex items-center justify-between px-5 py-3 border-b border-white/5 bg-zinc-900/70">
        <div class="flex items-center gap-2 text-xs text-zinc-400">
          <BookOpen class="w-4 h-4 text-primary" />
          <span class="font-black uppercase tracking-wider text-white text-[11px]">Publication Text</span>
        </div>

        <div class="flex items-center gap-2">
          <!-- Font Size Selector -->
          <div class="flex items-center bg-black/40 border border-white/10 rounded-lg p-0.5 text-[10px] font-bold">
            <button @click="fontSize = 'sm'"
              :class="fontSize === 'sm' ? 'bg-primary text-black font-black' : 'text-zinc-400 hover:text-white'"
              class="px-2 py-1 rounded transition-all cursor-pointer">
              A-
            </button>
            <button @click="fontSize = 'base'"
              :class="fontSize === 'base' ? 'bg-primary text-black font-black' : 'text-zinc-400 hover:text-white'"
              class="px-2 py-1 rounded transition-all cursor-pointer">
              A
            </button>
            <button @click="fontSize = 'lg'"
              :class="fontSize === 'lg' ? 'bg-primary text-black font-black' : 'text-zinc-400 hover:text-white'"
              class="px-2 py-1 rounded transition-all cursor-pointer">
              A+
            </button>
          </div>

          <!-- Copy Button -->
          <button @click="copyContent"
            class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
            <Check v-if="copied" class="w-3.5 h-3.5 text-emerald-400" />
            <Copy v-else class="w-3.5 h-3.5" />
            <span class="text-[10px] uppercase tracking-wider">{{ copied ? 'Copied' : 'Copy' }}</span>
          </button>
        </div>
      </div>

      <!-- Text Body Area -->
      <div class="p-6 sm:p-8 relative">
        <!-- If full text is available -->
        <div v-if="formattedParagraphs.length > 0" class="space-y-4"
          :class="[
            fontSize === 'sm' ? 'text-xs leading-relaxed' : '',
            fontSize === 'base' ? 'text-sm leading-relaxed' : '',
            fontSize === 'lg' ? 'text-base leading-loose' : '',
            !isPro ? 'max-h-96 overflow-hidden relative select-none' : ''
          ]">
          <p v-for="(para, idx) in formattedParagraphs" :key="idx"
            class="text-zinc-200 font-sans tracking-wide">
            {{ para }}
          </p>

          <!-- Blur & Pro Lock Overlay for Standard Tier -->
          <div v-if="!isPro"
            class="absolute inset-x-0 bottom-0 top-32 bg-gradient-to-t from-zinc-950 via-zinc-950/90 to-transparent flex flex-col items-center justify-end pb-8 p-6 text-center space-y-3">
            <div
              class="w-10 h-10 rounded-full bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
              <Lock class="w-5 h-5" />
            </div>
            <h4 class="text-sm font-black uppercase tracking-wider text-white">
              Full Publication Reading Mode Locked
            </h4>
            <p class="text-xs text-zinc-400 max-w-md">
              Upgrade to a Pro subscription to read unredacted articles, complete statutory notices, and download original files.
            </p>
            <a href="/#pricing"
              class="btn btn-primary px-5 py-2.5 text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-primary/20 flex items-center gap-2 mt-2">
              <span>Unlock Full Publication</span>
              <ArrowRight class="w-4 h-4" />
            </a>
          </div>
        </div>

        <!-- Fallback if no full text is stored in database -->
        <div v-else class="py-12 flex flex-col items-center justify-center text-center space-y-4">
          <div class="w-12 h-12 rounded-2xl bg-zinc-800/60 border border-white/5 flex items-center justify-center text-zinc-400">
            <FileText class="w-6 h-6 text-zinc-500" />
          </div>
          <div class="space-y-1 max-w-md">
            <h4 class="text-sm font-bold text-white uppercase tracking-wider">Indexed Publication Notice</h4>
            <p class="text-xs text-zinc-400 leading-relaxed">
              The full publication body is hosted on the primary forum repository. You can inspect the complete source document directly at the original provider.
            </p>
          </div>
          <a v-if="sourceUrl" :href="sourceUrl" target="_blank" rel="noopener noreferrer"
            class="btn btn-primary px-4 py-2 text-xs font-black uppercase tracking-wider rounded-xl shadow-md shadow-primary/20 flex items-center gap-2">
            <span>Open Source Publication</span>
            <ExternalLink class="w-3.5 h-3.5" />
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
