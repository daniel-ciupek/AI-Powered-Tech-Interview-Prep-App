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

    <div class="relative flex min-h-screen flex-col overflow-x-hidden bg-gradient-to-br from-emerald-50 via-white to-teal-50 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950/40">

        <!-- Decorative blobs (fixed so they don't scroll with content) -->
        <div aria-hidden="true" class="pointer-events-none fixed -top-24 -right-24 h-96 w-96 rounded-full bg-emerald-300/30 blur-3xl dark:bg-emerald-500/10 -z-10" />
        <div aria-hidden="true" class="pointer-events-none fixed -bottom-20 -left-20 h-80 w-80 rounded-full bg-teal-400/20 blur-3xl dark:bg-teal-500/10 -z-10" />

        <!-- Header -->
        <header class="relative z-10 mx-auto flex w-full max-w-2xl items-center justify-between px-4 py-6 sm:px-6">
            <Link href="/" class="flex items-center gap-2.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 rounded-xl p-1">
                <ApplicationLogo class="h-12 w-12 fill-current text-emerald-600 dark:text-emerald-400" />
                <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-sm font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
                    PrepMind
                </span>
            </Link>
            <p class="text-xs font-medium text-gray-400 dark:text-gray-500">
                {{ t('onboarding.step', { current: step, total: totalSteps }) }}
            </p>
        </header>

        <!-- Progress bar -->
        <div class="relative z-10 mx-auto w-full max-w-2xl px-4 sm:px-6">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200/60 dark:bg-gray-800/60">
                <div
                    class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 shadow-sm shadow-emerald-500/30 transition-all duration-500 ease-out"
                    :style="{ width: `${(step / totalSteps) * 100}%` }"
                />
            </div>
        </div>

        <!-- Main card -->
        <main class="relative z-10 mx-auto flex w-full max-w-2xl flex-1 flex-col px-4 py-8 sm:px-6">
            <div class="rounded-3xl border border-white/40 bg-white/60 p-8 shadow-lg shadow-emerald-100/40 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/40 dark:shadow-emerald-950/30">

                <!-- Step 1: Welcome -->
                <section v-if="step === 1" class="space-y-5 animate-fade-in-up">
                    <h1 class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-2xl font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
                        {{ t('onboarding.welcome.header') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ t('onboarding.welcome.intro') }}
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-xs font-bold text-white shadow-sm">1</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('onboarding.welcome.feature_questions') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-xs font-bold text-white shadow-sm">2</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('onboarding.welcome.feature_study') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-xs font-bold text-white shadow-sm">3</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('onboarding.welcome.feature_interview') }}</span>
                        </li>
                    </ul>
                    <div class="rounded-xl border border-amber-200/60 bg-amber-50/60 p-4 text-sm text-amber-800 backdrop-blur-sm dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-200">
                        {{ t('onboarding.welcome.byok_note') }}
                    </div>
                </section>

                <!-- Step 2: API key -->
                <section v-else-if="step === 2" class="space-y-5 animate-fade-in-up">
                    <h1 class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-2xl font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
                        {{ t('onboarding.api_key.header') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ t('onboarding.api_key.intro') }}
                    </p>

                    <div class="rounded-xl border border-gray-200/60 bg-gray-50/60 p-4 text-sm backdrop-blur-sm dark:border-gray-700/40 dark:bg-slate-800/40">
                        <p class="font-medium text-gray-700 dark:text-gray-200">{{ t('onboarding.api_key.instructions_intro') }}</p>
                        <ol class="mt-2 space-y-1.5 text-gray-600 dark:text-gray-300">
                            <li>
                                1. {{ t('onboarding.api_key.step1') }}
                                <a
                                    href="https://aistudio.google.com/apikey"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-emerald-600 underline hover:text-emerald-500 dark:text-emerald-400"
                                >{{ t('onboarding.api_key.step1_link') }}</a>.
                            </li>
                            <li>2. {{ t('onboarding.api_key.step2') }}</li>
                            <li>3. {{ t('onboarding.api_key.step3') }}</li>
                        </ol>
                    </div>

                    <div v-if="apiKeyPresent" class="rounded-xl border border-emerald-200/60 bg-emerald-50/60 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300">
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
                            class="block w-full rounded-xl border border-gray-200/60 bg-white/70 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 backdrop-blur-sm shadow-sm transition-all duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-700/60 dark:bg-slate-800/50 dark:text-gray-100 dark:placeholder-gray-500"
                        />
                        <p v-if="apiKeyForm.errors.api_key" class="text-sm text-red-600 dark:text-red-400">
                            {{ apiKeyForm.errors.api_key }}
                        </p>
                        <button
                            type="submit"
                            :disabled="apiKeyForm.processing || apiKeyForm.api_key.length === 0"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:from-emerald-600 hover:to-teal-700 hover:scale-[1.02] disabled:opacity-50 disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        >
                            {{ t('settings.api_key.save') }}
                        </button>
                    </form>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ t('onboarding.api_key.save_skipped') }}
                    </p>
                </section>

                <!-- Step 3: Preferences -->
                <section v-else-if="step === 3" class="space-y-6 animate-fade-in-up">
                    <h1 class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-2xl font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
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
                                    class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition-all duration-150"
                                    :class="prefsForm.preferred_difficulty === d
                                        ? 'border-emerald-500 bg-emerald-50/80 text-emerald-700 shadow-sm shadow-emerald-500/20 dark:border-emerald-500 dark:bg-emerald-900/30 dark:text-emerald-300'
                                        : 'border-gray-200/60 text-gray-700 hover:border-emerald-400 dark:border-gray-700/60 dark:text-gray-300 dark:hover:border-emerald-500'"
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
                                    class="block w-24 rounded-xl border border-gray-200/60 bg-white/70 px-4 py-2.5 text-sm text-gray-900 backdrop-blur-sm shadow-sm transition-all duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-700/60 dark:bg-slate-800/50 dark:text-gray-100"
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
                <section v-else class="space-y-5 text-center animate-fade-in-up">
                    <div class="flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 text-3xl shadow-lg shadow-emerald-200/40 dark:from-emerald-900/40 dark:to-teal-900/40 dark:shadow-emerald-900/30">
                            🎉
                        </div>
                    </div>
                    <h1 class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-2xl font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
                        {{ t('onboarding.ready.header') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ t('onboarding.ready.intro') }}
                    </p>
                    <ul class="space-y-2 text-left">
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-semibold text-emerald-500 dark:text-emerald-400">→</span>
                            {{ t('onboarding.ready.tip_study') }}
                        </li>
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-semibold text-emerald-500 dark:text-emerald-400">→</span>
                            {{ t('onboarding.ready.tip_interview') }}
                        </li>
                    </ul>
                </section>

                <!-- Nav row -->
                <div class="mt-8 flex items-center justify-between border-t border-gray-200/50 pt-6 dark:border-gray-700/50">
                    <button
                        type="button"
                        :disabled="step === 1"
                        @click="goBack"
                        class="text-sm font-medium text-gray-500 transition-colors duration-150 hover:text-gray-700 disabled:invisible focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        ← {{ t('onboarding.back') }}
                    </button>

                    <template v-if="step === 1">
                        <button
                            type="button"
                            @click="goNext"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:from-emerald-600 hover:to-teal-700 hover:scale-[1.02] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        >
                            {{ t('onboarding.next') }} →
                        </button>
                    </template>
                    <template v-else-if="step === 2">
                        <button
                            type="button"
                            @click="goNext"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:from-emerald-600 hover:to-teal-700 hover:scale-[1.02] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        >
                            {{ apiKeyPresent ? t('onboarding.next') : t('onboarding.skip') }} →
                        </button>
                    </template>
                    <template v-else-if="step === 3">
                        <button
                            type="button"
                            :disabled="prefsForm.processing"
                            @click="preferencesForm?.requestSubmit()"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:from-emerald-600 hover:to-teal-700 hover:scale-[1.02] disabled:opacity-50 disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        >
                            <svg v-if="prefsForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            {{ prefsForm.processing ? t('onboarding.preferences.saving') : t('onboarding.next') }} →
                        </button>
                    </template>
                    <template v-else-if="step === 4">
                        <button
                            type="button"
                            :disabled="completing"
                            @click="finish"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:from-emerald-600 hover:to-teal-700 hover:scale-[1.02] disabled:opacity-50 disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        >
                            <svg v-if="completing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            {{ t('onboarding.finish') }}
                        </button>
                    </template>
                </div>
            </div>
        </main>
    </div>
</template>
