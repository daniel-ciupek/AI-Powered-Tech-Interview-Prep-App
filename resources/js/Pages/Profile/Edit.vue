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
                <div class="rounded-3xl border border-white/40 bg-white/60 p-6 shadow-lg shadow-emerald-100/40 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/40 dark:shadow-emerald-950/30">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <div class="rounded-3xl border border-white/40 bg-white/60 p-6 shadow-lg shadow-emerald-100/40 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/40 dark:shadow-emerald-950/30">
                    <UpdatePasswordForm />
                </div>

                <div class="rounded-3xl border border-red-100/60 bg-white/60 p-6 shadow-lg shadow-red-100/20 backdrop-blur-md dark:border-red-900/20 dark:bg-slate-900/40 dark:shadow-red-950/10">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
