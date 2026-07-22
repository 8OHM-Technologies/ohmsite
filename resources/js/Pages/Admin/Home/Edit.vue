<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    heroSlider: Object,
    aboutUs: Object,
    roadmap: Object,
    sectionHeaders: Object,
    products: Array,
});

const defaultRoadmap = [
    { status: 'Live', date: 'Current', title: 'CCMA Arbitration Awards', description: 'Comprehensive CCMA Awards & Judgment records.', icon: 'ph-check-circle', iconClass: 'text-green-500' },
    { status: 'Upcoming', date: 'Q3 2026', title: 'SA Labour Court Case Law', description: 'Comprehensive case law from the Labour and Labour Appeal Courts.', icon: 'ph-spinner', iconClass: 'text-blue-500' },
    { status: 'Upcoming', date: 'Q4 2026', title: 'SA High Court Case Law', description: 'Comprehensive case law from the High Court, Supreme Court of Appeal, and the Constitutional Court.', icon: 'ph-calendar-blank', iconClass: 'text-orange-500' },
    { status: 'Planned', date: 'Q1 2027', title: 'Tribunals and Other Courts Case Law', description: 'Comprehensive case law from the various SA Tribunals, as well as smaller courts including the Equality, Electoral and Tax Courts.', icon: 'ph-rocket-launch', iconClass: 'text-yellow-500' },
    { status: 'Planned', date: '2027', title: 'Legislative Expansion', description: 'Expansion of our Legal dataset to include acts, bills, government gazettes and other legislative documents.', icon: 'ph-rocket-launch', iconClass: 'text-yellow-500' },
    { status: 'Ultimate Goal', date: '2027', title: 'LaybaLaw AI', description: 'South African Labour Law & CCMA AI Assistant - Help average South Africans navigate disputes with their employers.', icon: 'ph-robot', iconClass: 'text-red-500' },
];

const defaultSectionHeaders = {
    services: {
        eyebrow: 'Data Solutions and IT Consulting',
        title: 'SA Labour Law Datasets & Analytics for Legal, HR, and Data Professionals',
        subtitle: 'Access structured South African public legal data (currently CCMA & Labour Courts, with more arriving soon) via download and API, or explore trends visually using our analytics dashboard.',
    },
    philosophy: {
        eyebrow: 'Why Infinity Ohm?',
        title: 'Our Philosophy',
        subtitle: 'Strategic engineering, absolute data ownership, and open standards.',
    },
    ohmbase: {
        eyebrow: 'DIY Smart Home Blueprints',
        title: 'OhmBase',
        subtitle: 'The business data architectures we build are born from our philosophy of absolute digital sovereignty. Explore our open-source blueprints and Smart Home/IoT hardware store, designed to help individuals build their own self-hosted, cloud-free smart home ecosystems.',
    },
    faq: {
        eyebrow: 'FAQs',
        title: 'Frequently Asked Questions',
        subtitle: '',
    },
    contact: {
        eyebrow: 'Contact',
        title: 'Enquire Now',
        subtitle: 'Have questions about our South African public data feeds, custom analytics dashboards, or DIY hardware waitlists? Reach out below.',
    },
};

const iconOptions = [
    { value: 'ph-check-circle', label: '✓ Check Circle (Live)' },
    { value: 'ph-spinner', label: '⟳ Spinner (In Progress)' },
    { value: 'ph-calendar-blank', label: '📅 Calendar (Scheduled)' },
    { value: 'ph-rocket-launch', label: '🚀 Rocket (Planned)' },
    { value: 'ph-robot', label: '🤖 Robot (AI)' },
    { value: 'ph-star', label: '⭐ Star' },
    { value: 'ph-lightning', label: '⚡ Lightning' },
    { value: 'ph-flag', label: '🚩 Flag' },
];

const colorOptions = [
    { value: 'text-green-500', label: 'Green' },
    { value: 'text-blue-500', label: 'Blue' },
    { value: 'text-orange-500', label: 'Orange' },
    { value: 'text-yellow-500', label: 'Yellow' },
    { value: 'text-red-500', label: 'Red' },
    { value: 'text-purple-500', label: 'Purple' },
    { value: 'text-cyan-500', label: 'Cyan' },
];

const form = useForm({
    _method: 'POST',
    hero_slider: props.heroSlider?.value || [],
    hero_images: {},
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
    roadmap: props.roadmap?.value || defaultRoadmap,
});

const activeRoadmapIndex = ref(0);
const activeSectionKey = ref('services');

const toggleRoadmapItem = (index) => {
    activeRoadmapIndex.value = activeRoadmapIndex.value === index ? null : index;
};

const toggleSectionHeader = (key) => {
    activeSectionKey.value = activeSectionKey.value === key ? null : key;
};

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

