<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { 
    ChevronLeft, 
    Upload, 
    Plus, 
    X, 
    Euro, 
    Box, 
    Palette, 
    Maximize2, 
    CheckCircle2,
    Images
} from 'lucide-vue-next';

const props = defineProps({
    brands: Array,
    categories: Array
});

const form = useForm({
    name: '',
    description: '',
    price: '',
    sale_price: '',
    brand_ids: [],
    category_id: '',
    stock: '',
    colors: [],
    sizes: [],
    image: null,
    gallery_images: []
});

const newColor = ref('');
const addColor = () => {
    if (newColor.value && !form.colors.includes(newColor.value)) {
        form.colors.push(newColor.value);
        newColor.value = '';
    }
};
const removeColor = (index) => {
    form.colors.splice(index, 1);
};

const newSize = ref('');
const addSize = () => {
    if (newSize.value && !form.sizes.includes(newSize.value)) {
        form.sizes.push(newSize.value);
        form.sizes.sort((a, b) => parseFloat(a) - parseFloat(b));
        newSize.value = '';
    }
};
const removeSize = (index) => {
    form.sizes.splice(index, 1);
};

const submit = () => {
    form.post(route('admin.products.store'), {
        onSuccess: () => form.reset(),
    });
};

const handleMainImage = (e) => {
    form.image = e.target.files[0];
};

const handleGalleryImages = (e) => {
    form.gallery_images = Array.from(e.target.files).slice(0, 4);
};
</script>

