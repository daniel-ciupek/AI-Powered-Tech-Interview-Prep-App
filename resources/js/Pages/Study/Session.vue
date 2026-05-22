<script setup lang="ts">
import QuestionCard from '@/Components/QuestionCard.vue';
import TagSelector from '@/Components/TagSelector.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

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
    has_api_key: boolean;
    preferred_difficulty: 'junior' | 'mid' | 'senior';
}>();

const question = ref<Question | null>(null);
const generating = ref(false);
const error = ref<string | null>(null);

const availableTags = ref<string[]>([]);
const selectedTags = ref<string[]>([]);

onMounted(async () => {
    if (props.has_api_key) {
        try {
            const response = await window.axios.get<{ data: string[] }>('/api/tags');
            availableTags.value = response.data.data;
        } catch {
            // tags optional — silent fail
        }
    }
});

async function generateQuestion(): Promise<void> {
    generating.value = true;
    error.value = null;
    question.value = null;

    try {
        const response = await window.axios.post<{ data: Question }>('/api/questions/generate', {
            tags: selectedTags.value,
            difficulty: props.preferred_difficulty,
        });
        question.value = response.data.data;
    } catch (err: unknown) {
        const axiosErr = err as { response?: { data?: { message?: string }; status?: number } };
        if (axiosErr.response?.status === 429) {
            error.value = 'Rate limit reached. Please wait a moment and try again.';
        } else {
            error.value = axiosErr.response?.data?.message ?? 'Generation failed. Please try again.';
        }
    } finally {
        generating.value = false;
    }
}
</script>

<template>
    <Head title="Study Session" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Study Session
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- No API key -->
                <div v-if="!has_api_key" class="rounded-lg border-2 border-dashed border-gray-300 py-16 text-center">
                    <p class="text-gray-600 font-medium">Gemini API key required</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Add your key in
                        <Link :href="route('settings.edit')" class="text-indigo-600 underline">Settings</Link>
                        to start generating questions.
                    </p>
                </div>

                <template v-else>
                    <!-- Tag selector -->
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <TagSelector
                            :tags="availableTags"
                            :selected-tags="selectedTags"
                            :max-tags="5"
                            @update:selected-tags="selectedTags = $event"
                        />
                    </div>

                    <!-- Generate button -->
                    <div class="text-center">
                        <button
                            type="button"
                            :disabled="generating"
                            @click="generateQuestion"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 transition"
                        >
                            <svg v-if="generating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            {{ generating ? 'Generating…' : question ? 'Next Question' : 'Generate Question' }}
                        </button>
                        <p class="mt-2 text-xs text-gray-500">
                            Difficulty: <span class="font-medium capitalize">{{ preferred_difficulty }}</span>
                            · <Link :href="route('settings.edit')" class="text-indigo-600 underline">change</Link>
                        </p>
                    </div>

                    <!-- Error -->
                    <p v-if="error" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 text-center">
                        {{ error }}
                    </p>

                    <!-- Question card -->
                    <Transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="opacity-0 translate-y-2"
                    >
                        <div v-if="question">
                            <QuestionCard :question="question" />
                            <p class="mt-4 text-center text-sm text-gray-500">
                                Saved to your
                                <Link :href="route('questions.index')" class="text-indigo-600 underline">library</Link>.
                            </p>
                        </div>
                    </Transition>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
