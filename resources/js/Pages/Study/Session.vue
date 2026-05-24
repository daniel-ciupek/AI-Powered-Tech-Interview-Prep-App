<script setup lang="ts">
import QuestionCard from '@/Components/QuestionCard.vue';
import QuestionChat from '@/Components/QuestionChat.vue';
import FreeTextTagInput from '@/Components/FreeTextTagInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
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

interface StudyItem {
    repetition_id: number;
    question: Question;
}

const props = defineProps<{
    has_api_key: boolean;
    preferred_difficulty: 'junior' | 'mid' | 'senior';
}>();

// ─── Generate mode state ──────────────────────────────────────────────────
const availableTags = ref<string[]>([]);
const selectedTags = ref<string[]>([]);
const generating = ref(false);
const generateError = ref<string | null>(null);
const generatedQuestion = ref<Question | null>(null);

// ─── Review mode state ────────────────────────────────────────────────────
const dueItems = ref<StudyItem[]>([]);
const currentIndex = ref(0);
const reviewing = ref(false);
const sessionComplete = ref(false);

const currentItem = (): StudyItem | null => dueItems.value[currentIndex.value] ?? null;
const currentQuestion = (): Question | null => currentItem()?.question ?? null;

// ─── Load due questions ───────────────────────────────────────────────────
async function loadDueQuestions(): Promise<void> {
    try {
        const res = await window.axios.get<{ data: StudyItem[]; count: number }>('/api/study/today');
        dueItems.value = res.data.data;
    } catch {
        // silent — fall back to generate mode
    }
}

// ─── Load available tags ──────────────────────────────────────────────────
async function loadTags(): Promise<void> {
    if (!props.has_api_key) return;
    try {
        const res = await window.axios.get<{ data: string[] }>('/api/tags');
        availableTags.value = res.data.data;
    } catch {
        // tags optional
    }
}

onMounted(async () => {
    await Promise.all([loadDueQuestions(), loadTags()]);
});

// ─── Generate question ────────────────────────────────────────────────────
async function generateQuestion(): Promise<void> {
    generating.value = true;
    generateError.value = null;
    generatedQuestion.value = null;
    try {
        const res = await window.axios.post<{ data: Question }>('/api/questions/generate', {
            tags: selectedTags.value,
            difficulty: props.preferred_difficulty,
        });
        generatedQuestion.value = res.data.data;
        await loadDueQuestions();
    } catch (err: unknown) {
        const e = err as { response?: { data?: { message?: string }; status?: number } };
        generateError.value = e.response?.status === 429
            ? t('study.rate_limit')
            : (e.response?.data?.message ?? t('study.generation_failed'));
    } finally {
        generating.value = false;
    }
}

// ─── Record review ────────────────────────────────────────────────────────
async function recordReview(quality: number): Promise<void> {
    const item = currentItem();
    if (!item || reviewing.value) return;

    reviewing.value = true;
    try {
        await window.axios.post(`/api/repetitions/${item.repetition_id}/review`, { quality });
        if (currentIndex.value + 1 >= dueItems.value.length) {
            sessionComplete.value = true;
        } else {
            currentIndex.value++;
        }
    } catch {
        // keep current card
    } finally {
        reviewing.value = false;
    }
}

