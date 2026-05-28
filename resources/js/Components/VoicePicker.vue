<script setup lang="ts">
import { useSpeechSynthesis } from '@/composables/useSpeechSynthesis';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { voices, selectedVoiceName, setSelectedVoice, speak, supported } = useSpeechSynthesis();

const polishVoices = computed(() =>
    voices.value.filter((v) => v.lang.toLowerCase().startsWith('pl')),
);

const otherVoices = computed(() =>
    voices.value.filter((v) => !v.lang.toLowerCase().startsWith('pl')),
);

function onChange(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;
    setSelectedVoice(value === '' ? null : value);
}

function preview(): void {
    speak(t('settings.tts.preview_text'));
}
</script>

<template>
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ t('settings.tts.header') }}
        </h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ t('settings.tts.intro') }}
        </p>

        <p v-if="!supported" class="mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
            {{ t('settings.tts.unsupported') }}
        </p>

        <template v-else>
            <p v-if="polishVoices.length === 0 && otherVoices.length === 0" class="mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                {{ t('settings.tts.no_voices') }}
            </p>

            <div v-else class="mt-4">
                <label for="tts-voice" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ t('settings.tts.label') }}
                </label>
                <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                    <select
                        id="tts-voice"
                        :value="selectedVoiceName ?? ''"
                        @change="onChange"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">{{ t('settings.tts.default') }}</option>
                        <optgroup v-if="polishVoices.length > 0" :label="t('settings.tts.group_pl')">
                            <option v-for="v in polishVoices" :key="v.name" :value="v.name">
                                {{ v.name }} ({{ v.lang }})
                            </option>
                        </optgroup>
                        <optgroup v-if="otherVoices.length > 0" :label="t('settings.tts.group_other')">
                            <option v-for="v in otherVoices" :key="v.name" :value="v.name">
                                {{ v.name }} ({{ v.lang }})
                            </option>
                        </optgroup>
                    </select>
                    <button
                        type="button"
                        @click="preview"
                        class="shrink-0 rounded-md border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:bg-gray-800 dark:text-indigo-300 dark:hover:bg-indigo-900/30"
                    >
                        {{ t('settings.tts.preview') }}
                    </button>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ t('settings.tts.hint') }}
                </p>
            </div>
        </template>
    </div>
</template>
