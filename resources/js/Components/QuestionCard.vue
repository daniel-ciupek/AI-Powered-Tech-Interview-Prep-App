<script setup lang="ts">
import { ref } from 'vue';

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

const difficultyConfig = {
    junior: { label: 'Junior', classes: 'bg-green-100 text-green-800' },
    mid: { label: 'Mid', classes: 'bg-yellow-100 text-yellow-800' },
    senior: { label: 'Senior', classes: 'bg-red-100 text-red-800' },
} as const;
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4">
            <p class="text-base font-medium text-gray-900 leading-relaxed">
                {{ question.content }}
            </p>
            <span
                class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="difficultyConfig[question.difficulty].classes"
            >
                {{ difficultyConfig[question.difficulty].label }}
            </span>
        </div>

        <!-- Keywords -->
        <div v-if="question.expected_keywords.length > 0" class="mt-3 flex flex-wrap gap-1.5">
            <span
                v-for="kw in question.expected_keywords"
                :key="kw"
                class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
            >
                {{ kw }}
            </span>
        </div>

        <!-- Answer toggle -->
        <div class="mt-4">
            <button
                type="button"
                @click="answerVisible = !answerVisible"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
            >
                {{ answerVisible ? 'Hide answer' : 'Show answer' }}
            </button>

            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                leave-active-class="transition duration-100 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="answerVisible && question.expected_answer"
                    class="mt-3 rounded-md bg-indigo-50 p-4 text-sm text-gray-700 leading-relaxed"
                >
                    {{ question.expected_answer }}
                </div>
            </Transition>
        </div>

        <!-- Meta -->
        <p class="mt-3 text-xs text-gray-400">
            {{ question.source === 'ai_generated' ? 'AI generated' : 'User created' }}
            · {{ new Date(question.created_at).toLocaleDateString() }}
        </p>
    </div>
</template>
