<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import {
    ChevronLeft,
    CheckCircle2,
    Database,
    FileText,
    Link2
} from 'lucide-vue-next';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    is_active: true
});

// Auto-generate slug from name
watch(() => form.name, (newValue) => {
    form.slug = newValue
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
});

const submit = () => {
    form.post(route('admin.datasets.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Admin - Create Dataset" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-10 gap-6">
            <div class="flex items-start gap-4">
                <Link :href="route('admin.datasets.index')"
                    class="p-3 bg-zinc-900 border border-white/5 text-zinc-500 hover:text-white rounded-xl transition-all shadow-sm">
                    <ChevronLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tighter text-white">Create New Dataset</h1>
                    <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">Add a new legal case library or intelligence dataset</p>
                </div>
            </div>

            <button @click="submit" :disabled="form.processing"
                class="bg-admin-modern text-black px-10 py-4 rounded-xl font-black uppercase tracking-widest text-xs hover:bg-admin-modern/90 transition-all shadow-xl shadow-admin-modern/20 disabled:opacity-50 flex items-center justify-center gap-2">
                <CheckCircle2 class="w-4 h-4" />
                Finalize & Publish
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start pb-20">
            <!-- Left Side: Main Form Data -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Core Details Section -->
                <div class="bg-zinc-900/40 rounded-[2.5rem] border border-white/5 p-6 sm:p-10">
                    <h2 class="text-xl font-black uppercase tracking-tight text-white mb-10 flex items-center gap-3">
                        <Database class="w-5 h-5 text-admin-modern" />
                        Dataset Specifications
                    </h2>

                    <div class="space-y-8">
                        <div>
                            <InputLabel for="name" value="Dataset Name"
                                class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-3 ml-1" />
                            <input id="name" type="text" v-model="form.name"
                                class="w-full bg-black/40 border border-white/5 text-white rounded-xl py-4 px-6 focus:ring-1 focus:ring-admin-modern/40 focus:border-admin-modern/40 transition-all font-bold"
                                placeholder="e.g. Labour Court Judgments" required />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="slug" value="Slug Identifier (URL safe)"
                                class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-3 ml-1" />
                            <div class="relative">
                                <Link2 class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" />
                                <input id="slug" type="text" v-model="form.slug"
                                    class="w-full bg-black/40 border border-white/5 text-white rounded-xl py-4 pl-12 pr-6 focus:ring-1 focus:ring-admin-modern/40 focus:border-admin-modern/40 transition-all font-mono text-xs"
                                    placeholder="e.g. labour-court" required />
                            </div>
                            <InputError class="mt-2" :message="form.errors.slug" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Dataset Description"
                                class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-3 ml-1" />
                            <textarea id="description" v-model="form.description" rows="6"
                                placeholder="Detail the legal court sources, date coverage, update frequency..."
                                class="w-full bg-black/40 border border-white/5 text-white rounded-xl p-6 focus:ring-1 focus:ring-admin-modern/40 transition-all font-medium text-sm leading-relaxed"></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Status Sidebar -->
            <div class="space-y-8">
                <div class="bg-zinc-900/40 rounded-[2.5rem] border border-white/5 p-6 sm:p-10">
                    <h2 class="text-lg font-black uppercase tracking-tight text-white mb-6">Status</h2>
                    <div>
                        <label class="flex items-center cursor-pointer gap-3">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                            <div class="relative w-11 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-zinc-400 after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-admin-modern peer-checked:after:bg-black peer-checked:after:border-black"></div>
                            <span class="text-xs font-black uppercase tracking-widest text-zinc-400 peer-checked:text-white">Active & Searchable</span>
                        </label>
                        <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wide mt-3 ml-1">If disabled, this dataset won't show in pricing tiers or checkout options.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
