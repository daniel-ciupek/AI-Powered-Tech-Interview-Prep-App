<script setup lang="ts">
import { useSpeechSynthesis } from '@/composables/useSpeechSynthesis';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { speak, cancel, speaking, supported } = useSpeechSynthesis();

const props = withDefaults(
    defineProps<{
        text: string;
        lang?: string;
        size?: 'sm' | 'md';
    }>(),
    { lang: 'pl-PL', size: 'sm' },
);

const isMine = ref(false);
const isSpeaking = computed(() => isMine.value && speaking.value);

function toggle(): void {
    if (isSpeaking.value) {
        cancel();
        isMine.value = false;
        return;
    }
    isMine.value = true;
    speak(props.text, { lang: props.lang });
}

const tooltip = computed(() =>
    isSpeaking.value ? t('common.tts.stop') : t('common.tts.play'),
);

const sizeClass = computed(() => (props.size === 'md' ? 'h-9 w-9' : 'h-7 w-7'));
const iconSize = computed(() => (props.size === 'md' ? 'h-5 w-5' : 'h-4 w-4'));
</script>

<template>
    <button
        v-if="supported"
        type="button"
        @click.stop="toggle"
        :title="tooltip"
        :aria-label="tooltip"
        :aria-pressed="isSpeaking"
        :class="[
            sizeClass,
            'inline-flex items-center justify-center rounded-full transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500',
            isSpeaking
                ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60'
                : 'text-gray-400 hover:bg-gray-100/80 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-slate-700/60 dark:hover:text-gray-300',
        ]"
    >
        <!-- Speaker icon -->
        <svg
            v-if="!isSpeaking"
            xmlns="http://www.w3.org/2000/svg"
            :class="iconSize"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path
                d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 11-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z"
            />
        </svg>
        <!-- Stop icon while speaking -->
        <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            :class="iconSize"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path
                fill-rule="evenodd"
                d="M4 4a1 1 0 011-1h10a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                clip-rule="evenodd"
            />
        </svg>
    </button>
</template>
