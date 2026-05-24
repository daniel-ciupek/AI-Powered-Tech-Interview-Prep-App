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
            <h2 class="text-xl font-bold tracking-tight bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-300">
                {{ t('settings.title') }}
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6 lg:px-8">

                <!-- Success message -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="status === 'settings-updated'"
                        class="rounded-xl border border-emerald-200/60 bg-emerald-50/80 px-4 py-3 text-sm font-medium text-emerald-700 backdrop-blur-sm dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300"
                    >
                        {{ t('settings.saved') }}
                    </div>
                </Transition>

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Interview Preferences -->
                    <div class="rounded-2xl border border-white/40 bg-white/82 p-6 shadow-lg shadow-emerald-100/30 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/40 dark:shadow-emerald-950/20">
                        <h3 class="text-base font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {{ t('settings.interview.header') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('settings.interview.intro') }}
                        </p>

                        <!-- Difficulty -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('settings.interview.difficulty_label') }}
                            </label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <label
                                    v-for="d in difficulties"
                                    :key="d"
                                    class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition-all duration-150"
                                    :class="form.preferred_difficulty === d
                                        ? 'border-emerald-500 bg-emerald-50/80 text-emerald-700 shadow-sm shadow-emerald-500/20 dark:border-emerald-500 dark:bg-emerald-900/30 dark:text-emerald-300'
                                        : 'border-gray-200/60 text-gray-700 hover:border-emerald-400/70 hover:bg-emerald-50/40 dark:border-gray-700/60 dark:text-gray-300 dark:hover:border-emerald-600/60'"
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
                            <p v-if="form.errors.preferred_difficulty" class="mt-2 text-sm text-red-600 dark:text-red-400">
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
                                class="mt-2 block w-28 rounded-xl border border-gray-200/60 bg-white/70 px-4 py-2.5 text-sm text-gray-900 backdrop-blur-sm shadow-sm transition-all duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-700/60 dark:bg-slate-800/50 dark:text-gray-100"
                            />
                            <p v-if="form.errors.daily_goal" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.daily_goal }}
                            </p>
                        </div>
                    </div>

                    <!-- Appearance -->
                    <div class="rounded-2xl border border-white/40 bg-white/82 p-6 shadow-lg shadow-emerald-100/30 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/40 dark:shadow-emerald-950/20">
                        <h3 class="text-base font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {{ t('settings.appearance.header') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('settings.appearance.intro') }}
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <label
                                v-for="themeOption in themes"
                                :key="themeOption"
                                class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition-all duration-150"
                                :class="form.theme === themeOption
                                    ? 'border-emerald-500 bg-emerald-50/80 text-emerald-700 shadow-sm shadow-emerald-500/20 dark:border-emerald-500 dark:bg-emerald-900/30 dark:text-emerald-300'
                                    : 'border-gray-200/60 text-gray-700 hover:border-emerald-400/70 hover:bg-emerald-50/40 dark:border-gray-700/60 dark:text-gray-300 dark:hover:border-emerald-600/60'"
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
                        <p v-if="form.errors.theme" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.theme }}
                        </p>
                    </div>

                    <!-- TTS Voice -->
                    <div class="rounded-2xl border border-white/40 bg-white/82 p-6 shadow-lg shadow-emerald-100/30 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/40 dark:shadow-emerald-950/20">
                        <VoicePicker />
                    </div>

                    <!-- API Key -->
                    <div class="rounded-2xl border border-white/40 bg-white/82 p-6 shadow-lg shadow-emerald-100/30 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/40 dark:shadow-emerald-950/20">
                        <ApiKeyInput :has-api-key="has_api_key" />
                    </div>

                    <!-- Save -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:from-emerald-600 hover:to-teal-700 hover:scale-[1.02] active:scale-[0.97] disabled:opacity-50 disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            {{ t('settings.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
