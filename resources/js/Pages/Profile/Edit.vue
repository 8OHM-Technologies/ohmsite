<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    auth: {
        type: Object,
    }
});
</script>

<template>
    <MainLayout :auth="auth" title="Profile">
        <div class="max-w-7xl mx-auto">
            <div class="mb-16">
                <h1 class="text-7xl font-black uppercase italic tracking-tighter text-white leading-none">Settings</h1>
                <p class="text-zinc-500 font-black uppercase tracking-widest text-xs mt-4">Manage your account and preferences</p>
            </div>

            <div class="space-y-10">
                <!-- Profile Information -->
                <div class="bg-zinc-900 rounded-[3rem] p-10 lg:p-16 border border-white/5 shadow-2xl">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-2xl"
                    />
                </div>

                <!-- Update Password -->
                <div class="bg-zinc-900 rounded-[3rem] p-10 lg:p-16 border border-white/5 shadow-2xl">
                    <UpdatePasswordForm class="max-w-2xl" />
                </div>

                <!-- Delete User -->
                <div class="bg-zinc-900 rounded-[3rem] p-10 lg:p-16 border border-rose-500/10 shadow-2xl">
                    <DeleteUserForm class="max-w-2xl" />
                </div>
            </div>
        </div>
    </MainLayout>
</template>
