<script setup lang="ts">
import QuestionCard from '@/Components/QuestionCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
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

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedQuestions {
    data: Question[];
    links: PaginationLink[];
    meta: { current_page: number; last_page: number; total: number };
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
        generateError.value = axiosErr.response?.data?.message ?? 'Generation failed. Please try again.';
    } finally {
        generating.value = false;
    }
}
</script>

<template>
    <Head title="Questions" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    My Questions
                </h2>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('study.session')"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        Study Session →
                    </Link>
                    <button
                        type="button"
                        :disabled="generating || !has_api_key"
                        @click="generate"
                        class="rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-600 hover:bg-indigo-50 disabled:opacity-50"
                    >
                        {{ generating ? 'Generating…' : '+ Generate' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

                <!-- No API key warning -->
                <div
                    v-if="!has_api_key"
                    class="mb-6 rounded-md bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800"
                >
                    No Gemini API key configured.
                    <Link :href="route('settings.edit')" class="underline font-medium">Go to Settings</Link>
                    to add one.
                </div>

                <!-- Error -->
                <p v-if="generateError" class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ generateError }}
                </p>

                <!-- Empty state -->
                <div
                    v-if="questions.data.length === 0"
                    class="rounded-lg border-2 border-dashed border-gray-300 py-16 text-center"
                >
                    <p class="text-gray-500">No questions yet.</p>
                    <p class="mt-1 text-sm text-gray-400">
                        Click <span class="font-medium">+ Generate</span> to create your first AI question.
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
                <div v-if="questions.meta.last_page > 1" class="mt-8 flex justify-center gap-1">
                    <template v-for="link in questions.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded px-3 py-1.5 text-sm"
                            :class="link.active
                                ? 'bg-indigo-600 text-white'
                                : 'text-gray-600 hover:bg-gray-100'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="rounded px-3 py-1.5 text-sm text-gray-400"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
