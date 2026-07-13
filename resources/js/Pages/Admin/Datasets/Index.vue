<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Edit3,
    Trash2,
    Plus,
    Database,
    Search,
    CheckCircle,
    XCircle
} from 'lucide-vue-next';

defineProps({
    datasets: Array
});

const deleteDataset = (id) => {
    if (confirm('Are you sure you want to delete this dataset? This may affect products linked to this dataset.')) {
        router.delete(route('admin.datasets.destroy', id));
    }
};
</script>

<template>
    <Head title="Admin - Datasets" />

    <AdminLayout>
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 lg:mb-12 gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-2 h-8 bg-admin-modern rounded-full"></div>
                    <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tighter text-white">Datasets Configuration</h1>
                </div>
                <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">Configure legal case law and metadata sources</p>
            </div>
            <Link :href="route('admin.datasets.create')"
                class="w-full sm:w-auto bg-white text-black px-8 py-4 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-admin-modern transition-all shadow-xl active:scale-95 flex items-center justify-center gap-2">
                <Plus class="w-4 h-4" />
                Add New Dataset
            </Link>
        </div>

        <!-- Datasets List -->
        <div class="bg-zinc-900/40 rounded-[2rem] lg:rounded-[3rem] border border-white/5 overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-white/5 flex flex-col sm:flex-row justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" />
                    <input type="text" placeholder="Filter datasets..."
                        class="w-full bg-black/40 border border-white/5 rounded-xl py-3 pl-11 pr-4 text-xs font-bold text-white focus:ring-1 focus:ring-admin-modern/30" />
                </div>
            </div>

            <div class="p-4 sm:p-8">
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left border-separate border-spacing-y-4">
                        <thead>
                            <tr>
                                <th class="pb-2 px-6 font-black uppercase text-[10px] tracking-[0.2em] text-zinc-600">Dataset Details</th>
                                <th class="pb-2 px-6 font-black uppercase text-[10px] tracking-[0.2em] text-zinc-600">Slug Identifier</th>
                                <th class="pb-2 px-6 font-black uppercase text-[10px] tracking-[0.2em] text-zinc-600">Status</th>
                                <th class="pb-2 px-6 font-black uppercase text-[10px] tracking-[0.2em] text-zinc-600 text-right">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="dataset in datasets" :key="dataset.id" class="group">
                                <td class="py-4 px-6 bg-black/20 rounded-l-3xl border-y border-l border-white/5 group-hover:bg-zinc-800/40 transition-all duration-300">
                                    <div class="flex items-center gap-5">
                                        <div class="w-12 h-12 rounded-xl bg-zinc-900 border border-white/5 flex items-center justify-center overflow-hidden shrink-0">
                                            <Database class="w-5 h-5 text-admin-modern" />
                                        </div>
                                        <div>
                                            <p class="font-black text-white uppercase tracking-tight leading-none mb-1.5 group-hover:text-admin-modern transition-colors">
                                                {{ dataset.name }}
                                            </p>
                                            <p class="text-[9px] font-bold text-zinc-600 uppercase tracking-widest truncate max-w-md">
                                                {{ dataset.description || 'No description provided.' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 bg-black/20 border-y border-white/5 group-hover:bg-zinc-800/40 transition-all duration-300">
                                    <span class="text-xs font-mono text-zinc-300 bg-zinc-950 px-3 py-1.5 rounded-lg border border-white/5">
                                        {{ dataset.slug }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 bg-black/20 border-y border-white/5 group-hover:bg-zinc-800/40 transition-all duration-300">
                                    <div class="flex items-center gap-2">
                                        <component :is="dataset.is_active ? CheckCircle : XCircle" 
                                            class="w-4 h-4" 
                                            :class="dataset.is_active ? 'text-admin-modern' : 'text-rose-500'" />
                                        <span class="text-[10px] font-black uppercase tracking-[0.1em]"
                                            :class="dataset.is_active ? 'text-zinc-400' : 'text-rose-500'">
                                            {{ dataset.is_active ? 'Active' : 'Disabled' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 bg-black/20 rounded-r-3xl border-y border-r border-white/5 group-hover:bg-zinc-800/40 transition-all duration-300 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.datasets.edit', dataset.id)"
                                            class="p-3 bg-zinc-900 border border-white/5 text-zinc-400 hover:text-admin-modern hover:border-admin-modern/30 rounded-xl transition-all">
                                            <Edit3 class="w-4 h-4" />
                                        </Link>
                                        <button @click="deleteDataset(dataset.id)"
                                            class="p-3 bg-zinc-900 border border-white/5 text-zinc-400 hover:text-rose-500 hover:border-rose-500/30 rounded-xl transition-all">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden space-y-4">
                    <div v-for="dataset in datasets" :key="dataset.id"
                        class="bg-black/20 rounded-3xl border border-white/5 p-6 space-y-6">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-zinc-900 border border-white/5 flex items-center justify-center overflow-hidden shrink-0">
                                <Database class="w-6 h-6 text-admin-modern" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-black text-lg text-white uppercase tracking-tighter truncate">{{ dataset.name }}</h3>
                                <p class="text-[9px] font-mono text-zinc-500 tracking-wider mb-2">{{ dataset.slug }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/5">
                            <div class="flex flex-col gap-1">
                                <span class="text-[8px] font-black text-zinc-500 uppercase tracking-widest">Status</span>
                                <span class="text-[10px] font-black uppercase tracking-widest"
                                    :class="dataset.is_active ? 'text-admin-modern' : 'text-rose-500'">
                                    {{ dataset.is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <Link :href="route('admin.datasets.edit', dataset.id)"
                                class="flex-1 bg-white text-black py-4 rounded-xl font-black uppercase text-[10px] tracking-widest text-center">
                                Edit Dataset</Link>
                            <button @click="deleteDataset(dataset.id)"
                                class="p-4 bg-zinc-800 text-rose-500 rounded-xl border border-white/5">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="datasets.length === 0" class="py-32 text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-zinc-800/50 rounded-full flex items-center justify-center mb-6 border border-white/5">
                        <Database class="w-8 h-8 text-zinc-600" />
                    </div>
                    <h3 class="text-2xl font-black uppercase tracking-tighter text-zinc-700 mb-2">No datasets configured</h3>
                    <p class="text-zinc-600 font-bold uppercase tracking-widest text-[10px]">Start by creating your first case law or telemetry dataset</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
