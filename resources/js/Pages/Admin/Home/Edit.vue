<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    heroSlider: Object,
    aboutUs: Object,
    products: Array,
    brands: Array,
});

const form = useForm({
    _method: 'POST',
    hero_slider: props.heroSlider?.value?.map(s => ({
        ...s,
        brand_ids: s.brand_ids || []
    })) || [],
    hero_images: {}, // Store files here with index as key
    about_us: props.aboutUs?.value || {
        title_line_1: '',
        title_line_2: '',
        description: '',
        stats: [
            { label: 'Years in treg', value: '00' },
            { label: 'Pleasing Costumers', value: '00' }
        ]
    },
    about_us_image: null,
});

const aboutUsPreview = ref(props.aboutUs?.value?.image || null);
const heroPreviews = ref(props.heroSlider?.value?.map(s => s.image) || []);

const handleAboutUsImage = (e) => {
    const file = e.target.files[0];
    form.about_us_image = file;
    if (file) {
        aboutUsPreview.value = URL.createObjectURL(file);
    }
};

const updateSlideFromProduct = (index, productId) => {
    const product = props.products.find(p => p.id === productId);
    if (product) {
        form.hero_slider[index].title = product.name;
        form.hero_slider[index].price = product.sale_price || product.price;
        form.hero_slider[index].image = product.image;
        heroPreviews.value[index] = product.image;
        // Optionally auto-select brand if product has one
        if (product.brand_id && !form.hero_slider[index].brand_ids.includes(product.brand_id)) {
            form.hero_slider[index].brand_ids.push(product.brand_id);
        }
    }
};

const handleHeroImage = (e, index) => {
    const file = e.target.files[0];
    if (file) {
        // Use a stable key if possible, but for now we'll stick to index 
        // and ensure we don't mess up on save.
        form.hero_images[index] = file;
        heroPreviews.value[index] = URL.createObjectURL(file);
    }
};

const addHeroSlide = () => {
    if (form.hero_slider.length < 5) {
        form.hero_slider.push({ 
            product_id: null,
            title: '', 
            price: '', 
            category: '',
            image: '', 
            brand_ids: [],
            is_active: true 
        });
        heroPreviews.value.push(null);
    }
};

const removeHeroSlide = (index) => {
    form.hero_slider.splice(index, 1);
    heroPreviews.value.splice(index, 1);
    
    // Re-index hero_images to match new slider indices
    const newHeroImages = {};
    Object.keys(form.hero_images).forEach(key => {
        const k = parseInt(key);
        if (k < index) {
            newHeroImages[k] = form.hero_images[k];
        } else if (k > index) {
            newHeroImages[k - 1] = form.hero_images[k];
        }
    });
    form.hero_images = newHeroImages;
};

const toggleBrand = (slideIndex, brandId) => {
    const slide = form.hero_slider[slideIndex];
    if (!slide.brand_ids) slide.brand_ids = [];
    
    const idx = slide.brand_ids.indexOf(brandId);
    if (idx > -1) {
        slide.brand_ids.splice(idx, 1);
    } else {
        slide.brand_ids.push(brandId);
    }
};

const submit = () => {
    form.post(route('admin.home.update'), {
        preserveScroll: true,
        onSuccess: () => alert('Saved successfully!'),
        onError: (err) => {
            console.error(err);
            alert('Failed to save. Check console.');
        }
    });
};
</script>

