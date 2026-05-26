<script setup lang="ts">
import FreeTextTagInput from '@/Components/FreeTextTagInput.vue';
import QuestionCard from '@/Components/QuestionCard.vue';
import QuestionChat from '@/Components/QuestionChat.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Question {
    id: number;
    content: string;
    difficulty: 'junior' | 'mid' | 'senior';
    source: 'ai_generated' | 'user_created';
    expected_answer: string | null;
    expected_keywords: string[];
    topic_tags: string[];
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
    selected_tags: string[];
    tag_suggestions: string[];
}>();

const generating = ref(false);
const generateError = ref<string | null>(null);
const selectedId = ref<number | null>(props.questions.data[0]?.id ?? null);
const mobileShowDetail = ref(false);

// Local copy of active filter tags — synced back to URL on change
const filterTags = ref<string[]>([...props.selected_tags]);

const selectedQuestion = computed(
    () => props.questions.data.find((q) => q.id === selectedId.value) ?? null,
);

function selectQuestion(q: Question): void {
    selectedId.value = q.id;
    mobileShowDetail.value = true;
}

function applyTagFilter(tags: string[]): void {
    filterTags.value = tags;
    router.visit(route('questions.index'), {
        data: tags.length ? { tags } : {},
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: () => {
            selectedId.value = props.questions.data[0]?.id ?? null;
        },
    });
}

