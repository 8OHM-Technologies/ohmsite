<script setup>
import { ref } from 'vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { User, Shield, Store, Lock, Save, Camera } from 'lucide-vue-next';

const props = defineProps({
    settings: Object,
    team: Array
});

const activeTab = ref('profile');
const user = usePage().props.auth.user;

const profileForm = useForm({
    name: user.name,
    email: user.email,
    language: props.settings.store.language || 'English',
});

const securityForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const storeForm = useForm({
    store_name: props.settings.store.name,
    currency: props.settings.store.currency,
    timezone: props.settings.store.timezone,
});

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

const updatePassword = () => {
    securityForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => securityForm.reset(),
    });
};

const updateStore = () => {
    storeForm.post(route('admin.settings.update'), {
        preserveScroll: true,
    });
};

const tabs = [
    { id: 'profile', name: 'Profile', icon: User },
    { id: 'security', name: 'Security', icon: Shield },
    { id: 'store', name: 'Store', icon: Store },
];
</script>

<template>
    <Head title="Admin Settings" />

    <AdminLayout>
        <div class="space-y-10">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <div class="w-2 h-10 bg-admin-modern rounded-full shadow-[0_0_20px_rgba(159,232,112,0.3)]"></div>
                <div>
                    <h1 class="text-4xl font-black text-white tracking-tighter uppercase italic">Control Center</h1>
                    <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px] mt-1">Manage your identity and platform configurations</p>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-10">
                <!-- Navigation Sidebar -->
                <aside class="lg:w-72 shrink-0">
                    <nav class="space-y-2 bg-zinc-900/40 p-4 rounded-[2rem] border border-white/5">
                        <button 
                            v-for="tab in tabs" 
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[activeTab === tab.id ? 'bg-white text-black font-black' : 'text-zinc-500 hover:text-white hover:bg-white/5']"
                            class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl text-[10px] uppercase tracking-widest transition-all group"
                        >
                            <component :is="tab.icon" class="w-4 h-4" />
                            {{ tab.name }}
                        </button>
                    </nav>
                </aside>

                <!-- Content Panel -->
                <main class="flex-1">
                    <div class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-8 sm:p-12 rounded-[3rem] shadow-2xl relative overflow-hidden">
                        
                        <!-- Profile Settings -->
                        <div v-if="activeTab === 'profile'" class="space-y-12 animate-in slide-in-from-right-4 duration-500">
                            <div class="flex items-center gap-8">
                                <div class="relative group">
                                    <div class="w-24 h-24 rounded-[2rem] bg-zinc-800 flex items-center justify-center font-black text-3xl text-white border border-white/10 overflow-hidden shadow-2xl">
                                        <span v-if="!user.profile_photo_url">{{ user.name.charAt(0) }}</span>
                                        <img v-else :src="user.profile_photo_url" class="w-full h-full object-cover" />
                                    </div>
                                    <button class="absolute -bottom-2 -right-2 p-2 bg-white text-black rounded-xl shadow-xl hover:scale-110 transition-all">
                                        <Camera class="w-4 h-4" />
                                    </button>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-black text-white uppercase italic tracking-tight">{{ user.name }}</h3>
                                    <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px] mt-1">{{ user.email }}</p>
                                    <span class="inline-block bg-admin-modern/10 text-admin-modern border border-admin-modern/20 px-3 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest mt-4">Administrative Access</span>
                                </div>
                            </div>

                            <form @submit.prevent="updateProfile" class="space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">Full Identity</label>
                                        <input type="text" v-model="profileForm.name" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 px-6 text-white focus:ring-1 focus:ring-admin-modern/40 transition-all font-bold" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">Email Address</label>
                                        <input type="email" v-model="profileForm.email" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 px-6 text-white focus:ring-1 focus:ring-admin-modern/40 transition-all font-bold" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1 flex items-center gap-2">
                                            <Globe class="w-3 h-3" /> Preferred Language
                                        </label>
                                        <select v-model="profileForm.language" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 px-6 text-white focus:ring-1 focus:ring-admin-modern/40 transition-all font-bold uppercase tracking-widest text-[10px]">
                                            <option value="English">English (United States)</option>
                                            <option value="Albanian">Shqip (Albania)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="pt-8 border-t border-white/5">
                                    <button type="submit" :disabled="profileForm.processing" class="bg-white text-black px-12 py-4 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-admin-modern transition-all shadow-xl disabled:opacity-50">
                                        Save Profile
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Security Settings -->
                        <div v-if="activeTab === 'security'" class="space-y-10 animate-in slide-in-from-right-4 duration-500">
                            <div class="flex items-center gap-4 mb-8">
                                <Lock class="w-6 h-6 text-admin-modern" />
                                <h3 class="text-2xl font-black text-white uppercase italic tracking-tighter leading-none">Authentication Security</h3>
                            </div>
                            
                            <form @submit.prevent="updatePassword" class="space-y-8">
                                <div class="max-w-xl space-y-8">
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">Current Password</label>
                                        <input type="password" v-model="securityForm.current_password" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 px-6 text-white focus:ring-1 focus:ring-admin-modern/40 transition-all" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">New Secret Key</label>
                                        <input type="password" v-model="securityForm.password" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 px-6 text-white focus:ring-1 focus:ring-admin-modern/40 transition-all" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">Confirm New Key</label>
                                        <input type="password" v-model="securityForm.password_confirmation" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 px-6 text-white focus:ring-1 focus:ring-admin-modern/40 transition-all" />
                                    </div>
                                </div>

                                <div class="pt-8 border-t border-white/5">
                                    <button type="submit" :disabled="securityForm.processing" class="bg-white text-black px-12 py-4 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-admin-modern transition-all shadow-xl disabled:opacity-50">
                                        Update Security
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Store Settings -->
                        <div v-if="activeTab === 'store'" class="space-y-10 animate-in slide-in-from-right-4 duration-500">
                            <h3 class="text-2xl font-black text-white uppercase italic tracking-tighter mb-8">Platform Configuration</h3>
                            
                            <form @submit.prevent="updateStore" class="space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">Store Name</label>
                                        <input type="text" v-model="storeForm.store_name" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 px-6 text-white focus:ring-1 focus:ring-admin-modern/40 transition-all font-bold" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">Global Currency</label>
                                        <select v-model="storeForm.currency" class="w-full bg-black/40 border-white/5 rounded-2xl py-4 px-6 text-white focus:ring-1 focus:ring-admin-modern/40 transition-all font-bold uppercase text-[10px]">
                                            <option>USD</option>
                                            <option>EUR</option>
                                            <option>ALL</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="pt-8 border-t border-white/5">
                                    <button type="submit" :disabled="storeForm.processing" class="bg-white text-black px-12 py-4 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-admin-modern transition-all shadow-xl disabled:opacity-50">
                                        Update Store
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </AdminLayout>
</template>