const addRoadmapItem = () => {
    form.roadmap.push({
        status: 'Planned',
        date: '',
        title: '',
        description: '',
        icon: 'ph-rocket-launch',
        iconClass: 'text-yellow-500',
    });
    activeRoadmapIndex.value = form.roadmap.length - 1;
};

const removeRoadmapItem = (index) => {
    form.roadmap.splice(index, 1);
    if (activeRoadmapIndex.value === index) {
        activeRoadmapIndex.value = form.roadmap.length > 0 ? Math.max(0, index - 1) : null;
    } else if (activeRoadmapIndex.value > index) {
        activeRoadmapIndex.value--;
    }
};

const moveRoadmapItem = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= form.roadmap.length) return;
    const items = form.roadmap;
    [items[index], items[newIndex]] = [items[newIndex], items[index]];
    if (activeRoadmapIndex.value === index) {
        activeRoadmapIndex.value = newIndex;
    } else if (activeRoadmapIndex.value === newIndex) {
        activeRoadmapIndex.value = index;
    }
};

// toggleBrand removed

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
                <h1 class="text-4xl font-black uppercase tracking-tighter mb-2 text-white">Home Page Editor</h1>
                <p class="text-zinc-500 font-bold uppercase tracking-widest text-xs">Customize the storefront appearance
                    and featured collections</p>
            </div>
            <button @click="submit"
                class="btn-primary text-black px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-admin-modern/90 transition-all shadow-xl shadow-admin-modern/10"
                :disabled="form.processing">
                {{ form.processing ? 'Saving...' : 'Save All Changes' }}
            </button>
        </div>

        <div class="space-y-12 pb-20">  
            <!-- Roadmap Carousel Settings -->
            <div class="bg-zinc-900/50 overflow-hidden shadow-sm sm:rounded-[2.5rem] p-10 border border-white/5">
                <div class="flex justify-between items-center mb-10">
                    <h3 class="text-2xl font-black text-white uppercase tracking-tighter">Roadmap Carousel</h3>
                    <span
                        class="bg-zinc-800/50 text-zinc-500 px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest">{{
                            form.roadmap.length }} Items</span>
                </div>

                <div class="space-y-6">
                    <div v-for="(item, index) in form.roadmap" :key="index"
                        class="border border-white/5 rounded-[2rem] bg-zinc-800/10 hover:bg-zinc-800/20 transition-all duration-300 overflow-hidden"
                        :class="{ 'bg-zinc-800/30 border-white/10 ring-1 ring-white/5': activeRoadmapIndex === index }">

                        <!-- Accordion Header -->
                        <div @click="toggleRoadmapItem(index)"
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-8 cursor-pointer select-none">
                            <div class="flex items-center gap-4 flex-wrap sm:flex-nowrap min-w-0">
                                <!-- Item number badge -->
                                <span class="bg-zinc-800 text-zinc-400 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shrink-0">
                                    Item {{ index + 1 }}
                                </span>
                                <!-- Icon preview -->
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-black/40 border border-white/5 shrink-0">
                                    <i class="ph-fill text-lg" :class="[item.icon || 'ph-rocket-launch', item.iconClass || 'text-yellow-500']"></i>
                                </div>
                                <!-- Title and Subtitle -->
                                <div class="flex flex-col min-w-0">
                                    <span class="text-white font-black uppercase tracking-tight text-sm truncate">
                                        {{ item.title || 'New Roadmap Item' }}
                                    </span>
                                    <span class="text-zinc-500 text-xs font-semibold mt-0.5">
                                        {{ item.status || 'Draft' }} &bull; {{ item.date || 'TBD' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Right Side: Controls and Chevron -->
                            <div class="flex items-center gap-3 shrink-0 self-end sm:self-auto">
                                <!-- Controls -->
                                <div class="flex items-center gap-1.5 bg-black/20 p-1 rounded-xl border border-white/5">
                                    <button @click.stop="moveRoadmapItem(index, -1)" :disabled="index === 0" type="button"
                                        class="text-zinc-500 hover:text-white transition-all bg-zinc-900 hover:bg-zinc-800 rounded-lg p-2 disabled:opacity-30 disabled:cursor-not-allowed border border-white/5"
                                        title="Move Up">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                    <button @click.stop="moveRoadmapItem(index, 1)" :disabled="index === form.roadmap.length - 1" type="button"
                                        class="text-zinc-500 hover:text-white transition-all bg-zinc-900 hover:bg-zinc-800 rounded-lg p-2 disabled:opacity-30 disabled:cursor-not-allowed border border-white/5"
                                        title="Move Down">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <button @click.stop="removeRoadmapItem(index)" type="button"
                                        class="text-zinc-500 hover:text-red-500 transition-all bg-zinc-900 hover:bg-zinc-800 rounded-lg p-2 border border-white/5"
                                        title="Remove">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <!-- Chevron -->
                                <div class="w-8 h-8 rounded-lg bg-black/20 border border-white/5 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-zinc-400 transform transition-transform duration-300"
                                        :class="{ 'rotate-180': activeRoadmapIndex === index }"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Content -->
                        <div v-show="activeRoadmapIndex === index" 
                            class="px-8 pb-8 border-t border-white/5 pt-8 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Title</label>
                                    <input v-model="item.title" type="text"
                                        class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-sm text-white" />
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Status</label>
                                    <input v-model="item.status" type="text" placeholder="e.g. Live, Upcoming, Planned"
                                        class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-sm text-white" />
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Date</label>
                                    <input v-model="item.date" type="text" placeholder="e.g. Q3 2026, Current, 2027"
                                        class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-sm text-white" />
                                </div>
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label
                                        class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Description</label>
                                    <textarea v-model="item.description" rows="2"
                                        class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-medium text-sm leading-relaxed text-white"></textarea>
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Icon</label>
                                    <select v-model="item.icon"
                                        class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-xs appearance-none text-white">
                                        <option v-for="opt in iconOptions" :key="opt.value" :value="opt.value">
                                            {{ opt.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Icon
                                        Color</label>
                                    <select v-model="item.iconClass"
                                        class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-xs appearance-none text-white">
                                        <option v-for="opt in colorOptions" :key="opt.value" :value="opt.value">
                                            {{ opt.label }}
                                        </option>
                                    </select>
                                </div>
                                <!-- Live preview -->
                                <div class="flex items-center">
                                    <div
                                        class="bg-black rounded-2xl p-5 w-full border border-white/5 flex items-center gap-3">
                                        <i class="ph-fill text-lg" :class="[item.icon, item.iconClass]"></i>
                                        <span class="text-zinc-400 text-xs font-bold">{{ item.status }} &bull; {{
                                            item.date }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button @click="addRoadmapItem" type="button"
                        class="w-full py-8 border-2 border-dashed border-white/5 rounded-[2rem] text-zinc-500 font-black uppercase tracking-[0.3em] text-xs hover:border-admin-modern hover:text-admin-modern transition-all hover:bg-zinc-800/20 mt-4">
                        + Add Roadmap Item
                    </button>
                </div>
            </div>

            <!-- Section Headers Settings -->
            <div class="bg-zinc-900/50 overflow-hidden shadow-sm sm:rounded-[2.5rem] p-10 border border-white/5">
                <h3 class="text-2xl font-black text-white uppercase mb-4 tracking-tighter">Section Headers</h3>
                <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-10">Edit the headings and
                    subtitles shown above each section on the homepage</p>

                <div class="space-y-8">
                    <div v-for="(section, key) in form.section_headers" :key="key"
                        class="border border-white/5 rounded-[2rem] bg-zinc-800/10 hover:bg-zinc-800/20 transition-all duration-300 overflow-hidden"
                        :class="{ 'bg-zinc-800/30 border-white/10 ring-1 ring-white/5': activeSectionKey === key }">

                        <!-- Accordion Header -->
                        <div @click="toggleSectionHeader(key)"
                            class="flex items-center justify-between p-8 cursor-pointer select-none">
                            <div class="flex items-center gap-4 min-w-0">
                                <!-- Glowing status dot -->
                                <div class="w-2.5 h-2.5 bg-admin-modern rounded-full shrink-0 shadow-[0_0_8px_rgba(159,232,112,0.5)]"></div>
                                <!-- Section Label/Name -->
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-zinc-500">Section Header Config</span>
                                    <span class="text-white font-black uppercase tracking-tight text-sm mt-0.5 capitalize">
                                        {{ key }} Section
                                    </span>
                                </div>
                                <!-- Quick title preview -->
                                <span class="hidden md:inline-block text-zinc-500 text-xs font-semibold max-w-md truncate ml-4 border-l border-white/10 pl-4">
                                    {{ section.title || '(No Title)' }}
                                </span>
                            </div>

                            <!-- Chevron -->
                            <div class="w-8 h-8 rounded-lg bg-black/20 border border-white/5 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-zinc-400 transform transition-transform duration-300"
                                    :class="{ 'rotate-180': activeSectionKey === key }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Accordion Content -->
                        <div v-show="activeSectionKey === key"
                            class="px-8 pb-8 border-t border-white/5 pt-8 space-y-6">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Eyebrow
                                    Badge Text</label>
                                <input v-model="section.eyebrow" type="text"
                                    class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-sm text-white" />
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Section
                                    Title (H2)</label>
                                <input v-model="section.title" type="text"
                                    class="w-full border-white/5 border rounded-2xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-bold text-white" />
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Section
                                    Subtitle (Paragraph)</label>
                                <textarea v-model="section.subtitle" rows="3"
                                    class="w-full border-white/5 border rounded-3xl py-4 px-6 focus:ring-2 focus:ring-admin-modern focus:border-admin-modern transition-all bg-zinc-900 font-medium text-sm leading-relaxed text-white"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
