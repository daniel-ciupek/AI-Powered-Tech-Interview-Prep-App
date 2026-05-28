<script setup lang="ts">
import SpeakButton from '@/Components/SpeakButton.vue';
import { nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    questionId: number;
    alwaysExpanded?: boolean;
}>();

interface ChatMessage {
    id: number;
    role: 'user' | 'assistant';
    content: string;
}

const expanded = ref(props.alwaysExpanded ?? false);
const messages = ref<ChatMessage[]>([]);
const input = ref('');
const sending = ref(false);
const error = ref<string | null>(null);
const chatBottom = ref<HTMLElement | null>(null);
let nextId = 1;

const HISTORY_LIMIT = 10;
const INPUT_MAX = 500;

watch(
    () => messages.value.length,
    async () => {
        await nextTick();
        chatBottom.value?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    },
);

watch(
    () => props.questionId,
    () => {
        messages.value = [];
        input.value = '';
        error.value = null;
        expanded.value = false;
        nextId = 1;
    },
);

async function submit(): Promise<void> {
    const text = input.value.trim();
    if (!text || sending.value) return;
    if (text.length > INPUT_MAX) {
        error.value = t('study.chat.error_generic');
        return;
    }

    error.value = null;
    sending.value = true;

    const history = messages.value
        .slice(-HISTORY_LIMIT)
        .map((m) => ({ role: m.role, content: m.content }));

    messages.value.push({ id: nextId++, role: 'user', content: text });
    input.value = '';

    try {
        const res = await window.axios.post<{ data: { content: string; tokens_used: number } }>(
            `/api/questions/${props.questionId}/chat`,
            { content: text, history },
        );
        messages.value.push({
            id: nextId++,
            role: 'assistant',
            content: res.data.data.content,
        });
    } catch (err: unknown) {
        const e = err as { response?: { status?: number; data?: { message?: string } } };
        const status = e.response?.status;
        error.value = status === 429
            ? t('study.chat.error_rate_limit')
            : (e.response?.data?.message ?? t('study.chat.error_generic'));
        messages.value.pop();
    } finally {
        sending.value = false;
    }
}

function clearChat(): void {
    messages.value = [];
    error.value = null;
}

function onKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        submit();
    }
}
</script>

<template>
    <section
        class="rounded-2xl border border-emerald-200/60 bg-white/70 shadow-sm backdrop-blur-sm dark:border-white/10 dark:bg-black/45 dark:backdrop-blur-2xl dark:saturate-150 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.15),0_8px_32px_rgba(16,185,129,0.18)]"
        :aria-label="t('study.chat.title')"
    >
        <button
            v-if="!alwaysExpanded"
            type="button"
            @click="expanded = !expanded"
            class="flex w-full items-center justify-between rounded-2xl px-5 py-3 text-left transition hover:bg-emerald-50/60 dark:hover:bg-white/5"
            :aria-expanded="expanded"
        >
            <span class="flex items-center gap-2 text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd" />
                </svg>
                {{ t('study.chat.title') }}
            </span>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 text-gray-400 transition-transform dark:text-gray-500"
                :class="{ 'rotate-180': expanded }"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
            >
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div v-else class="flex items-center gap-2 px-5 py-3 text-sm font-semibold text-emerald-900 dark:text-emerald-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd" />
            </svg>
            {{ t('study.chat.title') }}
        </div>

        <div v-if="expanded" class="border-t border-emerald-200/40 px-5 py-4 dark:border-emerald-700/20">

            <!-- Messages -->
            <TransitionGroup
                v-if="messages.length > 0"
                name="msg"
                tag="div"
                class="max-h-52 space-y-3 overflow-y-auto pr-1 sm:max-h-80"
            >
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="flex items-end gap-2"
                    :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[85%] whitespace-pre-wrap px-4 py-2 text-sm leading-relaxed"
                        :class="msg.role === 'user'
                            ? 'rounded-2xl rounded-br-sm bg-emerald-600 text-white dark:bg-emerald-500/90'
                            : 'rounded-2xl rounded-bl-sm bg-emerald-50 text-gray-900 dark:bg-white/10 dark:text-gray-100 dark:border dark:border-white/10'"
                    >{{ msg.content }}</div>
                    <SpeakButton
                        v-if="msg.role === 'assistant'"
                        :text="msg.content"
                        class="mb-0.5 shrink-0"
                    />
                </div>

                <!-- Typing indicator -->
                <div v-if="sending" key="typing" class="flex justify-start">
                    <div class="flex items-center gap-1 rounded-2xl rounded-bl-sm bg-emerald-50 px-4 py-3 dark:bg-emerald-900/30">
                        <span class="h-2 w-2 animate-bounce rounded-full bg-emerald-400" style="animation-delay:0ms" />
                        <span class="h-2 w-2 animate-bounce rounded-full bg-emerald-400" style="animation-delay:150ms" />
                        <span class="h-2 w-2 animate-bounce rounded-full bg-emerald-400" style="animation-delay:300ms" />
                    </div>
                </div>

                <div key="bottom" ref="chatBottom" />
            </TransitionGroup>

            <!-- Empty state -->
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                {{ t('study.chat.empty') }}
            </p>

            <!-- Error -->
            <p v-if="error" class="mt-3 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-900/30 dark:text-red-300">
                {{ error }}
            </p>

            <!-- Input -->
            <div class="mt-3 flex gap-2">
                <textarea
                    v-model="input"
                    :disabled="sending"
                    :placeholder="t('study.chat.placeholder')"
                    :maxlength="INPUT_MAX"
                    @keydown="onKeydown"
                    rows="2"
                    class="flex-1 resize-none rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 dark:border-white/20 dark:bg-white/10 dark:text-gray-100 dark:placeholder-gray-400"
                />
                <button
                    type="button"
                    :disabled="sending || !input.trim()"
                    @click="submit"
                    class="self-end rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                >
                    {{ sending ? t('study.chat.sending') : t('study.chat.send') }}
                </button>
            </div>

            <div class="mt-2 flex items-center justify-between text-xs text-gray-400 dark:text-gray-500">
                <span>{{ input.length }} / {{ INPUT_MAX }}</span>
                <button
                    v-if="messages.length > 0"
                    type="button"
                    @click="clearChat"
                    class="underline hover:text-gray-600 dark:hover:text-gray-300"
                >
                    {{ t('study.chat.clear') }}
                </button>
            </div>
        </div>
    </section>
</template>
