<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Key, 
    Copy, 
    Check, 
    Terminal, 
    BookOpen, 
    Cpu, 
    Layers, 
    ShieldCheck,
    ArrowRight,
    RefreshCw,
    Trash2
} from 'lucide-vue-next';

defineProps({
    auth: Object,
    apiKeys: Array
});

const isGenerating = ref(false);
const activeTab = ref('curl');
const copiedKey = ref('');

const generateKey = () => {
    isGenerating.value = true;
    router.post(route('developer.api-keys.store'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isGenerating.value = false;
        }
    });
};

const deleteKey = (id) => {
    if (confirm('Are you sure you want to delete this API key? Requests using this key will immediately fail.')) {
        router.delete(route('developer.api-keys.destroy', id), {
            preserveScroll: true
        });
    }
};

const copyKey = (keyText) => {
    navigator.clipboard.writeText(keyText);
    copiedKey.value = keyText;
    setTimeout(() => {
        if (copiedKey.value === keyText) {
            copiedKey.value = '';
        }
    }, 2000);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const codeSnippets = {
    curl: `curl -X GET "https://ohmbase.io/api/v1/cases?dataset=ccma&page=1&limit=10" \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Accept: application/json"`,
    javascript: `fetch('https://ohmbase.io/api/v1/cases?dataset=ccma&page=1&limit=10', {
  headers: {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Accept': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));`,
    php: `$client = new \\GuzzleHttp\\Client();

$response = $client->request('GET', 'https://ohmbase.io/api/v1/cases', [
    'query' => [
        'dataset' => 'ccma',
        'page' => 1,
        'limit' => 10
    ],
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Accept' => 'application/json',
    ]
]);

echo $response->getBody();`,
    python: `import requests

url = "https://ohmbase.io/api/v1/cases"
headers = {
    "Authorization": "Bearer YOUR_API_KEY",
    "Accept": "application/json"
}
params = {
    "dataset": "ccma",
    "page": 1,
    "limit": 10
}

response = requests.get(url, headers=headers, params=params)
print(response.json())`
};
</script>

<template>
    <MainLayout :auth="auth" title="Developer API Documentation">
        <div class="max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="mb-16">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-4 py-1 bg-sky-500/10 text-sky-400 text-[10px] font-black uppercase tracking-widest rounded-full border border-sky-500/20">
                        Developer Portal
                    </span>
                </div>
                <h1 class="text-7xl font-black uppercase tracking-tighter text-white leading-none">API Documentation</h1>
                <p class="text-zinc-500 font-black uppercase tracking-widest text-xs mt-4">Integrate structured legal intelligence directly into your workflow</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <!-- API Info & Key Generation -->
                <div class="lg:col-span-2 space-y-10">
                    <!-- Authentication Card -->
                    <div class="bg-zinc-900 rounded-[3rem] p-10 border border-white/5 shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-sky-500/5 rounded-full blur-3xl -z-10"></div>
                        <div class="flex items-start gap-6">
                            <div class="w-14 h-14 bg-sky-500/10 rounded-2xl flex items-center justify-center border border-sky-500/20 text-sky-400">
                                <Key class="w-6 h-6" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-white mb-2">API Authentication</h3>
                                <p class="text-zinc-400 text-sm mb-8 leading-relaxed">
                                    Authenticate your requests using Bearer tokens. Keep your keys secret and never share them or expose them in client-side code.
                                </p>

                                <button 
                                    @click="generateKey" 
                                    :disabled="isGenerating"
                                    class="inline-flex items-center gap-2 bg-white text-black hover:bg-zinc-200 disabled:bg-zinc-800 disabled:text-zinc-600 px-8 py-4 rounded-full font-black uppercase tracking-widest text-xs transition-all active:scale-95 mb-8"
                                >
                                    <RefreshCw v-if="isGenerating" class="w-4 h-4 animate-spin" />
                                    <span>Generate API Key</span>
                                </button>

                                <!-- API Keys List -->
                                <div v-if="apiKeys && apiKeys.length > 0" class="space-y-4 pt-6 border-t border-white/5">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Active API Keys</p>
                                    <div v-for="keyObj in apiKeys" :key="keyObj.id" class="bg-black/40 border border-white/5 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-4 bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 font-mono text-xs text-sky-400 overflow-x-auto whitespace-nowrap">
                                                <span>{{ keyObj.key }}</span>
                                                <button @click="copyKey(keyObj.key)" class="p-1 hover:bg-white/5 rounded transition-colors flex-shrink-0 ml-auto">
                                                    <Check v-if="copiedKey === keyObj.key" class="w-3.5 h-3.5 text-emerald-400" />
                                                    <Copy v-else class="w-3.5 h-3.5 text-zinc-400 hover:text-white" />
                                                </button>
                                            </div>
                                            <p class="text-[9px] text-zinc-500 mt-1.5 ml-1">Created {{ formatDate(keyObj.created_at) }}</p>
                                        </div>
                                        <button @click="deleteKey(keyObj.id)" class="p-3 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white rounded-xl transition-all border border-red-500/10 self-start sm:self-center flex-shrink-0">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Endpoints Documentation -->
                    <div class="bg-zinc-900 rounded-[3rem] p-10 border border-white/5 shadow-2xl space-y-12">
                        <div class="border-b border-white/5 pb-6">
                            <h3 class="text-2xl font-bold text-white">API Reference</h3>
                            <p class="text-zinc-500 text-sm mt-1">Version 1.0 (OpenAPI-compliant endpoints)</p>
                        </div>

                        <!-- GET /cases -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-4 flex-wrap">
                                <span class="bg-emerald-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-wider px-3.5 py-1.5 rounded-lg border border-emerald-500/20">
                                    GET
                                </span>
                                <span class="font-mono font-bold text-white">/api/v1/cases</span>
                                <span class="text-zinc-500 text-sm">— Retrieve list of arbitrations/judgments</span>
                            </div>

                            <p class="text-zinc-400 text-sm leading-relaxed">
                                Query our structured, sanitized dataset. Results are ordered chronologically by decision date.
                            </p>

                            <!-- Query Params -->
                            <div>
                                <h4 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-3">Query Parameters</h4>
                                <div class="bg-black/30 border border-white/5 rounded-2xl overflow-hidden divide-y divide-white/5 text-sm">
                                    <div class="p-4 flex justify-between gap-6">
                                        <div>
                                            <span class="font-mono text-white font-bold">dataset</span>
                                            <span class="text-zinc-600 text-xs ml-2">string</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-zinc-500">Filters by collection. Options: </span>
                                            <code class="text-sky-400 text-xs">ccma</code>, <code class="text-sky-400 text-xs">labour-court</code>
                                        </div>
                                    </div>
                                    <div class="p-4 flex justify-between gap-6">
                                        <div>
                                            <span class="font-mono text-white font-bold">page</span>
                                            <span class="text-zinc-600 text-xs ml-2">integer</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-zinc-500">Pagination page offset. Default: </span>
                                            <code class="text-sky-400 text-xs">1</code>
                                        </div>
                                    </div>
                                    <div class="p-4 flex justify-between gap-6">
                                        <div>
                                            <span class="font-mono text-white font-bold">limit</span>
                                            <span class="text-zinc-600 text-xs ml-2">integer</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-zinc-500">Number of cases per page. Max: 100. Default: </span>
                                            <code class="text-sky-400 text-xs">10</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- GET /cases/{id} -->
                        <div class="pt-8 border-t border-white/5 space-y-6">
                            <div class="flex items-center gap-4 flex-wrap">
                                <span class="bg-emerald-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-wider px-3.5 py-1.5 rounded-lg border border-emerald-500/20">
                                    GET
                                </span>
                                <span class="font-mono font-bold text-white">/api/v1/cases/{id}</span>
                                <span class="text-zinc-500 text-sm">— Retrieve specific case details</span>
                            </div>

                            <p class="text-zinc-400 text-sm leading-relaxed">
                                Fetch complete details, metadata, and full text for a single arbitration award or court judgment.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Code Example Tabs & Sidebar -->
                <div class="space-y-10">
                    <!-- Quick Specs -->
                    <div class="bg-zinc-900 rounded-[3rem] p-10 border border-white/5 shadow-2xl">
                        <h4 class="text-xl font-bold text-white mb-6">Service Specs</h4>
                        <ul class="space-y-4 text-sm text-zinc-400">
                            <li class="flex items-center gap-3">
                                <ShieldCheck class="w-5 h-5 text-emerald-400 flex-shrink-0" />
                                <span>SSL-enforced HTTPS endpoints</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <Layers class="w-5 h-5 text-sky-400 flex-shrink-0" />
                                <span>Rate Limit: 1,000 req/month</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <Cpu class="w-5 h-5 text-purple-400 flex-shrink-0" />
                                <span>POPIA compliant data entries</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Interactive Code Tab -->
                    <div class="bg-zinc-900 rounded-[3rem] border border-white/5 overflow-hidden shadow-2xl">
                        <div class="bg-black/50 px-6 py-4 border-b border-white/5 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-zinc-400">
                                <Terminal class="w-4 h-4" />
                                <span class="text-xs font-bold uppercase tracking-widest">Example Request</span>
                            </div>
                        </div>

                        <!-- Language Tabs -->
                        <div class="flex border-b border-white/5 bg-zinc-900/60 p-1 text-[11px] font-black uppercase tracking-widest text-zinc-500">
                            <button 
                                v-for="lang in ['curl', 'javascript', 'php', 'python']" 
                                :key="lang"
                                @click="activeTab = lang"
                                :class="[
                                    'flex-1 py-2 text-center rounded-lg transition-all',
                                    activeTab === lang ? 'bg-white/5 text-white' : 'hover:text-zinc-300'
                                ]"
                            >
                                {{ lang === 'javascript' ? 'JS' : lang.toUpperCase() }}
                            </button>
                        </div>

                        <!-- Code Body -->
                        <div class="p-6 bg-black/40 font-mono text-[12px] leading-relaxed text-zinc-300 overflow-x-auto whitespace-pre">
                            <code>{{ codeSnippets[activeTab] }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
