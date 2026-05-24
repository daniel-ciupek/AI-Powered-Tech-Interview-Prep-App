<script setup lang="ts">
import FreeTextTagInput from '@/Components/FreeTextTagInput.vue';
import SpeakButton from '@/Components/SpeakButton.vue';
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

    <AuthenticatedLayout :minimal="true">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold tracking-tight bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-300">
                    {{ t('interview.header') }}
                </h2>
                <span v-if="store.session" class="rounded-full border border-emerald-200/60 bg-emerald-50/60 px-3 py-1 text-xs font-semibold capitalize text-emerald-700 backdrop-blur-sm dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                    {{ store.session.difficulty }} ·
                    {{ store.session.topic_tags.join(', ') || t('interview.general_topic') }}
                </span>
            </div>
        </template>

        <div class="flex h-[calc(100vh-8rem)] flex-col py-6">
            <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 sm:px-6 lg:px-8">

                <!-- No API key -->
                <div v-if="!has_api_key" class="flex flex-1 items-center justify-center">
                    <div class="rounded-2xl border border-amber-200/60 bg-amber-50/60 px-10 py-14 text-center backdrop-blur-sm dark:border-amber-800/40 dark:bg-amber-900/10">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-2xl dark:bg-amber-900/40">
                            🔑
                        </div>
                        <p class="font-semibold text-amber-800 dark:text-amber-200">{{ t('interview.no_api_key_header') }}</p>
                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                            {{ t('interview.no_api_key_prefix') }}
                            <Link :href="route('settings.edit')" class="font-medium underline decoration-amber-400 hover:text-amber-900 dark:hover:text-amber-100">{{ t('interview.no_api_key_link') }}</Link>{{ t('interview.no_api_key_suffix') }}
                        </p>
                    </div>
                </div>

                <!-- Setup screen -->
                <div v-else-if="!store.session" class="flex flex-1 flex-col items-center justify-center gap-6">
                    <!-- Info header -->
                    <div class="animate-spring-in w-full text-center" style="animation-delay: 0ms;">
                        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 text-2xl shadow-lg shadow-emerald-200/40 dark:from-emerald-900/40 dark:to-teal-900/40 dark:shadow-emerald-900/30">
                            🤝
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('interview.difficulty_prefix') }} <span class="font-semibold capitalize text-gray-800 dark:text-gray-200">{{ preferred_difficulty }}</span>
                            · <Link :href="route('settings.edit')" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">{{ t('interview.difficulty_change') }}</Link>
                        </p>
                    </div>

                    <div class="animate-spring-in w-full rounded-2xl border border-white/40 bg-white/82 p-6 shadow-lg shadow-emerald-100/30 backdrop-blur-md dark:border-white/10 dark:bg-black/45 dark:backdrop-blur-2xl dark:saturate-150 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.15),0_8px_32px_rgba(16,185,129,0.18)]" style="animation-delay: 80ms;">
                        <FreeTextTagInput
                            v-model="selectedTags"
                            :suggestions="availableTags"
                            :max-tags="5"
                            :max-length="50"
                        />
                    </div>

                    <button
                        type="button"
                        :disabled="store.loading"
                        @click="startInterview"
                        class="animate-spring-in btn-shimmer inline-flex items-center gap-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-8 py-3 text-base font-semibold text-white transition-all duration-200 hover:from-emerald-400 hover:to-teal-500 hover:scale-[1.02] hover:shadow-[0_0_40px_rgba(16,185,129,0.6)] active:scale-[0.95] active:shadow-[0_0_8px_rgba(16,185,129,0.2)] disabled:opacity-50 disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        style="box-shadow: 0 0 20px rgba(16,185,129,0.35); animation-delay: 160ms;"
                    >
                        <svg v-if="store.loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ store.loading ? t('interview.start_loading') : t('interview.start_button') }}
                    </button>

                    <div
                        v-if="store.error"
                        class="w-full rounded-xl border border-red-200/60 bg-red-50/60 px-4 py-3 text-sm text-red-700 backdrop-blur-sm dark:border-red-800/40 dark:bg-red-950/20 dark:text-red-300"
                    >
                        {{ store.error }}
                    </div>
                </div>

                <!-- Chat -->
                <template v-else>
                    <!-- Messages -->
                    <TransitionGroup
                        name="msg"
                        tag="div"
                        class="animate-spring-in flex-1 space-y-3 overflow-y-auto rounded-2xl border border-white/30 bg-white/70 p-4 shadow-lg shadow-gray-200/30 backdrop-blur-md dark:border-white/10 dark:bg-black/45 dark:backdrop-blur-2xl dark:saturate-150 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.15),0_8px_32px_rgba(16,185,129,0.18)]"
                        style="animation-delay: 0ms;"
                    >
                        <div
                            v-for="msg in store.session.messages"
                            :key="msg.id"
                            class="flex items-end gap-2"
                            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                        >
                            <!-- AI avatar -->
                            <div
                                v-if="msg.role === 'assistant'"
                                class="mb-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-xs font-bold text-white shadow-sm"
                                aria-hidden="true"
                            >
                                AI
                            </div>

                            <div
                                class="max-w-[80%] px-4 py-2.5 text-sm leading-relaxed"
                                :class="msg.role === 'user'
                                    ? 'rounded-2xl rounded-br-sm bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm shadow-emerald-500/20'
                                    : 'rounded-2xl rounded-bl-sm border border-gray-200/60 bg-white/90 text-gray-900 shadow-sm backdrop-blur-sm dark:border-gray-700/40 dark:bg-slate-800/60 dark:text-gray-100'"
                            >
                                {{ msg.content }}
                            </div>
                            <SpeakButton
                                v-if="msg.role === 'assistant'"
                                :text="msg.content"
                                class="mb-0.5 shrink-0"
                            />
                        </div>

                        <!-- Typing indicator -->
                        <div v-if="store.sending" key="typing" class="flex justify-start">
                            <div class="flex items-center gap-1.5 rounded-2xl rounded-bl-sm border border-gray-200/60 bg-white/90 px-4 py-3 backdrop-blur-sm dark:border-gray-700/40 dark:bg-slate-800/60">
                                <span class="h-2 w-2 animate-bounce rounded-full bg-emerald-400 dark:bg-emerald-500" style="animation-delay:0ms" aria-hidden="true"/>
                                <span class="h-2 w-2 animate-bounce rounded-full bg-emerald-400 dark:bg-emerald-500" style="animation-delay:150ms" aria-hidden="true"/>
                                <span class="h-2 w-2 animate-bounce rounded-full bg-emerald-400 dark:bg-emerald-500" style="animation-delay:300ms" aria-hidden="true"/>
                            </div>
                        </div>

                        <div key="bottom" ref="chatBottom" />
                    </TransitionGroup>

                    <!-- Error -->
                    <div
                        v-if="store.error"
                        class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-red-200/60 bg-red-50/60 px-4 py-2 text-sm text-red-700 backdrop-blur-sm dark:border-red-800/40 dark:bg-red-950/20 dark:text-red-300"
                    >
                        <span>{{ store.error }}</span>
                        <button
                            v-if="store.session.status === 'completed' && !store.session.final_report"
                            type="button"
                            @click="store.retryReport()"
                            class="shrink-0 rounded-lg border border-red-300 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-700/40 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40"
                        >
                            {{ t('interview.retry_report') }}
                        </button>
                    </div>

                    <!-- Report: generating (polling) -->
                    <div
                        v-if="store.reportQueued && !store.session.final_report"
                        class="mt-2 flex items-center gap-3 rounded-xl border border-emerald-200/60 bg-emerald-50/60 px-4 py-3 text-sm text-emerald-800 backdrop-blur-sm dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300"
                    >
                        <svg class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        {{ t('interview.report_generating') }}
                    </div>

                    <!-- Report: ready -->
                    <div
                        v-if="store.session.final_report"
                        class="mt-3 rounded-2xl border border-emerald-200/40 bg-white/80 p-5 shadow-lg shadow-emerald-100/30 backdrop-blur-md dark:border-emerald-500/20 dark:bg-black/45 dark:backdrop-blur-2xl dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.10),0_8px_32px_rgba(16,185,129,0.15)]"
                    >
                        <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                            </svg>
                            {{ t('interview.report_title') }}
                        </h3>
                        <pre class="whitespace-pre-wrap font-sans text-sm leading-relaxed text-gray-700 dark:text-gray-200">{{ store.session.final_report }}</pre>
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
                                class="flex-1 resize-none rounded-xl border border-gray-200/60 bg-white/70 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 backdrop-blur-sm shadow-sm transition-all duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50 dark:border-gray-700/60 dark:bg-slate-800/50 dark:text-gray-100 dark:placeholder-gray-500"
                            />
                            <button
                                type="button"
                                :disabled="store.sending || !inputText.trim()"
                                @click="submit"
                                class="btn-shimmer self-end rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:from-emerald-400 hover:to-teal-500 hover:scale-[1.02] hover:shadow-[0_0_40px_rgba(16,185,129,0.6)] active:scale-[0.95] active:shadow-[0_0_8px_rgba(16,185,129,0.2)] disabled:opacity-50 disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                                style="box-shadow: 0 0 20px rgba(16,185,129,0.35);"
                                :aria-label="t('interview.send')"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>
                        <div class="mt-1.5 flex justify-end">
                            <button
                                type="button"
                                :disabled="store.finishing || store.sending"
                                @click="store.finishSession()"
                                class="text-xs text-gray-400 underline decoration-dotted transition-colors duration-150 hover:text-gray-600 disabled:opacity-50 dark:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
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
