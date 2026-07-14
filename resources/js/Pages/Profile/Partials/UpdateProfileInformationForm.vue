<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    first_name: user.first_name || '',
    last_name: user.last_name || '',
    company_name: user.company_name || '',
    phone: user.phone || '',
    country: user.country || 'South Africa',
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-2xl font-black uppercase tracking-tighter text-primary">
                Profile Information
            </h2>

            <p class="mt-2 text-[10px] font-black uppercase tracking-widest text-zinc-500">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-10 space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <InputLabel for="first_name" value="First Name" />

                    <TextInput id="first_name" type="text" class="mt-1 block w-full" v-model="form.first_name" required autofocus
                        autocomplete="given-name" />

                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <div>
                    <InputLabel for="last_name" value="Last Name" />

                    <TextInput id="last_name" type="text" class="mt-1 block w-full" v-model="form.last_name" required
                        autocomplete="family-name" />

                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <InputLabel for="company_name" value="Company Name" />

                    <TextInput id="company_name" type="text" class="mt-1 block w-full" v-model="form.company_name"
                        autocomplete="organization" />

                    <InputError class="mt-2" :message="form.errors.company_name" />
                </div>

                <div>
                    <InputLabel for="phone" value="Phone" />

                    <TextInput id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" required
                        autocomplete="tel" />

                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>
            </div>

            <div>
                <InputLabel for="country" value="Country" />

                <TextInput id="country" type="text" class="mt-1 block w-full" v-model="form.country"
                    autocomplete="country-name" />

                <InputError class="mt-2" :message="form.errors.country" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                    autocomplete="username" />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-zinc-400 font-bold uppercase">
                    Your email address is unverified.
                    <Link :href="route('verification.send')" method="post" as="button"
                        class="rounded-md text-sm text-white underline hover:text-zinc-300 focus:outline-none">
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-black uppercase text-emerald-500">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-6">
                <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful"
                        class="text-xs font-black uppercase tracking-widest text-emerald-500">
                        Saved successfully
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
