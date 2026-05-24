<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useTheme } from '@/composables/useTheme';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

useTheme();

const { t } = useI18n();

const props = defineProps<{
    has_api_key: boolean;
    settings: {
        preferred_difficulty: 'junior' | 'mid' | 'senior';
        daily_goal: number;
    };
}>();

const totalSteps = 4;
const step = ref(1);
const completing = ref(false);
const preferencesForm = ref<HTMLFormElement | null>(null);

const apiKeyForm = useForm({ api_key: '' });
const prefsForm = useForm({
    preferred_difficulty: props.settings.preferred_difficulty,
    daily_goal: props.settings.daily_goal,
});

const apiKeyPresent = computed(() => props.has_api_key || apiKeyForm.wasSuccessful);

function goNext(): void {
    if (step.value < totalSteps) step.value++;
}

function goBack(): void {
    if (step.value > 1) step.value--;
}

function saveApiKey(): void {
    apiKeyForm.post(route('settings.api-key.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            apiKeyForm.reset('api_key');
            step.value = 3;
        },
    });
}

function savePreferences(): void {
    prefsForm.transform((data) => ({ ...data, theme: 'system' })).patch(route('settings.update'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            step.value = 4;
        },
    });
}

function finish(): void {
    completing.value = true;
    router.post(route('onboarding.complete'), {}, {
        onFinish: () => {
            completing.value = false;
        },
    });
}

const difficulties = ['junior', 'mid', 'senior'] as const;
</script>

