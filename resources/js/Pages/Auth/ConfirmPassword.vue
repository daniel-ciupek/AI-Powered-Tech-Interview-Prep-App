<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.confirm.title')" />

        <div class="mb-6">
            <h1 class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-2xl font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
                {{ t('auth.confirm.title') }}
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ t('auth.confirm.intro') }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="password" :value="t('auth.fields.password')" class="mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                :disabled="form.processing"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                {{ t('auth.confirm.submit') }}
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