// ─── Keyboard shortcuts ───────────────────────────────────────────────────
function onKeydown(e: KeyboardEvent): void {
    if (currentQuestion() === null) return;
    if (e.key === 'ArrowLeft' || e.key === 'r') recordReview(2);
    if (e.key === 'ArrowRight' || e.key === 'g') recordReview(4);
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Head :title="t('study.title')" />

    <AuthenticatedLayout :minimal="true">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold tracking-tight bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-300">
                    {{ t('study.header') }}
                </h2>
                <span v-if="dueItems.length > 0 && !sessionComplete" class="rounded-full border border-emerald-200/60 bg-emerald-50/60 px-3 py-1 text-xs font-semibold text-emerald-700 backdrop-blur-sm dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                    {{ t('study.due_counter', { current: currentIndex + 1, total: dueItems.length }) }}
                </span>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6 lg:px-8">

                <!-- No API key -->
                <div
                    v-if="!has_api_key"
                    class="rounded-2xl border border-amber-200/60 bg-amber-50/60 px-6 py-12 text-center backdrop-blur-sm dark:border-amber-800/40 dark:bg-amber-900/10"
                >
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-2xl dark:bg-amber-900/40">
                        🔑
                    </div>
                    <p class="font-semibold text-amber-800 dark:text-amber-200">{{ t('study.no_api_key_header') }}</p>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        {{ t('study.no_api_key_prefix') }}
                        <Link :href="route('settings.edit')" class="font-medium underline decoration-amber-400 hover:text-amber-900 dark:hover:text-amber-100">{{ t('study.no_api_key_link') }}</Link>{{ t('study.no_api_key_suffix') }}
                    </p>
                </div>

                <template v-else>

                    <!-- ── REVIEW MODE ─────────────────────────────────── -->
                    <template v-if="dueItems.length > 0 && !sessionComplete">
                        <QuestionCard
                            v-if="currentQuestion()"
                            :question="currentQuestion()!"
                            :show-answer-by-default="false"
                        />

                        <QuestionChat
                            v-if="currentQuestion()"
                            :question-id="currentQuestion()!.id"
                            :key="currentQuestion()!.id"
                        />

                        <!-- Review buttons -->
                        <div class="flex gap-3">
                            <button
                                type="button"
                                :disabled="reviewing"
                                @click="recordReview(2)"
                                class="group flex-1 rounded-xl border border-red-200/60 bg-red-50/70 px-4 py-3.5 text-sm font-semibold text-red-700 shadow-sm backdrop-blur-sm transition-all duration-200 hover:border-red-300/80 hover:bg-red-100/80 hover:-translate-y-0.5 hover:shadow-md hover:shadow-red-100/60 active:scale-[0.97] disabled:opacity-50 disabled:hover:translate-y-0 dark:border-red-800/40 dark:bg-red-950/20 dark:text-red-300 dark:hover:bg-red-950/30"
                            >
                                <span class="flex items-center justify-center gap-2">
                                    <span class="text-base">✗</span>
                                    {{ t('study.didnt_know') }}
                                    <span class="text-xs font-normal text-red-400 dark:text-red-500">{{ t('study.didnt_know_shortcut') }}</span>
                                </span>
                            </button>
                            <button
                                type="button"
                                :disabled="reviewing"
                                @click="recordReview(4)"
                                class="group flex-1 rounded-xl border border-emerald-200/60 bg-emerald-50/70 px-4 py-3.5 text-sm font-semibold text-emerald-700 shadow-sm backdrop-blur-sm transition-all duration-200 hover:border-emerald-300/80 hover:bg-emerald-100/80 hover:-translate-y-0.5 hover:shadow-md hover:shadow-emerald-100/60 active:scale-[0.97] disabled:opacity-50 disabled:hover:translate-y-0 dark:border-emerald-800/40 dark:bg-emerald-950/20 dark:text-emerald-300 dark:hover:bg-emerald-950/30"
                            >
                                <span class="flex items-center justify-center gap-2">
                                    <span class="text-base">✓</span>
                                    {{ t('study.got_it') }}
                                    <span class="text-xs font-normal text-emerald-400 dark:text-emerald-500">{{ t('study.got_it_shortcut') }}</span>
                                </span>
                            </button>
                        </div>
                        <p class="text-center text-xs text-gray-400 dark:text-gray-500">
                            {{ t('study.shortcuts_hint') }}
                        </p>
                    </template>

                    <!-- ── SESSION COMPLETE ────────────────────────────── -->
                    <div
                        v-else-if="sessionComplete"
                        class="rounded-2xl border border-emerald-200/60 bg-emerald-50/60 px-6 py-12 text-center backdrop-blur-sm dark:border-emerald-800/40 dark:bg-emerald-900/10"
                    >
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 text-3xl shadow-lg shadow-emerald-200/40 dark:from-emerald-900/40 dark:to-teal-900/40">
                            🎯
                        </div>
                        <p class="text-lg font-bold text-emerald-800 dark:text-emerald-300">{{ t('study.session_complete') }}</p>
                        <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">{{ t('study.session_complete_summary', { count: dueItems.length }) }}</p>
                    </div>

                    <!-- ── GENERATE MODE ───────────────────────────────── -->
                    <template v-else>
                        <!-- Tag selector -->
                        <div class="rounded-2xl border border-white/40 bg-white/82 p-5 shadow-lg shadow-emerald-100/30 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/40">
                            <FreeTextTagInput
                                v-model="selectedTags"
                                :suggestions="availableTags"
                                :max-tags="5"
                                :max-length="50"
                            />
                        </div>

                        <!-- Generate button -->
                        <div class="text-center">
                            <button
                                type="button"
                                :disabled="generating"
                                @click="generateQuestion"
                                class="inline-flex items-center gap-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-7 py-3 text-base font-semibold text-white shadow-lg shadow-emerald-500/30 transition-all duration-200 hover:from-emerald-600 hover:to-teal-700 hover:scale-[1.02] hover:shadow-emerald-500/40 active:scale-[0.97] disabled:opacity-50 disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                            >
                                <svg v-if="generating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ generating ? t('study.generating') : t('study.generate') }}
                            </button>
                            <p class="mt-2.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ t('study.difficulty_prefix') }} <span class="font-semibold capitalize text-gray-700 dark:text-gray-200">{{ preferred_difficulty }}</span>
                                · <Link :href="route('settings.edit')" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">{{ t('study.difficulty_change') }}</Link>
                            </p>
                        </div>

                        <div
                            v-if="generateError"
                            class="rounded-xl border border-red-200/60 bg-red-50/60 px-4 py-3 text-sm text-red-700 text-center backdrop-blur-sm dark:border-red-800/40 dark:bg-red-950/20 dark:text-red-300"
                        >
                            {{ generateError }}
                        </div>

                        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-y-2">
                            <div v-if="generatedQuestion" class="space-y-4">
                                <QuestionCard :question="generatedQuestion" />
                                <QuestionChat
                                    :question-id="generatedQuestion.id"
                                    :key="generatedQuestion.id"
                                />
                                <p class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ t('study.come_back_tomorrow') }}
                                </p>
                            </div>
                        </Transition>
                    </template>

                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
