<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Lock, Mail, ArrowRight, ShieldAlert, KeyRound } from 'lucide-vue-next';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-black text-white font-sans selection:bg-white selection:text-black flex items-center justify-center p-4">
        <Head title="Reset Password" />

        <div class="w-full max-w-md">
            <div class="bg-[#111111] border border-white/5 rounded-[2.5rem] p-8 sm:p-12 shadow-2xl relative overflow-hidden group">
                <!-- Decorative Elements -->
                <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-white opacity-[0.02] blur-[80px] group-hover:opacity-[0.05] transition-opacity duration-1000"></div>
                
                <!-- Header -->
                <div class="mb-10 relative z-10 text-center">
                    <div class="w-16 h-16 bg-zinc-900 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-white/5 shadow-xl group-hover:border-admin-modern/30 transition-all duration-700">
                        <KeyRound class="w-8 h-8 text-admin-modern" />
                    </div>
                    <h1 class="text-3xl font-black italic uppercase tracking-tighter leading-none mb-4">Reset Authentication</h1>
                    <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest leading-relaxed">
                        Establish a new secure secret key for your global identity.
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-8 relative z-10">
                    <!-- Email (Read Only Visual) -->
                    <div class="space-y-3 opacity-60">
                        <label class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">Account Identity</label>
                        <div class="relative">
                            <Mail class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" />
                            <input
                                type="email"
                                :value="form.email"
                                readonly
                                class="w-full bg-zinc-900/50 border border-white/5 rounded-2xl py-5 pl-14 pr-6 text-sm font-bold text-zinc-400 outline-none cursor-not-allowed"
                            />
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="space-y-3">
                        <label for="password" class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">New Secret Key</label>
                        <div class="relative group">
                            <Lock class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600 group-focus-within:text-white transition-colors" />
                            <input
                                id="password"
                                type="password"
                                v-model="form.password"
                                class="w-full bg-black border border-white/5 rounded-2xl py-5 pl-14 pr-6 text-sm font-bold text-white focus:ring-1 focus:ring-white/20 focus:border-white/20 transition-all outline-none"
                                required
                                autofocus
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div class="space-y-3">
                        <label for="password_confirmation" class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em] ml-1">Verify Key</label>
                        <div class="relative group">
                            <ShieldAlert class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600 group-focus-within:text-white transition-colors" />
                            <input
                                id="password_confirmation"
                                type="password"
                                v-model="form.password_confirmation"
                                class="w-full bg-black border border-white/5 rounded-2xl py-5 pl-14 pr-6 text-sm font-bold text-white focus:ring-1 focus:ring-white/20 focus:border-white/20 transition-all outline-none"
                                required
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-white text-black py-5 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] hover:bg-admin-modern transition-all shadow-xl active:scale-95 disabled:opacity-50 flex items-center justify-center gap-3 group"
                    >
                        {{ form.processing ? 'Rebuilding...' : 'Update Authentication' }}
                        <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                    </button>
                </form>
            </div>

            <!-- Brand Footer -->
            <div class="mt-12 text-center opacity-20">
                <img src="/assets/images/logo-white.png" class="h-6 mx-auto grayscale invert" />
            </div>
        </div>
    </div>
</template>
