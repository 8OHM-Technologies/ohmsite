<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Mail, ChevronLeft, ArrowRight, ShieldCheck } from 'lucide-vue-next';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <div class="min-h-screen bg-black text-white font-sans selection:bg-white selection:text-black flex items-center justify-center p-4">
        <Head title="Forgot Password" />

        <div class="w-full max-w-md">
            <!-- Back to Login -->
            <Link :href="route('login')" class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition-colors mb-12 group">
                <ChevronLeft class="w-4 h-4 transition-transform group-hover:-translate-x-1" />
                Back to Authentication
            </Link>

            <div class="bg-[#111111] border border-white/5 rounded-[2.5rem] p-8 sm:p-12 shadow-2xl relative overflow-hidden group">
                <!-- Decorative Elements -->
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-white opacity-[0.02] blur-[80px] group-hover:opacity-[0.05] transition-opacity duration-1000"></div>
                
                <!-- Header -->
                <div class="mb-10 relative z-10 text-center">
                    <div class="w-16 h-16 bg-zinc-900 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-white/5">
                        <ShieldCheck class="w-8 h-8 text-zinc-400" />
                    </div>
                    <h1 class="text-3xl font-black italic uppercase tracking-tighter leading-none mb-4">Security Recovery</h1>
                    <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest leading-relaxed">
                        Enter your identity email to receive a secure access restoration key.
                    </p>
                </div>

                <!-- Status Alert -->
                <div v-if="status" class="mb-8 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-500 text-[10px] font-black uppercase tracking-widest text-center animate-in fade-in zoom-in duration-500">
                    {{ status }}
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-8 relative z-10">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center ml-1">
                            <label for="email" class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em]">Registered Email</label>
                        </div>
                        <div class="relative group">
                            <Mail class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600 group-focus-within:text-white transition-colors" />
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                class="w-full bg-black border border-white/5 rounded-2xl py-5 pl-14 pr-6 text-sm font-bold text-white focus:ring-1 focus:ring-white/20 focus:border-white/20 transition-all outline-none placeholder:text-zinc-800"
                                placeholder="name@domain.com"
                                required
                                autofocus
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-1 animate-pulse">{{ form.errors.email }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-white text-black py-5 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] hover:bg-zinc-200 transition-all shadow-xl active:scale-95 disabled:opacity-50 flex items-center justify-center gap-3 group"
                    >
                        {{ form.processing ? 'Dispatching...' : 'Dispatch Reset Key' }}
                        <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                    </button>
                </form>
            </div>

            <!-- Brand Footer -->
            <div class="mt-12 text-center opacity-20 group">
                <img src="/assets/images/logo-white.png" class="h-6 mx-auto grayscale invert" />
            </div>
        </div>
    </div>
</template>