<template>
    <Head title="Admin - Create Product" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-10 gap-6">
            <div class="flex items-start gap-4">
                <Link :href="route('admin.products.index')" class="p-3 bg-zinc-900 border border-white/5 text-zinc-500 hover:text-white rounded-xl transition-all shadow-sm">
                    <ChevronLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-3xl sm:text-4xl font-black italic uppercase tracking-tighter text-white">Create New Drop</h1>
                    <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px]">Introduce a new sneaker silhouette to your global catalog</p>
                </div>
            </div>
            
            <button 
                @click="submit"
                :disabled="form.processing"
                class="bg-admin-modern text-black px-10 py-4 rounded-xl font-black uppercase tracking-widest text-xs hover:bg-admin-modern/90 transition-all shadow-xl shadow-admin-modern/20 disabled:opacity-50 flex items-center justify-center gap-2"
            >
                <CheckCircle2 class="w-4 h-4" />
                Finalize & Publish
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start pb-20">
            <!-- Left Side: Main Form Data -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Core Details Section -->
                <div class="bg-zinc-900/40 rounded-[2.5rem] border border-white/5 p-6 sm:p-10">
                    <h2 class="text-xl font-black uppercase italic tracking-tight text-white mb-10 flex items-center gap-3">
                        <Box class="w-5 h-5 text-admin-modern" />
                        Core Specifications
                    </h2>

                    <div class="space-y-8">
                        <div>
                            <InputLabel for="name" value="Product Title" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-3 ml-1" />
                            <input 
                                id="name" 
                                type="text" 
                                v-model="form.name"
                                class="w-full bg-black/40 border border-white/5 text-white rounded-xl py-4 px-6 focus:ring-1 focus:ring-admin-modern/40 focus:border-admin-modern/40 transition-all font-bold" 
                                placeholder="Jordan 4 Retro 'Military Blue'" 
                                required 
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Product Narrative" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-3 ml-1" />
                            <textarea 
                                id="description" 
                                v-model="form.description"
                                rows="6"
                                placeholder="Detail the materials, heritage, and unique design features..."
                                class="w-full bg-black/40 border border-white/5 text-white rounded-xl p-6 focus:ring-1 focus:ring-admin-modern/40 transition-all font-medium text-sm leading-relaxed"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div class="sm:col-span-1">
                                <InputLabel for="price" value="Retail Price (€)" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-3 ml-1" />
                                <div class="relative">
                                    <input id="price" type="number" step="0.01" v-model="form.price" class="w-full bg-black/40 border border-white/5 text-white rounded-xl py-4 px-6 font-black italic text-lg" required />
                                </div>
                            </div>
                            <div class="sm:col-span-1">
                                <InputLabel for="sale_price" value="Offer Price (€)" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-3 ml-1" />
                                <input id="sale_price" type="number" step="0.01" v-model="form.sale_price" class="w-full bg-black/40 border border-white/5 text-white rounded-xl py-4 px-6 font-black italic text-lg text-admin-modern" />
                            </div>
                            <div class="sm:col-span-1">
                                <InputLabel for="stock" value="Stock Count" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-3 ml-1" />
                                <input id="stock" type="number" v-model="form.stock" class="w-full bg-black/40 border border-white/5 text-white rounded-xl py-4 px-6 font-bold" required />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attributes Section -->
                <div class="bg-zinc-900/40 rounded-[2.5rem] border border-white/5 p-6 sm:p-10">
                    <h2 class="text-xl font-black uppercase italic tracking-tight text-white mb-10 flex items-center gap-3">
                        <Palette class="w-5 h-5 text-admin-modern" />
                        Variant Mapping
                    </h2>

                    <div class="space-y-10">
                        <div>
                            <InputLabel value="Available Colorways" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-4 ml-1" />
                            <div class="flex gap-2">
                                <input v-model="newColor" @keydown.enter.prevent="addColor" placeholder="e.g. #FFFFFF or OG Red" class="flex-1 bg-black/40 border border-white/5 text-white rounded-xl py-4 px-6 focus:ring-1 focus:ring-admin-modern/40" />
                                <button type="button" @click="addColor" class="bg-white text-black px-8 rounded-xl font-black uppercase text-[10px] tracking-widest">Add</button>
                            </div>
                            <div class="flex flex-wrap gap-3 mt-6">
                                <div v-for="(color, index) in form.colors" :key="index" class="group flex items-center bg-zinc-800 border border-white/10 pl-2 pr-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-white transition-all hover:border-white/20">
                                    <div v-if="color.startsWith('#')" class="w-6 h-6 rounded-lg mr-3 border border-white/10" :style="{ backgroundColor: color }"></div>
                                    <span>{{ color }}</span>
                                    <button type="button" @click="removeColor(index)" class="ml-3 text-zinc-600 hover:text-rose-500 transition-colors"><X class="w-3 h-3" /></button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Size Run (EU)" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-4 ml-1" />
                            <div class="flex gap-2">
                                <input v-model="newSize" @keydown.enter.prevent="addSize" placeholder="e.g. 42.5" class="flex-1 bg-black/40 border border-white/5 text-white rounded-xl py-4 px-6 focus:ring-1 focus:ring-admin-modern/40" />
                                <button type="button" @click="addSize" class="bg-white text-black px-8 rounded-xl font-black uppercase text-[10px] tracking-widest">Add</button>
                            </div>
                            <div class="flex flex-wrap gap-3 mt-6">
                                <div v-for="(size, index) in form.sizes" :key="index" class="flex items-center bg-zinc-800 border border-white/10 px-5 py-2.5 rounded-xl text-[10px] font-black italic uppercase tracking-widest text-white transition-all hover:bg-zinc-700">
                                    <span>EU {{ size }}</span>
                                    <button type="button" @click="removeSize(index)" class="ml-3 text-zinc-600 hover:text-rose-500 transition-colors"><X class="w-3 h-3" /></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Taxonomy & Media -->
            <div class="space-y-8">
                <!-- Taxonomy Sidebar -->
                <div class="bg-zinc-900/40 rounded-[2.5rem] border border-white/5 p-6 sm:p-10">
                    <div class="space-y-8">
                        <div>
                            <InputLabel value="Brand Partnership" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-4 ml-1" />
                            <div class="grid grid-cols-2 gap-3 max-h-56 overflow-y-auto custom-scrollbar pr-2">
                                <label 
                                    v-for="brand in brands" 
                                    :key="brand.id"
                                    :class="[
                                        'flex flex-col items-center justify-center p-4 rounded-2xl border transition-all cursor-pointer text-center gap-3',
                                        form.brand_ids.includes(brand.id) 
                                            ? 'bg-white border-white text-black shadow-2xl' 
                                            : 'bg-black/20 border-white/5 text-zinc-500 hover:border-white/10'
                                    ]"
                                >
                                    <input type="checkbox" :value="brand.id" v-model="form.brand_ids" class="hidden" />
                                    <img :src="brand.logo" class="h-5 w-full object-contain transition-all" :class="{'grayscale-0 invert': form.brand_ids.includes(brand.id), 'grayscale opacity-40': !form.brand_ids.includes(brand.id)}" />
                                    <span class="text-[8px] font-black uppercase tracking-[0.2em] truncate w-full">{{ brand.name }}</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="category" value="Classification" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-4 ml-1" />
                            <select id="category" v-model="form.category_id" class="w-full bg-black/40 border border-white/5 text-white rounded-xl py-4 px-6 focus:ring-1 focus:ring-admin-modern appearance-none font-bold text-xs uppercase tracking-widest" required>
                                <option value="">Select Genre</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Media Assets Sidebar -->
                <div class="bg-black border border-white/5 rounded-[2.5rem] p-6 sm:p-10 shadow-2xl">
                    <h2 class="text-xl font-black uppercase italic tracking-tight text-white mb-10 flex items-center gap-3">
                        <Images class="w-5 h-5 text-admin-modern" />
                        Assets
                    </h2>

                    <div class="space-y-8">
                        <div>
                            <InputLabel value="Primary Shot" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-4 ml-1" />
                            <div class="relative group aspect-square">
                                <input type="file" id="image" @change="handleMainImage" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" />
                                <div class="h-full bg-zinc-900 border-2 border-dashed border-white/5 rounded-[2rem] flex flex-col items-center justify-center text-center p-6 transition-all group-hover:border-admin-modern group-hover:bg-zinc-900/50">
                                    <Upload class="w-10 h-10 text-zinc-700 mb-4 group-hover:text-admin-modern transition-colors" />
                                    <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Drag primary image</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Studio Gallery (Max 4)" class="text-zinc-600 font-black uppercase tracking-widest text-[10px] mb-4 ml-1" />
                            <div class="relative group">
                                <input type="file" id="gallery" @change="handleGalleryImages" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" />
                                <div class="bg-zinc-900 border-2 border-dashed border-white/5 rounded-3xl py-10 flex flex-col items-center justify-center text-center px-6 transition-all group-hover:border-white/20">
                                    <Plus class="w-8 h-8 text-zinc-700 mb-2" />
                                    <p class="text-[9px] font-black uppercase tracking-widest text-zinc-700">Multiple selection</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 3px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
</style>