async function generate(): Promise<void> {
    generating.value = true;
    generateError.value = null;
    try {
        await window.axios.post('/api/questions/generate', {
            tags: filterTags.value,
        });
        router.reload({ only: ['questions', 'tag_suggestions'] });
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

const difficultyBadge = {
    junior: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    mid: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    senior: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
} as const;
</script>

<template>
    <Head :title="t('questions.title')" />

    <AuthenticatedLayout :minimal="true">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-xl font-bold tracking-tight text-transparent dark:from-white dark:to-gray-300">
                    {{ t('questions.header') }}
                </h2>
                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('study.session')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-500/25 transition-all duration-200 hover:scale-[1.02] hover:from-emerald-600 hover:to-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                    >
                        {{ t('questions.study_session') }}
                    </Link>
                    <button
                        type="button"
                        :disabled="generating || !has_api_key"
                        @click="generate"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/50 bg-white/70 px-4 py-2 text-sm font-semibold text-emerald-700 backdrop-blur-sm transition-all duration-200 hover:scale-[1.02] hover:border-emerald-500 hover:bg-emerald-50/80 active:scale-[0.97] disabled:opacity-50 disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:border-emerald-600/50 dark:bg-slate-800/50 dark:text-emerald-300 dark:hover:bg-emerald-900/20"
                    >
                        <svg v-if="generating" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                        {{ generating ? t('questions.generating') : t('questions.generate') }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Master-detail layout -->
        <div class="flex h-[calc(100vh-3.5rem)] overflow-hidden">

            <!-- Left panel: question list -->
            <aside
                class="flex w-72 flex-none flex-col border-r border-emerald-500/15 bg-white/35 backdrop-blur-2xl dark:border-emerald-400/10 dark:bg-slate-900/35"
                :class="mobileShowDetail ? 'hidden md:flex' : 'flex'"
            >
                <!-- Tag filter -->
                <div class="border-b border-emerald-500/12 px-3 py-3 dark:border-emerald-400/8">
                    <FreeTextTagInput
                        :model-value="filterTags"
                        :suggestions="tag_suggestions"
                        :max-tags="5"
                        @update:model-value="applyTagFilter"
                    />
                </div>

                <!-- List header with count -->
                <div class="flex items-center justify-between border-b border-emerald-500/12 px-4 py-2.5 dark:border-emerald-400/8">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        {{ questions.meta.total }} {{ t('questions.list_count') }}
                    </span>
                    <button
                        v-if="filterTags.length > 0"
                        type="button"
                        class="text-xs text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200"
                        @click="applyTagFilter([])"
                    >
                        {{ t('questions.clear_filter') }}
                    </button>
                </div>

                <!-- No API key warning -->
                <div
                    v-if="!has_api_key"
                    class="border-b border-amber-200/60 bg-amber-50/60 px-4 py-2.5 text-xs text-amber-800 dark:border-amber-800/30 dark:bg-amber-900/20 dark:text-amber-300"
                >
                    {{ t('questions.no_api_key') }}
                    <Link :href="route('settings.edit')" class="font-semibold underline decoration-amber-400">{{ t('questions.go_to_settings') }}</Link>{{ t('questions.go_to_settings_suffix') }}
                </div>

                <!-- Error banner -->
                <div
                    v-if="generateError"
                    class="mx-3 mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-950/30 dark:text-red-300"
                >
                    {{ generateError }}
                </div>

                <!-- Empty state -->
                <div
                    v-if="questions.data.length === 0"
                    class="flex flex-1 flex-col items-center justify-center gap-2 px-6 text-center"
                >
                    <div class="text-3xl">📋</div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ filterTags.length > 0 ? t('questions.empty_state_filtered') : t('questions.empty_state') }}
                    </p>
                    <p v-if="filterTags.length === 0" class="text-xs text-gray-400 dark:text-gray-500">
                        {{ t('questions.empty_state_hint_prefix') }}
                        <span class="font-semibold text-gray-600 dark:text-gray-300">{{ t('questions.empty_state_hint_button') }}</span>{{ t('questions.empty_state_hint_suffix') }}
                    </p>
                </div>

                <!-- Scrollable question list -->
                <ul v-else class="flex-1 overflow-y-auto">
                    <li v-for="q in questions.data" :key="q.id">
                        <button
                            type="button"
                            @click="selectQuestion(q)"
                            class="w-full border-l-2 px-4 py-3.5 text-left transition-colors duration-150 hover:bg-emerald-50/70 dark:hover:bg-white/5"
                            :class="selectedId === q.id
                                ? 'border-emerald-500 bg-emerald-50/80 dark:border-emerald-400 dark:bg-emerald-900/20'
                                : 'border-transparent'"
                        >
                            <p class="line-clamp-2 text-sm font-medium leading-snug text-gray-800 dark:text-gray-200">
                                {{ q.content }}
                            </p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                                    :class="difficultyBadge[q.difficulty]"
                                >
                                    {{ t(`questions.card.difficulty.${q.difficulty}`) }}
                                </span>
                                <span
                                    v-for="tag in q.topic_tags.slice(0, 2)"
                                    :key="tag"
                                    class="inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-700/60 dark:text-slate-400"
                                >
                                    {{ tag }}
                                </span>
                                <span
                                    v-if="q.topic_tags.length > 2"
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                >+{{ q.topic_tags.length - 2 }}</span>
                            </div>
                        </button>
                    </li>
                </ul>

                <!-- Pagination -->
                <div
                    v-if="questions.meta.last_page > 1"
                    class="flex flex-wrap justify-center gap-1 border-t border-gray-200/60 px-3 py-2.5 dark:border-white/10"
                >
                    <template v-for="link in questions.meta.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-md px-2.5 py-1 text-xs font-medium transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                            :class="link.active
                                ? 'bg-emerald-500 text-white'
                                : 'text-gray-500 hover:text-emerald-700 dark:text-gray-400 dark:hover:text-emerald-300'"
                        >{{ decodeLabel(link.label) }}</Link>
                        <span
                            v-else
                            class="px-2.5 py-1 text-xs text-gray-300 dark:text-gray-600"
                        >{{ decodeLabel(link.label) }}</span>
                    </template>
                </div>
            </aside>

            <!-- Right panel: detail -->
            <main
                class="flex flex-1 flex-col overflow-hidden bg-white/20 backdrop-blur-sm dark:bg-slate-900/20"
                :class="!mobileShowDetail && questions.data.length > 0 ? 'hidden md:flex' : 'flex'"
            >
                <!-- Mobile back button -->
                <div class="flex items-center border-b border-emerald-500/12 px-4 py-2.5 md:hidden dark:border-emerald-400/8">
                    <button
                        type="button"
                        @click="mobileShowDetail = false"
                        class="flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0L3.586 10l4.707-4.707a1 1 0 011.414 1.414L7.414 9H17a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        {{ t('questions.back_to_list') }}
                    </button>
                </div>

                <!-- No selection placeholder -->
                <div
                    v-if="!selectedQuestion"
                    class="flex flex-1 flex-col items-center justify-center gap-3 text-center"
                >
                    <div class="text-5xl opacity-20">📋</div>
                    <p class="text-sm text-gray-400 dark:text-gray-500">{{ t('questions.select_question') }}</p>
                </div>

                <!-- Selected question + always-open chat -->
                <div
                    v-else
                    :key="selectedQuestion.id"
                    class="flex flex-1 flex-col gap-4 overflow-y-auto p-6"
                >
                    <QuestionCard :question="selectedQuestion" />
                    <QuestionChat :question-id="selectedQuestion.id" :always-expanded="true" />
                </div>
            </main>
        </div>
    </AuthenticatedLayout>
</template>