<template>
    <Head :title="t('onboarding.title')" />

    <div class="flex min-h-screen flex-col bg-gradient-to-br from-indigo-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
        <header class="mx-auto flex w-full max-w-2xl items-center justify-between px-4 py-6 sm:px-6">
            <Link href="/" class="flex items-center gap-2">
                <ApplicationLogo class="h-8 w-8 fill-current text-indigo-600 dark:text-indigo-400" />
                <span class="text-sm font-semibold tracking-wide text-gray-700 dark:text-gray-200">
                    PrepMind
                </span>
            </Link>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ t('onboarding.step', { current: step, total: totalSteps }) }}
            </p>
        </header>

        <!-- Progress bar -->
        <div class="mx-auto w-full max-w-2xl px-4 sm:px-6">
            <div class="h-1 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    class="h-full rounded-full bg-indigo-600 transition-all duration-300 dark:bg-indigo-400"
                    :style="{ width: `${(step / totalSteps) * 100}%` }"
                />
            </div>
        </div>

        <main class="mx-auto flex w-full max-w-2xl flex-1 flex-col px-4 py-8 sm:px-6">
            <div class="rounded-2xl border border-gray-200 bg-white/80 p-8 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-800/80">

                <!-- Step 1: Welcome -->
                <section v-if="step === 1" class="space-y-5">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ t('onboarding.welcome.header') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ t('onboarding.welcome.intro') }}
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">1</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('onboarding.welcome.feature_questions') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">2</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('onboarding.welcome.feature_study') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">3</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('onboarding.welcome.feature_interview') }}</span>
                        </li>
                    </ul>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                        {{ t('onboarding.welcome.byok_note') }}
                    </div>
                </section>

                <!-- Step 2: API key -->
                <section v-else-if="step === 2" class="space-y-5">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ t('onboarding.api_key.header') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ t('onboarding.api_key.intro') }}
                    </p>

                    <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-900/50">
                        <p class="font-medium text-gray-700 dark:text-gray-200">{{ t('onboarding.api_key.instructions_intro') }}</p>
                        <ol class="mt-2 space-y-1.5 text-gray-600 dark:text-gray-300">
                            <li>
                                1. {{ t('onboarding.api_key.step1') }}
                                <a
                                    href="https://aistudio.google.com/apikey"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-indigo-600 underline hover:text-indigo-500 dark:text-indigo-400"
                                >{{ t('onboarding.api_key.step1_link') }}</a>.
                            </li>
                            <li>2. {{ t('onboarding.api_key.step2') }}</li>
                            <li>3. {{ t('onboarding.api_key.step3') }}</li>
                        </ol>
                    </div>

                    <div v-if="apiKeyPresent" class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                        {{ t('onboarding.api_key.key_present') }}
                    </div>

                    <form v-else @submit.prevent="saveApiKey" class="space-y-3">
                        <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ t('settings.api_key.label') }}
                        </label>
                        <input
                            id="api_key"
                            type="password"
                            autocomplete="off"
                            v-model="apiKeyForm.api_key"
                            placeholder="AIza..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500"
                        />
                        <p v-if="apiKeyForm.errors.api_key" class="text-sm text-red-600 dark:text-red-400">
                            {{ apiKeyForm.errors.api_key }}
                        </p>
                        <button
                            type="submit"
                            :disabled="apiKeyForm.processing || apiKeyForm.api_key.length === 0"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            {{ t('settings.api_key.save') }}
                        </button>
                    </form>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ t('onboarding.api_key.save_skipped') }}
                    </p>
                </section>

                <!-- Step 3: Preferences -->
                <section v-else-if="step === 3" class="space-y-6">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ t('onboarding.preferences.header') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ t('onboarding.preferences.intro') }}
                    </p>

                    <form ref="preferencesForm" @submit.prevent="savePreferences" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('onboarding.preferences.difficulty_label') }}
                            </label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <label
                                    v-for="d in difficulties"
                                    :key="d"
                                    class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition"
                                    :class="prefsForm.preferred_difficulty === d
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-900/30 dark:text-indigo-300'
                                        : 'border-gray-300 text-gray-700 hover:border-indigo-400 dark:border-gray-600 dark:text-gray-300'"
                                >
                                    <input type="radio" :value="d" v-model="prefsForm.preferred_difficulty" class="sr-only" />
                                    {{ t(`settings.difficulty.${d}`) }}
                                </label>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ t('onboarding.preferences.difficulty_hint') }}
                            </p>
                        </div>

                        <div>
                            <label for="daily_goal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('onboarding.preferences.daily_goal_label') }}
                            </label>
                            <div class="mt-2 flex items-center gap-3">
                                <input
                                    id="daily_goal"
                                    type="number"
                                    min="1"
                                    max="50"
                                    v-model.number="prefsForm.daily_goal"
                                    class="block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ t('onboarding.preferences.daily_goal_suffix') }}
                                </span>
                            </div>
                            <p v-if="prefsForm.errors.daily_goal" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ prefsForm.errors.daily_goal }}
                            </p>
                        </div>
                    </form>
                </section>

                <!-- Step 4: Ready -->
                <section v-else class="space-y-5 text-center">
                    <div class="flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl dark:bg-green-900/40">
                            🎉
                        </div>
                    </div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ t('onboarding.ready.header') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ t('onboarding.ready.intro') }}
                    </p>
                    <ul class="space-y-2 text-left">
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="text-indigo-500 dark:text-indigo-400">→</span>
                            {{ t('onboarding.ready.tip_study') }}
                        </li>
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="text-indigo-500 dark:text-indigo-400">→</span>
                            {{ t('onboarding.ready.tip_interview') }}
                        </li>
                    </ul>
                </section>

                <!-- Nav row -->
                <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                    <button
                        type="button"
                        :disabled="step === 1"
                        @click="goBack"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700 disabled:invisible dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        ← {{ t('onboarding.back') }}
                    </button>

                    <template v-if="step === 1">
                        <button
                            type="button"
                            @click="goNext"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            {{ t('onboarding.next') }} →
                        </button>
                    </template>
                    <template v-else-if="step === 2">
                        <button
                            type="button"
                            @click="goNext"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            {{ apiKeyPresent ? t('onboarding.next') : t('onboarding.skip') }} →
                        </button>
                    </template>
                    <template v-else-if="step === 3">
                        <button
                            type="button"
                            :disabled="prefsForm.processing"
                            @click="preferencesForm?.requestSubmit()"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            {{ prefsForm.processing ? t('onboarding.preferences.saving') : t('onboarding.next') }} →
                        </button>
                    </template>
                    <template v-else-if="step === 4">
                        <button
                            type="button"
                            :disabled="completing"
                            @click="finish"
                            class="rounded-md bg-green-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 dark:bg-green-500 dark:hover:bg-green-400"
                        >
                            {{ t('onboarding.finish') }}
                        </button>
                    </template>
                </div>
            </div>
        </main>
    </div>
</template>
