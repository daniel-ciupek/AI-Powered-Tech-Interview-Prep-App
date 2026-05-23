<script setup lang="ts">
import QuestionCard from '@/Components/QuestionCard.vue';
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

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{{ t('study.header') }}</h2>
                <span v-if="dueItems.length > 0 && !sessionComplete" class="text-sm text-gray-500 dark:text-gray-400">
                    {{ t('study.due_counter', { current: currentIndex + 1, total: dueItems.length }) }}
                </span>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- No API key -->
                <div v-if="!has_api_key" class="rounded-lg border-2 border-dashed border-gray-300 py-16 text-center dark:border-gray-700">
                    <p class="font-medium text-gray-600 dark:text-gray-300">{{ t('study.no_api_key_header') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('study.no_api_key_prefix') }}
                        <Link :href="route('settings.edit')" class="text-indigo-600 underline dark:text-indigo-400">{{ t('study.no_api_key_link') }}</Link>{{ t('study.no_api_key_suffix') }}
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

                        <!-- Review buttons -->
                        <div class="flex gap-4">
                            <button
                                type="button"
                                :disabled="reviewing"
                                @click="recordReview(2)"
                                class="flex-1 rounded-lg border-2 border-red-300 bg-red-50 py-3 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50 transition dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50"
                            >
                                {{ t('study.didnt_know') }}
                                <span class="ml-1 text-xs text-red-400 dark:text-red-500">{{ t('study.didnt_know_shortcut') }}</span>
                            </button>
                            <button
                                type="button"
                                :disabled="reviewing"
                                @click="recordReview(4)"
                                class="flex-1 rounded-lg border-2 border-green-300 bg-green-50 py-3 text-sm font-semibold text-green-700 hover:bg-green-100 disabled:opacity-50 transition dark:border-green-800 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50"
                            >
                                {{ t('study.got_it') }}
                                <span class="ml-1 text-xs text-green-400 dark:text-green-500">{{ t('study.got_it_shortcut') }}</span>
                            </button>
                        </div>
                        <p class="text-center text-xs text-gray-400 dark:text-gray-500">
                            {{ t('study.shortcuts_hint') }}
                        </p>
                    </template>

                    <!-- ── SESSION COMPLETE ────────────────────────────── -->
                    <div v-else-if="sessionComplete" class="rounded-lg border border-green-200 bg-green-50 py-12 text-center dark:border-green-800 dark:bg-green-900/30">
                        <p class="text-lg font-semibold text-green-800 dark:text-green-300">{{ t('study.session_complete') }}</p>
                        <p class="mt-1 text-sm text-green-600 dark:text-green-400">{{ t('study.session_complete_summary', { count: dueItems.length }) }}</p>
                    </div>

                    <!-- ── GENERATE MODE ───────────────────────────────── -->
                    <template v-else>
                        <!-- Tag selector -->
                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:shadow-gray-900/30">
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
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 transition dark:bg-indigo-500 dark:hover:bg-indigo-400"
                            >
                                <svg v-if="generating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                {{ generating ? t('study.generating') : t('study.generate') }}
                            </button>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ t('study.difficulty_prefix') }} <span class="font-medium capitalize">{{ preferred_difficulty }}</span>
                                · <Link :href="route('settings.edit')" class="text-indigo-600 underline dark:text-indigo-400">{{ t('study.difficulty_change') }}</Link>
                            </p>
                        </div>

                        <p v-if="generateError" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 text-center dark:bg-red-900/30 dark:text-red-300">
                            {{ generateError }}
                        </p>

                        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-y-2">
                            <div v-if="generatedQuestion">
                                <QuestionCard :question="generatedQuestion" />
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
