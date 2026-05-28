<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head :title="t('profile.title')" />

    <AuthenticatedLayout :minimal="true">
        <template #header>
            <h2 class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-xl font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
                {{ t('profile.title') }}
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div class="glass-card animate-fade-in-up rounded-3xl p-6" style="animation-delay: 60ms;">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <div class="glass-card animate-fade-in-up rounded-3xl p-6" style="animation-delay: 120ms;">
                    <UpdatePasswordForm />
                </div>

                <div class="glass-card animate-fade-in-up rounded-3xl p-6" style="animation-delay: 180ms; border-color: rgba(239,68,68,0.20);">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
