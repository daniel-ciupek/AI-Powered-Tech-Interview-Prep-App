<script setup lang="ts">
import SpeakButton from '@/Components/SpeakButton.vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

interface Question {
    id: number;
    content: string;
    difficulty: 'junior' | 'mid' | 'senior';
    source: 'ai_generated' | 'user_created';
    expected_answer: string | null;
    expected_keywords: string[];
    created_at: string;
}

const props = defineProps<{
    question: Question;
    showAnswerByDefault?: boolean;
}>();

const answerVisible = ref(props.showAnswerByDefault ?? false);

const difficultyClasses = {
    junior: 'bg-emerald-100/80 text-emerald-800 border border-emerald-200/60 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700/40',
    mid: 'bg-amber-100/80 text-amber-800 border border-amber-200/60 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700/40',
    senior: 'bg-red-100/80 text-red-800 border border-red-200/60 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700/40',
} as const;
</script>

<template>
    <div class="group relative rounded-2xl border border-white/40 bg-white/82 p-6 shadow-lg shadow-emerald-100/40 backdrop-blur-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-emerald-100/60 dark:border-white/20 dark:bg-slate-800/75 dark:shadow-xl dark:shadow-black/50 dark:hover:border-white/30 dark:hover:shadow-black/70">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-1 items-start gap-3">
                <SpeakButton :text="question.content" class="mt-0.5 shrink-0" />
                <p class="text-base font-medium leading-relaxed text-gray-900 dark:text-gray-100">
                    {{ question.content }}
                </p>
            </div>
            <span
                class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="difficultyClasses[question.difficulty]"
            >
                {{ t(`questions.card.difficulty.${question.difficulty}`) }}
            </span>
        </div>

        <!-- Keywords -->
        <div v-if="question.expected_keywords.length > 0" class="mt-3 flex flex-wrap gap-1.5">
            <span
                v-for="kw in question.expected_keywords"
                :key="kw"
                class="rounded-lg border border-gray-200/60 bg-gray-100/70 px-2 py-0.5 text-xs text-gray-600 backdrop-blur-sm dark:border-white/15 dark:bg-white/10 dark:text-gray-300"
            >
                {{ kw }}
            </span>
        </div>

        <!-- Answer toggle -->
        <div class="mt-4">
            <button
                type="button"
                @click="answerVisible = !answerVisible"
                class="text-sm font-medium text-emerald-600 transition-colors duration-150 hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:text-emerald-400 dark:hover:text-emerald-300 dark:focus-visible:ring-offset-slate-900"
            >
                {{ answerVisible ? t('questions.card.hide_answer') : t('questions.card.show_answer') }}
            </button>

            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                leave-active-class="transition duration-100 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="answerVisible && question.expected_answer"
                    class="mt-3 flex items-start gap-3 rounded-xl border border-emerald-200/40 bg-emerald-50/60 p-4 text-sm leading-relaxed text-gray-700 backdrop-blur-sm dark:border-emerald-500/30 dark:bg-emerald-900/30 dark:text-gray-100"
                >
                    <SpeakButton :text="question.expected_answer" class="mt-0.5 shrink-0" />
                    <span>{{ question.expected_answer }}</span>
                </div>
            </Transition>
        </div>

        <!-- Meta -->
        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
            {{ question.source === 'ai_generated' ? t('questions.card.source_ai') : t('questions.card.source_user') }}
            · {{ new Date(question.created_at).toLocaleDateString(locale) }}
        </p>
    </div>
</template>
