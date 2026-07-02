<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    product: Object,
    brands: Array,
    categories: Array
});

const form = useForm({
    _method: 'PUT',
    name: props.product.name,
    description: props.product.description || '',
    price: props.product.price,
    sale_price: props.product.sale_price || '',
    brand_ids: props.product.brands.map(b => b.id),
    category_id: props.product.category_id,
    stock: props.product.stock,
    colors: props.product.colors || [],
    sizes: props.product.sizes || [],
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
        // Auto-sort sizes numerically
        form.sizes.sort((a, b) => parseFloat(a) - parseFloat(b));
        newSize.value = '';
    }
};
const removeSize = (index) => {
    form.sizes.splice(index, 1);
};

const submit = () => {
    // We use POST with _method: 'PUT' to handle file uploads in Laravel
    form.post(route('admin.products.update', props.product.id), {
        onSuccess: () => {},
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
    <Head :title="`Admin - Edit ${product.name}`" />

    <AdminLayout>
        <div class="flex justify-between items-end mb-10">
            <div>
                <h1 class="text-4xl font-black italic uppercase tracking-tighter mb-2 text-white">Refine Details</h1>
                <p class="text-zinc-500 font-bold uppercase tracking-widest text-xs">Editing: {{ product.name }}</p>
            </div>
            <Link 
                :href="route('admin.products.index')"
                class="bg-zinc-900 text-zinc-400 px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-zinc-800 hover:text-white transition-all shadow-sm border border-white/5"
            >
                Back to Inventory
            </Link>
        </div>

        <div class="bg-zinc-900/50 rounded-[2.5rem] overflow-hidden shadow-sm border border-white/5 p-10">
            <form @submit.prevent="submit" class="space-y-10">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Basic Info -->
                    <div class="space-y-8">
                        <div>
                            <InputLabel for="name" value="Product Name" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-2" />
                            <TextInput id="name" type="text" class="mt-1 block w-full bg-zinc-900 border-white/5 text-white rounded-2xl py-4 px-6 focus:ring-admin-modern focus:border-admin-modern transition-all" v-model="form.name" required />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Product Narrative" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-2" />
                            <textarea 
                                id="description" 
                                v-model="form.description"
                                class="mt-1 block w-full bg-zinc-900 border border-white/5 text-white rounded-2xl focus:ring-admin-modern focus:border-admin-modern transition-all h-40 p-6 text-sm font-medium leading-relaxed"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="price" value="Retail Price (€)" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-2" />
                                <TextInput id="price" type="number" step="0.01" class="mt-1 block w-full bg-zinc-900 border-white/5 text-white rounded-2xl py-4 px-6 focus:ring-admin-modern focus:border-admin-modern transition-all font-black italic text-lg" v-model="form.price" required />
                                <InputError class="mt-2" :message="form.errors.price" />
                            </div>
                            <div>
                                <InputLabel for="sale_price" value="Sale Price (€)" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-2" />
                                <TextInput id="sale_price" type="number" step="0.01" class="mt-1 block w-full bg-zinc-900 border-white/5 text-white rounded-2xl py-4 px-6 focus:ring-admin-modern focus:border-admin-modern transition-all font-black italic text-lg" v-model="form.sale_price" />
                                <InputError class="mt-2" :message="form.errors.sale_price" />
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <InputLabel value="Brand Identity" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-4" />
                                <div class="grid grid-cols-2 gap-3 max-h-40 overflow-y-auto pr-2 custom-scrollbar">
                                    <label 
                                        v-for="brand in brands" 
                                        :key="brand.id"
                                        :class="[
                                            'flex items-center space-x-3 p-3 rounded-xl border transition-all cursor-pointer group',
                                            form.brand_ids.includes(brand.id) 
                                                ? 'bg-admin-modern border-admin-modern text-black shadow-lg shadow-admin-modern/20' 
                                                : 'bg-zinc-900 border-white/5 text-zinc-500 hover:border-white/20'
                                        ]"
                                    >
                                        <input 
                                            type="checkbox" 
                                            :value="brand.id" 
                                            v-model="form.brand_ids"
                                            class="hidden"
                                        />
                                        <img :src="brand.logo" class="h-4 w-8 object-contain grayscale group-hover:grayscale-0" :class="{'grayscale-0 invert': form.brand_ids.includes(brand.id)}" />
                                        <span class="text-[10px] font-black uppercase tracking-widest truncate">{{ brand.name }}</span>
                                    </label>
                                </div>
                                <InputError class="mt-2" :message="form.errors.brand_ids" />
                            </div>
                            <div>
                                <InputLabel for="category" value="Style Category" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-2" />
                                <select id="category" v-model="form.category_id" class="mt-1 block w-full bg-zinc-900 border-white/5 text-white rounded-2xl py-4 px-6 focus:ring-admin-modern focus:border-admin-modern transition-all border font-bold text-sm appearance-none" required>
                                    <option value="">Select Category</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.category_id" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="stock" value="Initial Inventory" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-2" />
                            <TextInput id="stock" type="number" class="mt-1 block w-full bg-zinc-900 border-white/5 text-white rounded-2xl py-4 px-6 focus:ring-admin-modern focus:border-admin-modern transition-all font-bold" v-model="form.stock" required />
                            <InputError class="mt-2" :message="form.errors.stock" />
                        </div>
                    </div>

                    <!-- Attributes & Media -->
                    <div class="space-y-8">
                        <!-- Colors -->
                        <div>
                            <InputLabel value="Available Colorways" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-2" />
                            <div class="flex mt-1 space-x-2">
                                <TextInput v-model="newColor" @keydown.enter.prevent="addColor" placeholder="#000000 or Red" class="flex-1 bg-zinc-900 border-white/5 text-white rounded-2xl py-4 px-6 focus:ring-admin-modern focus:border-admin-modern" />
                                <button type="button" @click="addColor" class="bg-admin-modern text-black px-8 rounded-2xl font-black uppercase text-[10px] hover:bg-admin-modern/90 transition-all">Add</button>
                            </div>
                            <div class="flex flex-wrap gap-3 mt-4">
                                <div v-for="(color, index) in form.colors" :key="index" class="flex items-center bg-zinc-800 border border-white/10 pl-2 pr-4 py-2 rounded-xl text-xs font-bold shadow-sm group text-white">
                                    <div v-if="color.startsWith('#')" class="w-6 h-6 rounded-lg mr-3 border border-white/10" :style="{ backgroundColor: color }"></div>
                                    <span>{{ color }}</span>
                                    <button type="button" @click="removeColor(index)" class="ml-3 text-zinc-500 hover:text-red-500 transition-colors">&times;</button>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.colors" />
                        </div>

                        <!-- Sizes -->
                        <div>
                            <InputLabel value="Size Run" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-2" />
                            <div class="flex mt-1 space-x-2">
                                <TextInput v-model="newSize" @keydown.enter.prevent="addSize" placeholder="e.g. 42" class="flex-1 bg-zinc-900 border-white/5 text-white rounded-2xl py-4 px-6 focus:ring-admin-modern focus:border-admin-modern" />
                                <button type="button" @click="addSize" class="bg-admin-modern text-black px-8 rounded-2xl font-black uppercase text-[10px] hover:bg-admin-modern/90 transition-all">Add</button>
                            </div>
                            <div class="flex flex-wrap gap-3 mt-4">
                                <div v-for="(size, index) in form.sizes" :key="index" class="flex items-center bg-zinc-800 border border-white/10 px-4 py-2 rounded-xl text-xs font-black italic uppercase shadow-sm text-white">
                                    <span>EU {{ size }}</span>
                                    <button type="button" @click="removeSize(index)" class="ml-3 text-zinc-500 hover:text-red-500 transition-colors">&times;</button>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.sizes" />
                        </div>

                        <!-- Images -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                            <div>
                                <InputLabel for="image" value="Hero Shot (Primary)" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-3" />
                                <div class="relative group">
                                    <input 
                                        type="file" 
                                        id="image" 
                                        @change="handleMainImage" 
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                                        accept="image/*" 
                                    />
                                    <div class="bg-zinc-900 border-2 border-dashed border-white/5 rounded-[2rem] py-10 px-6 text-center group-hover:border-admin-modern transition-all">
                                        <div v-if="product.image && !form.image" class="mb-4">
                                            <img :src="product.image" class="h-24 object-contain mx-auto drop-shadow-lg" />
                                        </div>
                                        <svg v-else class="h-10 w-10 text-zinc-700 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.414a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-700">{{ product.image ? 'Replace Image' : 'Upload Image' }}</p>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.image" />
                            </div>

                            <div>
                                <InputLabel for="gallery" value="Gallery (Max 4)" class="text-zinc-500 font-black uppercase tracking-widest text-[10px] mb-3" />
                                <div class="relative group">
                                    <input 
                                        type="file" 
                                        id="gallery" 
                                        @change="handleGalleryImages" 
                                        multiple 
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                                        accept="image/*" 
                                    />
                                    <div class="bg-zinc-900 border-2 border-dashed border-white/5 rounded-[2rem] py-10 px-6 text-center group-hover:border-admin-modern transition-all">
                                        <div v-if="product.images && product.images.length > 0 && form.gallery_images.length === 0" class="flex gap-2 justify-center mb-4">
                                            <img v-for="(img, idx) in product.images.slice(0, 3)" :key="idx" :src="img" class="h-10 w-10 object-contain rounded-lg border border-white/10 bg-zinc-800" />
                                        </div>
                                        <svg v-else class="h-10 w-10 text-zinc-700 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-700">Update Gallery</p>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.gallery_images" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-10 border-t border-white/5">
                    <button 
                        type="submit"
                        :disabled="form.processing"
                        class="bg-admin-modern hover:bg-admin-modern/90 text-black px-12 py-5 rounded-[2rem] font-black uppercase tracking-widest text-sm transition-all shadow-lg shadow-admin-modern/20 disabled:opacity-50"
                    >
                        Update Inventory Item
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

