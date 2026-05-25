<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.verify.title')" />

        <div class="mb-6">
            <h1 class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-2xl font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
                {{ t('auth.verify.title') }}
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ t('auth.verify.intro') }}
            </p>
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-5 rounded-xl border border-emerald-200/60 bg-emerald-50/80 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300"
        >
            {{ t('auth.verify.resent_notice') }}
        </div>

        <form @submit.prevent="submit">
            <div class="flex items-center justify-between gap-4">
                <PrimaryButton
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    {{ t('auth.verify.resend') }}
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm text-gray-500 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    {{ t('auth.verify.logout') }}
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