<template>
    <Head title="Edit Home Page" />

    <AdminLayout>
        <div class="flex justify-between items-end mb-10">
            <div>
                <h1 class="text-4xl font-black italic uppercase tracking-tighter mb-2 text-white">Home Page Editor</h1>
                <p class="text-zinc-500 font-bold uppercase tracking-widest text-xs">Customize the storefront appearance and featured collections</p>
            </div>
            <button 
                @click="submit"
                class="bg-admin-modern text-black px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-admin-modern/90 transition-all shadow-xl shadow-admin-modern/10"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Saving...' : 'Save All Changes' }}
            </button>
        </div>

        <div class="space-y-12 pb-20">
            <!-- Hero Slider Settings -->
            <div class="bg-zinc-900/50 overflow-hidden shadow-sm sm:rounded-[2.5rem] p-10 border border-white/5">
                <div class="flex justify-between items-center mb-10">
                    <h3 class="text-2xl font-black text-white uppercase italic tracking-tighter">Hero Slider (Max 5)</h3>
                    <span class="bg-zinc-800/50 text-zinc-500 px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest">{{ form.hero_slider.length }}/5 Slides</span>
                </div>
                
                <div class="space-y-8">
                    <div v-for="(slide, index) in form.hero_slider" :key="index" class="relative p-10 border border-white/5 rounded-[2rem] bg-zinc-800/20 hover:bg-zinc-800/40 transition-all duration-500 group">
                        <button @click="removeHeroSlide(index)" class="absolute top-8 right-8 text-zinc-500 hover:text-red-500 transition-all bg-zinc-900 rounded-xl p-2 shadow-sm border border-white/10">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                            <!-- Product Selection, Title & Price -->
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Select Product Source</label>
                                    <select 
                                        v-model="slide.product_id" 
                                        @change="updateSlideFromProduct(index, slide.product_id)"
                                        class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-xs appearance-none text-white"
                                    >
                                        <option :value="null">-- Manual Entry --</option>
                                        <option v-for="product in products" :key="product.id" :value="product.id">
                                            {{ product.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Display Title</label>
                                    <input v-model="slide.title" type="text" class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-sm text-white" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Price (€)</label>
                                        <input v-model="slide.price" type="text" class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-black text-lg italic text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Label</label>
                                        <input v-model="slide.category" type="text" class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-sm text-white" />
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">Associated Brands</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button 
                                            v-for="brand in brands" 
                                            :key="brand.id"
                                            @click="toggleBrand(index, brand.id)"
                                            type="button"
                                            :class="[
                                                'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border',
                                                slide.brand_ids?.includes(brand.id) 
                                                    ? 'bg-admin-modern text-black border-admin-modern shadow-lg shadow-admin-modern/10' 
                                                    : 'bg-zinc-900 text-zinc-500 border-white/5 hover:border-white/10'
                                            ]"
                                        >
                                            {{ brand.name }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Image Upload -->
                            <div class="space-y-4">
                                <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Slide Media</label>
                                <div 
                                    class="aspect-square rounded-[2.5rem] border-2 border-dashed border-white/5 flex flex-col items-center justify-center relative overflow-hidden group/img cursor-pointer hover:border-admin-modern transition-all bg-zinc-900"
                                    @click="$refs['fileInput' + index][0].click()"
                                >
                                    <img v-if="heroPreviews[index]" :src="heroPreviews[index]" class="h-48 object-contain transform -rotate-12 transition-transform duration-700 group-hover/img:scale-110 drop-shadow-2xl" />
                                    <div v-else class="text-center space-y-2">
                                        <svg class="w-10 h-10 text-zinc-700 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                        <span class="text-[10px] font-black text-zinc-700 uppercase tracking-widest">Click to upload image</span>
                                    </div>
                                    
                                    <input 
                                        :ref="'fileInput' + index" 
                                        type="file" 
                                        class="hidden" 
                                        accept="image/*" 
                                        @change="(e) => handleHeroImage(e, index)"
                                    />
                                    
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm">
                                        <span class="text-white text-[10px] font-black uppercase tracking-[0.3em] border-2 border-white/20 px-6 py-2 rounded-full">Replace Media</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview Helper -->
                            <div class="flex items-center">
                                <div class="bg-black rounded-[2rem] p-8 w-full text-zinc-500 space-y-4 shadow-2xl border border-white/5">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="w-2 h-2 bg-admin-modern rounded-full shadow-[0_0_8px_rgba(159,232,112,0.5)]"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-white italic">Premium Preview</span>
                                    </div>
                                    <p class="text-xs leading-relaxed font-medium">
                                        The title and price will be overlaid on the high-contrast background. Ensure your product image is transparent (PNG) for the best "floating" effect.
                                    </p>
                                    <div class="pt-4 flex items-center gap-2">
                                        <div class="h-1 flex-1 bg-zinc-800 rounded-full overflow-hidden">
                                            <div class="h-full bg-admin-modern w-[60%]"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-zinc-400 italic">HD Quality</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button 
                        v-if="form.hero_slider.length < 5"
                        @click="addHeroSlide" 
                        type="button"
                        class="w-full py-8 border-2 border-dashed border-white/5 rounded-[2rem] text-zinc-500 font-black uppercase tracking-[0.3em] text-xs hover:border-admin-modern hover:text-admin-modern transition-all hover:bg-zinc-800/20 mt-8"
                    >
                        + Initialize New Slide
                    </button>
                </div>
            </div>

            <!-- About Us Settings -->
            <div class="bg-zinc-900/50 overflow-hidden shadow-sm sm:rounded-[2.5rem] p-10 border border-white/5">
                <h3 class="text-2xl font-black text-white uppercase italic mb-10 tracking-tighter">About Us Narrative</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                    <!-- Left: Content -->
                    <div class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Title Line 1 (Primary)</label>
                                <input v-model="form.about_us.title_line_1" type="text" class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-white" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Title Line 2 (Accent)</label>
                                <input v-model="form.about_us.title_line_2" type="text" class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-white" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Brand Story / Description</label>
                            <textarea v-model="form.about_us.description" rows="5" class="w-full border-white/5 border rounded-3xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-medium text-sm leading-relaxed text-white"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-8 pt-4">
                            <div v-for="(stat, index) in form.about_us.stats" :key="index" class="p-8 border border-white/5 rounded-[2rem] bg-zinc-800/20">
                                <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-2">Metric Label</label>
                                <input v-model="stat.label" type="text" class="w-full border-0 border-b border-white/10 focus:border-admin-modern focus:ring-0 transition-all bg-transparent p-0 mb-4 font-bold text-sm text-white" />
                                <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-2">Metric Value</label>
                                <input v-model="stat.value" type="text" class="w-full border-0 border-b border-white/10 focus:border-admin-modern focus:ring-0 transition-all bg-transparent p-0 font-black text-3xl italic tracking-tighter text-admin-modern" />
                            </div>
                        </div>
                    </div>

                    <!-- Right: Image -->
                    <div class="space-y-6">
                        <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Section Visual</label>
                        <div class="relative aspect-[4/5] rounded-[3rem] overflow-hidden border border-white/5 shadow-2xl group">
                            <img v-if="aboutUsPreview" :src="aboutUsPreview" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
                            <div v-else class="w-full h-full bg-zinc-800 flex items-center justify-center text-zinc-600 font-black uppercase tracking-widest italic">No Visual Loaded</div>
                            
                            <label class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center cursor-pointer text-white backdrop-blur-sm">
                                <svg class="w-12 h-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.414a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="font-black uppercase text-xs tracking-[0.3em] border-2 border-white/20 px-8 py-3 rounded-full hover:bg-admin-modern hover:text-black hover:border-admin-modern transition-colors">Update Story Visual</span>
                                <input type="file" @change="handleAboutUsImage" class="hidden" accept="image/*" />
                            </label>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
