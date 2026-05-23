<script setup lang="ts">
import TagSelector from '@/Components/TagSelector.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useInterviewSession } from '@/stores/interviewSession';
import { Head, Link } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    has_api_key: boolean;
    preferred_difficulty: 'junior' | 'mid' | 'senior';
}>();

const store = useInterviewSession();
const availableTags = ref<string[]>([]);
const selectedTags = ref<string[]>([]);
const inputText = ref('');
const chatBottom = ref<HTMLElement | null>(null);

onMounted(async () => {
    store.reset();
    if (props.has_api_key) {
        try {
            const res = await window.axios.get<{ data: string[] }>('/api/tags');
            availableTags.value = res.data.data;
        } catch { /* tags optional */ }
    }
});

watch(
    () => store.session?.messages.length,
    async () => {
        await nextTick();
        chatBottom.value?.scrollIntoView({ behavior: 'smooth' });
    },
);

async function startInterview(): Promise<void> {
    await store.start(selectedTags.value, props.preferred_difficulty);
}

async function submit(): Promise<void> {
    const text = inputText.value.trim();
    if (!text || store.sending) return;
    inputText.value = '';
    await store.sendMessage(text);
}

function onKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        submit();
    }
}
</script>

<template>
    <Head :title="t('interview.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('interview.header') }}</h2>
                <span v-if="store.session" class="text-sm text-gray-500 capitalize">
                    {{ store.session.difficulty }} ·
                    {{ store.session.topic_tags.join(', ') || t('interview.general_topic') }}
                </span>
            </div>
        </template>

        <div class="flex h-[calc(100vh-8rem)] flex-col py-6">
            <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 sm:px-6 lg:px-8">

                <!-- No API key -->
                <div v-if="!has_api_key" class="flex flex-1 items-center justify-center">
                    <div class="rounded-lg border-2 border-dashed border-gray-300 px-12 py-16 text-center">
                        <p class="font-medium text-gray-600">{{ t('interview.no_api_key_header') }}</p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('interview.no_api_key_prefix') }}
                            <Link :href="route('settings.edit')" class="text-indigo-600 underline">{{ t('interview.no_api_key_link') }}</Link>{{ t('interview.no_api_key_suffix') }}
                        </p>
                    </div>
                </div>

                <!-- Setup screen -->
                <div v-else-if="!store.session" class="flex flex-1 flex-col items-center justify-center gap-6">
                    <div class="w-full rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <TagSelector
                            :tags="availableTags"
                            :selected-tags="selectedTags"
                            :max-tags="5"
                            @update:selected-tags="selectedTags = $event"
                        />
                    </div>

                    <div class="text-center">
                        <button
                            type="button"
                            :disabled="store.loading"
                            @click="startInterview"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-8 py-3 text-base font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 transition"
                        >
                            <svg v-if="store.loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            {{ store.loading ? t('interview.start_loading') : t('interview.start_button') }}
                        </button>
                        <p class="mt-2 text-xs text-gray-500">
                            {{ t('interview.difficulty_prefix') }} <span class="font-medium capitalize">{{ preferred_difficulty }}</span>
                            · <Link :href="route('settings.edit')" class="text-indigo-600 underline">{{ t('interview.difficulty_change') }}</Link>
                        </p>
                    </div>

                    <p v-if="store.error" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ store.error }}
                    </p>
                </div>

                <!-- Chat -->
                <template v-else>
                    <!-- Messages -->
                    <div class="flex-1 space-y-4 overflow-y-auto rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <div
                            v-for="msg in store.session.messages"
                            :key="msg.id"
                            class="flex"
                            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                        >
                            <div
                                class="max-w-[80%] rounded-lg px-4 py-2.5 text-sm leading-relaxed"
                                :class="msg.role === 'user'
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-900'"
                            >
                                {{ msg.content }}
                            </div>
                        </div>

                        <!-- Typing indicator -->
                        <div v-if="store.sending" class="flex justify-start">
                            <div class="flex items-center gap-1 rounded-lg bg-gray-100 px-4 py-3">
                                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay:0ms"/>
                                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay:150ms"/>
                                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay:300ms"/>
                            </div>
                        </div>

                        <div ref="chatBottom" />
                    </div>

                    <!-- Error -->
                    <p v-if="store.error" class="mt-2 rounded-md bg-red-50 px-4 py-2 text-sm text-red-700">
                        {{ store.error }}
                    </p>

                    <!-- Report queued banner -->
                    <div v-if="store.reportQueued" class="mt-2 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ t('interview.report_queued') }}
                    </div>

                    <!-- Input (only while active) -->
                    <template v-if="store.session.status === 'active'">
                        <div class="mt-3 flex gap-2">
                            <textarea
                                v-model="inputText"
                                :disabled="store.sending"
                                @keydown="onKeydown"
                                rows="2"
                                :placeholder="t('interview.input_placeholder')"
                                class="flex-1 resize-none rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50"
                            />
                            <button
                                type="button"
                                :disabled="store.sending || !inputText.trim()"
                                @click="submit"
                                class="self-end rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                            >
                                {{ t('interview.send') }}
                            </button>
                        </div>
                        <div class="mt-1 flex justify-end">
                            <button
                                type="button"
                                :disabled="store.finishing || store.sending"
                                @click="store.finishSession()"
                                class="text-xs text-gray-400 underline hover:text-gray-600 disabled:opacity-50"
                            >
                                {{ store.finishing ? t('interview.finishing') : t('interview.finish') }}
                            </button>
                        </div>
                    </template>
                </template>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
