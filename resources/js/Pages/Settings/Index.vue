<script setup lang="ts">
import ApiKeyInput from '@/Components/ApiKeyInput.vue';
import VoicePicker from '@/Components/VoicePicker.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useTheme, type ThemePreference } from '@/composables/useTheme';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { watch } from 'vue';

const { t } = useI18n();
const { setPreference } = useTheme();

interface Settings {
    preferred_difficulty: 'junior' | 'mid' | 'senior';
    daily_goal: number;
    theme: 'light' | 'dark' | 'system';
}

const props = defineProps<{
    settings: Settings;
    has_api_key: boolean;
    status?: string;
}>();

const form = useForm<Settings>({
    preferred_difficulty: props.settings.preferred_difficulty,
    daily_goal: props.settings.daily_goal,
    theme: props.settings.theme,
});

function submit(): void {
    form.patch(route('settings.update'));
}

watch(
    () => form.theme,
    (value) => setPreference(value as ThemePreference),
);

const difficulties = ['junior', 'mid', 'senior'] as const;
const themes = ['light', 'dark', 'system'] as const;
</script>

<template>
    <Head :title="t('settings.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ t('settings.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">

                <!-- Success message -->
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="status === 'settings-updated'"
                        class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300"
                    >
                        {{ t('settings.saved') }}
                    </p>
                </Transition>

                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Interview Preferences -->
                    <div class="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 dark:shadow-gray-900/30">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ t('settings.interview.header') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ t('settings.interview.intro') }}
                        </p>

                        <!-- Difficulty -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('settings.interview.difficulty_label') }}
                            </label>
                            <div class="mt-2 flex gap-3">
                                <label
                                    v-for="d in difficulties"
                                    :key="d"
                                    class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition"
                                    :class="form.preferred_difficulty === d
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-900/30 dark:text-indigo-300'
                                        : 'border-gray-300 text-gray-700 hover:border-indigo-400 dark:border-gray-600 dark:text-gray-300 dark:hover:border-indigo-400'"
                                >
                                    <input
                                        type="radio"
                                        :value="d"
                                        v-model="form.preferred_difficulty"
                                        class="sr-only"
                                    />
                                    {{ t(`settings.difficulty.${d}`) }}
                                </label>
                            </div>
                            <p v-if="form.errors.preferred_difficulty" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.preferred_difficulty }}
                            </p>
                        </div>

                        <!-- Daily goal -->
                        <div class="mt-6">
                            <label
                                for="daily_goal"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ t('settings.interview.daily_goal_label') }}
                            </label>
                            <input
                                id="daily_goal"
                                type="number"
                                min="1"
                                max="50"
                                v-model.number="form.daily_goal"
                                class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p v-if="form.errors.daily_goal" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.daily_goal }}
                            </p>
                        </div>
                    </div>

                    <!-- Appearance -->
                    <div class="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 dark:shadow-gray-900/30">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ t('settings.appearance.header') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ t('settings.appearance.intro') }}
                        </p>

                        <div class="mt-4 flex gap-3">
                            <label
                                v-for="themeOption in themes"
                                :key="themeOption"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition"
                                :class="form.theme === themeOption
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-900/30 dark:text-indigo-300'
                                    : 'border-gray-300 text-gray-700 hover:border-indigo-400 dark:border-gray-600 dark:text-gray-300 dark:hover:border-indigo-400'"
                            >
                                <input
                                    type="radio"
                                    :value="themeOption"
                                    v-model="form.theme"
                                    class="sr-only"
                                />
                                {{ t(`settings.theme.${themeOption}`) }}
                            </label>
                        </div>
                        <p v-if="form.errors.theme" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.theme }}
                        </p>
                    </div>

                    <!-- TTS Voice -->
                    <div class="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 dark:shadow-gray-900/30">
                        <VoicePicker />
                    </div>

                    <!-- API Key -->
                    <div class="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 dark:shadow-gray-900/30">
                        <ApiKeyInput :has-api-key="has_api_key" />
                    </div>

                    <!-- Save -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            {{ t('settings.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
