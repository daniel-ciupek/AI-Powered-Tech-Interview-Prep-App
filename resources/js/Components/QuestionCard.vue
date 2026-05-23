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
    junior: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    mid: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
    senior: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
} as const;
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:shadow-gray-900/30">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-1 items-start gap-2">
                <SpeakButton :text="question.content" class="mt-0.5 shrink-0" />
                <p class="text-base font-medium text-gray-900 leading-relaxed dark:text-gray-100">
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
                class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300"
            >
                {{ kw }}
            </span>
        </div>

        <!-- Answer toggle -->
        <div class="mt-4">
            <button
                type="button"
                @click="answerVisible = !answerVisible"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
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
                    class="mt-3 flex items-start gap-2 rounded-md bg-indigo-50 p-4 text-sm text-gray-700 leading-relaxed dark:bg-indigo-900/30 dark:text-gray-200"
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
