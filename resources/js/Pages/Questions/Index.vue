<script setup lang="ts">
import QuestionCard from '@/Components/QuestionCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Question {
    id: number;
    content: string;
    difficulty: 'junior' | 'mid' | 'senior';
    source: 'ai_generated' | 'user_created';
    expected_answer: string | null;
    expected_keywords: string[];
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedQuestions {
    data: Question[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
        links: PaginationLink[];
    };
}

const props = defineProps<{
    questions: PaginatedQuestions;
    has_api_key: boolean;
}>();

const generating = ref(false);
const generateError = ref<string | null>(null);

async function generate(): Promise<void> {
    generating.value = true;
    generateError.value = null;
    try {
        await window.axios.post('/api/questions/generate');
        router.reload({ only: ['questions'] });
    } catch (err: unknown) {
        const axiosErr = err as { response?: { data?: { message?: string } } };
        generateError.value = axiosErr.response?.data?.message ?? t('questions.generation_failed');
    } finally {
        generating.value = false;
    }
}

const ENTITY_MAP: Record<string, string> = {
    '&laquo;': '«',
    '&raquo;': '»',
    '&lsaquo;': '‹',
    '&rsaquo;': '›',
    '&hellip;': '…',
    '&amp;': '&',
};

function decodeLabel(label: string | null | undefined): string {
    if (!label) return '';
    return label.replace(/&[a-z]+;/gi, (entity) => ENTITY_MAP[entity] ?? entity).replace(/\s+/g, ' ').trim();
}
</script>

<template>
    <Head :title="t('questions.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-bold tracking-tight bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-300">
                    {{ t('questions.header') }}
                </h2>
                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('study.session')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-500/25 transition-all duration-200 hover:from-emerald-600 hover:to-teal-700 hover:scale-[1.02] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                    >
                        {{ t('questions.study_session') }}
                    </Link>
                    <button
                        type="button"
                        :disabled="generating || !has_api_key"
                        @click="generate"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/50 bg-white/70 px-4 py-2 text-sm font-semibold text-emerald-700 backdrop-blur-sm transition-all duration-200 hover:border-emerald-500 hover:bg-emerald-50/80 hover:scale-[1.02] active:scale-[0.97] disabled:opacity-50 disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:border-emerald-600/50 dark:bg-slate-800/50 dark:text-emerald-300 dark:hover:bg-emerald-900/20"
                    >
                        <svg v-if="generating" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        {{ generating ? t('questions.generating') : t('questions.generate') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">

                <!-- No API key warning -->
                <div
                    v-if="!has_api_key"
                    class="rounded-xl border border-amber-200/60 bg-amber-50/60 p-4 text-sm text-amber-800 backdrop-blur-sm dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-200"
                >
                    {{ t('questions.no_api_key') }}
                    <Link :href="route('settings.edit')" class="font-semibold underline decoration-amber-400 hover:text-amber-900 dark:hover:text-amber-100">{{ t('questions.go_to_settings') }}</Link>{{ t('questions.go_to_settings_suffix') }}
                </div>

                <!-- Error -->
                <div
                    v-if="generateError"
                    class="rounded-xl border border-red-200/60 bg-red-50/60 px-4 py-3 text-sm text-red-700 backdrop-blur-sm dark:border-red-800/40 dark:bg-red-950/20 dark:text-red-300"
                >
                    {{ generateError }}
                </div>

                <!-- Empty state -->
                <div
                    v-if="questions.data.length === 0"
                    class="rounded-2xl border border-dashed border-gray-200/80 bg-white/40 px-6 py-16 text-center backdrop-blur-sm dark:border-gray-700/60 dark:bg-slate-900/20"
                >
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100/80 text-2xl dark:bg-slate-800/60">
                        📋
                    </div>
                    <p class="font-medium text-gray-600 dark:text-gray-400">{{ t('questions.empty_state') }}</p>
                    <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
                        {{ t('questions.empty_state_hint_prefix') }}
                        <span class="font-semibold text-gray-600 dark:text-gray-300">{{ t('questions.empty_state_hint_button') }}</span>{{ t('questions.empty_state_hint_suffix') }}
                    </p>
                </div>

                <!-- Questions list -->
                <div v-else class="space-y-4">
                    <QuestionCard
                        v-for="q in questions.data"
                        :key="q.id"
                        :question="q"
                    />
                </div>

                <!-- Pagination -->
                <div v-if="questions.meta.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-1.5">
                    <template v-for="link in questions.meta.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                            :class="link.active
                                ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-sm shadow-emerald-500/25'
                                : 'border border-gray-200/60 bg-white/70 text-gray-600 backdrop-blur-sm hover:border-emerald-400/60 hover:text-emerald-700 dark:border-gray-700/60 dark:bg-slate-800/50 dark:text-gray-300 dark:hover:text-emerald-300'"
                        >{{ decodeLabel(link.label) }}</Link>
                        <span
                            v-else
                            class="rounded-lg px-3 py-1.5 text-sm text-gray-300 dark:text-gray-600"
                        >{{ decodeLabel(link.label) }}</span>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
