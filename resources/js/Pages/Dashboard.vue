<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Stats {
    streak: { current: number; last_studied_at: string | null; daily_goal: number };
    today: { reviewed: number; due_remaining: number };
    totals: { questions: number; reviews: number; interviews: number };
    retention: { rate_30d: number | null; sample_size: number };
    heatmap: Array<{ date: string; count: number }>;
}

const props = defineProps<{
    stats: Stats;
    has_api_key: boolean;
}>();

const { t, locale } = useI18n();
const page = usePage();

const dailyGoal = computed(() => props.stats.streak.daily_goal);
const reviewedToday = computed(() => props.stats.today.reviewed);
const goalMet = computed(() => reviewedToday.value >= dailyGoal.value);
const dailyProgress = computed(() =>
    dailyGoal.value === 0 ? 0 : Math.min(100, Math.round((reviewedToday.value / dailyGoal.value) * 100)),
);
const dueRemaining = computed(() => props.stats.today.due_remaining);

const retentionPercent = computed(() => {
    if (props.stats.retention.rate_30d === null) return null;
    return Math.round(props.stats.retention.rate_30d * 100);
});

const heatmapMax = computed(() =>
    props.stats.heatmap.reduce((max, cell) => (cell.count > max ? cell.count : max), 0),
);

function lastStudiedLabel(): string {
    const iso = props.stats.streak.last_studied_at;
    if (!iso) return t('dashboard.streak.never');
    return new Date(iso).toLocaleString(locale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

const dowIndex = (date: string): number => {
    const day = new Date(`${date}T00:00:00`).getDay();
    return (day + 6) % 7;
};

const intensityClass = (count: number): string => {
    if (count === 0) return 'bg-gray-100 dark:bg-gray-700/50';
    const max = Math.max(1, heatmapMax.value);
    const ratio = count / max;
    if (ratio < 0.25) return 'bg-indigo-200 dark:bg-indigo-900';
    if (ratio < 0.5) return 'bg-indigo-400 dark:bg-indigo-700';
    if (ratio < 0.75) return 'bg-indigo-500 dark:bg-indigo-500';
    return 'bg-indigo-700 dark:bg-indigo-400';
};

const heatmapTitle = (cell: { date: string; count: number }): string =>
    t('dashboard.heatmap.cell', { date: cell.date, count: cell.count });
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ t('dashboard.header') }}
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- Greeting -->
                <div>
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ t('dashboard.greeting', { name: page.props.auth.user.name }) }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('dashboard.subtitle') }}
                    </p>
                </div>

                <!-- No API key banner -->
                <div
                    v-if="!has_api_key"
                    class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/30"
                >
                    <p class="font-medium text-amber-800 dark:text-amber-200">
                        {{ t('dashboard.no_api_key.title') }}
                    </p>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        {{ t('dashboard.no_api_key.description') }}
                    </p>
                    <Link
                        :href="route('settings.edit')"
                        class="mt-3 inline-flex items-center text-sm font-medium text-amber-900 underline hover:text-amber-700 dark:text-amber-100 dark:hover:text-amber-300"
                    >
                        {{ t('dashboard.no_api_key.cta') }}
                    </Link>
                </div>

                <!-- Top row: streak + today -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                    <!-- Streak card -->
                    <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-orange-50 to-amber-50 p-6 shadow-sm dark:border-gray-700 dark:from-orange-900/30 dark:to-amber-900/30">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                            {{ t('dashboard.streak.title') }}
                        </p>
                        <p class="mt-2 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-orange-600 dark:text-orange-400">🔥</span>
                            <span class="text-4xl font-bold text-gray-900 dark:text-gray-100">
                                {{ stats.streak.current }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ t('dashboard.streak.days', stats.streak.current) }}
                            </span>
                        </p>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ stats.streak.last_studied_at ? t('dashboard.streak.last_studied', { when: lastStudiedLabel() }) : t('dashboard.streak.never') }}
                        </p>
                    </div>

                    <!-- Today progress -->
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                            {{ t('dashboard.today.title') }}
                        </p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ t('dashboard.streak.today_done', { done: reviewedToday, goal: dailyGoal }) }}
                        </p>
                        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="goalMet ? 'bg-green-500 dark:bg-green-400' : 'bg-indigo-500 dark:bg-indigo-400'"
                                :style="{ width: `${dailyProgress}%` }"
                            />
                        </div>
                        <p v-if="goalMet" class="mt-2 text-xs font-medium text-green-700 dark:text-green-400">
                            {{ t('dashboard.streak.goal_met') }}
                        </p>
                        <p v-else-if="dueRemaining > 0" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.today.due_remaining') }}: <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ dueRemaining }}</span>
                        </p>
                    </div>

                    <!-- Retention card -->
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                            {{ t('dashboard.retention.title') }}
                        </p>
                        <p v-if="retentionPercent !== null" class="mt-2 text-4xl font-bold text-gray-900 dark:text-gray-100">
                            {{ retentionPercent }}<span class="text-2xl text-gray-500 dark:text-gray-400">%</span>
                        </p>
                        <p v-else class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.retention.no_data') }}
                        </p>
                        <p v-if="retentionPercent !== null" class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.retention.sample', { n: stats.retention.sample_size }) }}
                        </p>
                    </div>
                </div>

                <!-- Totals row -->
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ t('dashboard.totals.questions') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ stats.totals.questions }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ t('dashboard.totals.reviews') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ stats.totals.reviews }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ t('dashboard.totals.interviews') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ stats.totals.interviews }}</p>
                    </div>
                </div>

                <!-- Heatmap -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ t('dashboard.heatmap.title') }}
                        </h4>
                        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ t('dashboard.heatmap.legend_less') }}</span>
                            <span class="h-3 w-3 rounded-sm bg-gray-100 dark:bg-gray-700/50" />
                            <span class="h-3 w-3 rounded-sm bg-indigo-200 dark:bg-indigo-900" />
                            <span class="h-3 w-3 rounded-sm bg-indigo-400 dark:bg-indigo-700" />
                            <span class="h-3 w-3 rounded-sm bg-indigo-500 dark:bg-indigo-500" />
                            <span class="h-3 w-3 rounded-sm bg-indigo-700 dark:bg-indigo-400" />
                            <span>{{ t('dashboard.heatmap.legend_more') }}</span>
                        </div>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <div class="grid grid-flow-col grid-rows-7 gap-1" style="grid-auto-columns: 1rem;">
                            <div
                                v-for="cell in stats.heatmap"
                                :key="cell.date"
                                :title="heatmapTitle(cell)"
                                :style="{ gridRowStart: dowIndex(cell.date) + 1 }"
                                :class="intensityClass(cell.count)"
                                class="h-4 w-4 rounded-sm transition-colors"
                            />
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        :href="route('study.session')"
                        class="group rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-indigo-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-400"
                    >
                        <p class="text-base font-semibold text-gray-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400">
                            {{ t('dashboard.cta.study') }}
                        </p>
                        <p v-if="dueRemaining > 0" class="mt-1 text-sm text-indigo-600 dark:text-indigo-400">
                            {{ t('dashboard.today.due_remaining') }}: {{ dueRemaining }}
                        </p>
                    </Link>
                    <Link
                        :href="route('interview.show')"
                        class="group rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-indigo-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-400"
                    >
                        <p class="text-base font-semibold text-gray-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400">
                            {{ t('dashboard.cta.interview') }}
                        </p>
                    </Link>
                    <Link
                        :href="route('questions.index')"
                        class="group rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-indigo-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-400"
                    >
                        <p class="text-base font-semibold text-gray-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400">
                            {{ t('dashboard.cta.questions') }}
                        </p>
                    </Link>
                    <Link
                        :href="route('settings.edit')"
                        class="group rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-indigo-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-400"
                    >
                        <p class="text-base font-semibold text-gray-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400">
                            {{ t('dashboard.cta.settings') }}
                        </p>
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
