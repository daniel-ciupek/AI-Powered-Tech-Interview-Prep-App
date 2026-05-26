<script setup lang="ts">
import QuestionCard from '@/Components/QuestionCard.vue';
import QuestionChat from '@/Components/QuestionChat.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
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

type Difficulty = 'junior' | 'mid' | 'senior';

const props = defineProps<{
    preferred_difficulty: Difficulty;
}>();

const selectedDifficulty = ref<Difficulty>(props.preferred_difficulty);

const dueItems = ref<StudyItem[]>([]);
const currentIndex = ref(0);
const reviewing = ref(false);
const sessionComplete = ref(false);
const loading = ref(true);

const currentItem = (): StudyItem | null => dueItems.value[currentIndex.value] ?? null;
const currentQuestion = (): Question | null => currentItem()?.question ?? null;

async function loadDueQuestions(): Promise<void> {
    loading.value = true;
    try {
        const res = await window.axios.get<{ data: StudyItem[]; count: number }>('/api/study/today', {
            params: { difficulty: selectedDifficulty.value },
        });
        dueItems.value = res.data.data;
        currentIndex.value = 0;
        sessionComplete.value = false;
    } catch {
        // silent
    } finally {
        loading.value = false;
    }
}

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

function onKeydown(e: KeyboardEvent): void {
    if (currentQuestion() === null) return;
    if (e.key === 'ArrowLeft' || e.key === 'r') recordReview(2);
    if (e.key === 'ArrowRight' || e.key === 'g') recordReview(4);
}

onMounted(() => {
    loadDueQuestions();
    window.addEventListener('keydown', onKeydown);
});
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Head :title="t('study.title')" />

    <AuthenticatedLayout :minimal="true">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-xl font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
                    {{ t('study.header') }}
                </h2>
                <span v-if="dueItems.length > 0 && !sessionComplete" class="rounded-full border border-emerald-200/60 bg-emerald-50/60 px-3 py-1 text-xs font-semibold text-emerald-700 backdrop-blur-sm dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                    {{ t('study.due_counter', { current: currentIndex + 1, total: dueItems.length }) }}
                </span>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6 lg:px-8">

                <!-- Difficulty selector — always visible -->
                <div class="flex items-center justify-center gap-2">
                    <button
                        v-for="d in (['junior', 'mid', 'senior'] as Difficulty[])"
                        :key="d"
                        type="button"
                        class="rounded-xl px-5 py-2 text-sm font-semibold capitalize transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        :class="selectedDifficulty === d
                            ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/25'
                            : 'bg-white/60 text-gray-500 hover:bg-emerald-50 hover:text-emerald-700 dark:bg-slate-800/50 dark:text-gray-400 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-300'"
                        @click="selectedDifficulty = d; loadDueQuestions()"
                    >
                        {{ t(`study.difficulty.${d}`) }}
                    </button>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="flex justify-center py-12">
                    <svg class="h-6 w-6 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </div>

                <template v-else>

                    <!-- ── REVIEW MODE ─────────────────────────────────── -->
                    <template v-if="dueItems.length > 0 && !sessionComplete">
                        <div class="animate-spring-in" style="animation-delay: 0ms;">
                            <QuestionCard
                                v-if="currentQuestion()"
                                :question="currentQuestion()!"
                                :show-answer-by-default="false"
                            />
                        </div>

                        <div class="animate-spring-in" style="animation-delay: 80ms;">
                            <QuestionChat
                                v-if="currentQuestion()"
                                :question-id="currentQuestion()!.id"
                                :key="currentQuestion()!.id"
                            />
                        </div>

                        <!-- Review buttons -->
                        <div class="animate-spring-in flex gap-3" style="animation-delay: 160ms;">
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
                        class="glass-card animate-fade-in-up rounded-2xl px-6 py-12 text-center"
                        style="border-color: rgba(16,185,129,0.30);"
                    >
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 text-3xl shadow-lg shadow-emerald-200/40 dark:from-emerald-900/40 dark:to-teal-900/40">
                            🎯
                        </div>
                        <p class="text-lg font-bold text-emerald-800 dark:text-emerald-300">{{ t('study.session_complete') }}</p>
                        <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">{{ t('study.session_complete_summary', { count: dueItems.length }) }}</p>
                    </div>

                    <!-- ── NOTHING DUE ─────────────────────────────────── -->
                    <div
                        v-else
                        class="glass-card animate-fade-in-up rounded-2xl px-6 py-14 text-center"
                    >
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-slate-100 to-slate-200 text-3xl dark:from-slate-800 dark:to-slate-700">
                            ✅
                        </div>
                        <p class="text-lg font-bold text-gray-700 dark:text-gray-200">{{ t('study.nothing_due') }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ t('study.nothing_due_hint') }}</p>
                    </div>

                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
