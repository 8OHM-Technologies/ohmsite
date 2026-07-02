<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen bg-black text-white font-sans flex overflow-hidden">
        <!-- Left Side: Image & Info (Perfectly Matched with Register) -->
        <div class="hidden lg:flex lg:w-1/2 relative">
            <img 
                src="/assets/images/loginregister.jpg" 
                alt="Auth Background" 
                class="absolute inset-0 w-full h-full object-cover opacity-60"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent"></div>
            
            <div class="relative z-10 p-20 flex flex-col justify-between w-full h-full">
                <!-- Logo -->
                <Link :href="route('home')">
                    <img src="/assets/images/logo-white.png" alt="Logo" class="h-10" />
                </Link>

                <!-- Text Content -->
                <div class="space-y-6">
                    <h1 class="text-6xl font-black italic uppercase tracking-tighter leading-none">
                        Step into style.<br/>Step into you.
                    </h1>
                    <p class="text-zinc-400 font-bold uppercase tracking-widest text-xs max-w-md">
                        Discover the latest sneakers and exclusive drops. High quality, popular choices, unique style.
                    </p>
                    
                    <!-- Badges -->
                    <div class="flex items-center space-x-8 pt-10">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-white/80">100% Authentic</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-white/80">Easy Returns</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-white/80">Fast Delivery</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Form Container (Perfectly Matched with Register) -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-10 bg-zinc-950 overflow-y-auto h-screen">
            <div class="max-w-md w-full space-y-10 py-10">
                <div class="text-center lg:text-left">
                    <h2 class="text-4xl font-black italic uppercase tracking-tighter">Welcome back</h2>
                    <p class="text-zinc-500 font-bold uppercase tracking-widest text-[10px] mt-2">Log in to your account to continue</p>
                </div>

                <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email -->
                    <div class="space-y-2">
                        <label for="email" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 ml-1">Email Address</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-zinc-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <input 
                                id="email"
                                type="email"
                                v-model="form.email"
                                placeholder="Enter your email"
                                class="w-full bg-zinc-900/50 border border-white/5 rounded-2xl py-4 pl-14 pr-6 text-sm font-bold focus:ring-1 focus:ring-white/20 focus:border-white/20 transition-all placeholder:text-zinc-700"
                                required
                                autofocus
                                autocomplete="username"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-1 ml-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center ml-1">
                            <label for="password" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Password</label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-[10px] font-black uppercase tracking-widest text-zinc-600 hover:text-white transition-colors"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-zinc-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </span>
                            <input 
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                placeholder="Enter your password"
                                class="w-full bg-zinc-900/50 border border-white/5 rounded-2xl py-4 pl-14 pr-14 text-sm font-bold focus:ring-1 focus:ring-white/20 focus:border-white/20 transition-all placeholder:text-zinc-700"
                                required
                                autocomplete="current-password"
                            />
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-5 top-1/2 -translate-y-1/2 text-zinc-600 hover:text-white transition-colors"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 012.13-3.501m3.187-3.187A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21m-2.101-2.101L3 3" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-1 ml-1">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="remember" v-model="form.remember" class="w-4 h-4 bg-zinc-900 border-white/10 rounded focus:ring-0 text-white" />
                        <label for="remember" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 ml-3 cursor-pointer">Remember me</label>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full bg-white text-black py-4 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-zinc-200 transition-all active:scale-[0.98] shadow-2xl"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    >
                        Log In
                    </button>
                </form>

                <!-- Footer Link -->
                <div class="pt-4">
                    <p class="text-center text-[10px] font-black uppercase tracking-widest text-zinc-600">
                        Don't have an account? 
                        <Link :href="route('register')" class="text-white hover:underline transition-all">Register</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-sans {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
/* Custom Scrollbar for form container */
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
</style>
