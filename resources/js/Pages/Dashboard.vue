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
    if (count === 0) return 'bg-gray-100 dark:bg-gray-800/50';
    const max = Math.max(1, heatmapMax.value);
    const ratio = count / max;
    if (ratio < 0.25) return 'bg-emerald-200 dark:bg-emerald-900/50';
    if (ratio < 0.5) return 'bg-emerald-400 dark:bg-emerald-700';
    if (ratio < 0.75) return 'bg-emerald-500 dark:bg-emerald-600';
    return 'bg-emerald-600 dark:bg-emerald-500';
};

const heatmapTitle = (cell: { date: string; count: number }): string =>
    t('dashboard.heatmap.cell', { date: cell.date, count: cell.count });
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                {{ t('dashboard.header') }}
            </h2>
        </template>

        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-teal-50 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950/40">
            <!-- Decorative gradient blobs -->
            <div aria-hidden="true" class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-emerald-300/30 blur-3xl dark:bg-emerald-500/10" />
            <div aria-hidden="true" class="pointer-events-none absolute -left-40 bottom-0 h-[28rem] w-[28rem] rounded-full bg-teal-400/20 blur-3xl dark:bg-teal-500/10" />

            <div class="relative py-10">
                <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

                    <!-- Greeting -->
                    <div class="animate-fade-in-up" style="animation-delay: 0ms;">
                        <h3 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {{ t('dashboard.greeting', { name: page.props.auth.user.name }) }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.subtitle') }}
                        </p>
                    </div>

                <!-- No API key banner -->
                <div
                    v-if="!has_api_key"
                    class="animate-fade-in-up rounded-2xl border border-amber-200/60 bg-amber-50/80 p-4 backdrop-blur-sm dark:border-amber-800/40 dark:bg-amber-900/20"
                    style="animation-delay: 40ms;"
                >
                    <p class="font-semibold text-amber-800 dark:text-amber-200">
                        {{ t('dashboard.no_api_key.title') }}
                    </p>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        {{ t('dashboard.no_api_key.description') }}
                    </p>
                    <Link
                        :href="route('settings.edit')"
                        class="mt-3 inline-flex items-center text-sm font-semibold text-amber-900 underline hover:text-amber-700 dark:text-amber-100 dark:hover:text-amber-300"
                    >
                        {{ t('dashboard.no_api_key.cta') }}
                    </Link>
                </div>

                <!-- Stats grid: 4 cards -->
                <div class="grid animate-fade-in-up gap-4 sm:grid-cols-2 lg:grid-cols-4" style="animation-delay: 80ms;">

                    <!-- Streak card -->
                    <div class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 p-5 shadow-[0_4px_20px_rgb(0,0,0,0.04)] backdrop-blur-sm dark:border-white/5 dark:bg-gray-800/60 dark:shadow-[0_4px_20px_rgb(0,0,0,0.2)]">
                        <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-gradient-to-b from-orange-400 to-amber-500" />
                        <p class="pl-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.streak.title') }}
                        </p>
                        <p class="mt-2 pl-2 flex items-baseline gap-2">
                            <span class="bg-gradient-to-br from-emerald-500 to-teal-600 bg-clip-text text-4xl font-bold tabular-nums text-transparent dark:from-emerald-300 dark:to-teal-400">
                                {{ stats.streak.current }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ t('dashboard.streak.days', stats.streak.current) }}
                            </span>
                        </p>
                        <p class="mt-2 pl-2 text-xs text-gray-400 dark:text-gray-500">
                            {{ stats.streak.last_studied_at ? t('dashboard.streak.last_studied', { when: lastStudiedLabel() }) : t('dashboard.streak.never') }}
                        </p>
                    </div>

                    <!-- Today progress -->
                    <div class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 p-5 shadow-[0_4px_20px_rgb(0,0,0,0.04)] backdrop-blur-sm dark:border-white/5 dark:bg-gray-800/60 dark:shadow-[0_4px_20px_rgb(0,0,0,0.2)]">
                        <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-gradient-to-b from-blue-400 to-sky-500" />
                        <p class="pl-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.today.title') }}
                        </p>
                        <p class="mt-2 pl-2 text-3xl font-bold tabular-nums text-gray-900 dark:text-gray-100">
                            {{ t('dashboard.streak.today_done', { done: reviewedToday, goal: dailyGoal }) }}
                        </p>
                        <div class="mt-3 pl-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="goalMet ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : 'bg-gradient-to-r from-blue-500 to-sky-500'"
                                :style="{ width: `${dailyProgress}%` }"
                            />
                        </div>
                        <p v-if="goalMet" class="mt-2 pl-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                            {{ t('dashboard.streak.goal_met') }}
                        </p>
                        <p v-else-if="dueRemaining > 0" class="mt-2 pl-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.today.due_remaining') }}: <span class="font-semibold text-blue-600 dark:text-blue-400">{{ dueRemaining }}</span>
                        </p>
                    </div>

                    <!-- Due remaining -->
                    <div class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 p-5 shadow-[0_4px_20px_rgb(0,0,0,0.04)] backdrop-blur-sm dark:border-white/5 dark:bg-gray-800/60 dark:shadow-[0_4px_20px_rgb(0,0,0,0.2)]">
                        <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-gradient-to-b from-amber-400 to-orange-500" />
                        <p class="pl-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.today.due_remaining') }}
                        </p>
                        <p class="mt-2 pl-2 text-3xl font-bold tabular-nums text-gray-900 dark:text-gray-100">
                            {{ dueRemaining }}
                        </p>
                        <p class="mt-2 pl-2 text-xs text-gray-400 dark:text-gray-500">
                            {{ t('dashboard.totals.reviews') }}: {{ stats.totals.reviews }}
                        </p>
                    </div>

                    <!-- Retention card -->
                    <div class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 p-5 shadow-[0_4px_20px_rgb(0,0,0,0.04)] backdrop-blur-sm dark:border-white/5 dark:bg-gray-800/60 dark:shadow-[0_4px_20px_rgb(0,0,0,0.2)]">
                        <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-gradient-to-b from-violet-400 to-purple-500" />
                        <p class="pl-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.retention.title') }}
                        </p>
                        <p v-if="retentionPercent !== null" class="mt-2 pl-2 text-3xl font-bold tabular-nums text-gray-900 dark:text-gray-100">
                            {{ retentionPercent }}<span class="text-xl text-gray-400 dark:text-gray-500">%</span>
                        </p>
                        <p v-else class="mt-2 pl-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.retention.no_data') }}
                        </p>
                        <p v-if="retentionPercent !== null" class="mt-2 pl-2 text-xs text-gray-400 dark:text-gray-500">
                            {{ t('dashboard.retention.sample', { n: stats.retention.sample_size }) }}
                        </p>
                    </div>
                </div>

                <!-- Totals row -->
                <div class="grid animate-fade-in-up gap-4 sm:grid-cols-3" style="animation-delay: 140ms;">
                    <div class="rounded-2xl border border-white/60 bg-white/70 px-5 py-4 backdrop-blur-sm dark:border-white/5 dark:bg-gray-800/60">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ t('dashboard.totals.questions') }}</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ stats.totals.questions }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/60 bg-white/70 px-5 py-4 backdrop-blur-sm dark:border-white/5 dark:bg-gray-800/60">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ t('dashboard.totals.reviews') }}</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ stats.totals.reviews }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/60 bg-white/70 px-5 py-4 backdrop-blur-sm dark:border-white/5 dark:bg-gray-800/60">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ t('dashboard.totals.interviews') }}</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ stats.totals.interviews }}</p>
                    </div>
                </div>

                <!-- Heatmap -->
                <div class="animate-fade-in-up rounded-2xl border border-white/60 bg-white/70 p-6 backdrop-blur-sm dark:border-white/5 dark:bg-gray-800/60" style="animation-delay: 200ms;">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ t('dashboard.heatmap.title') }}
                        </h4>
                        <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <span>{{ t('dashboard.heatmap.legend_less') }}</span>
                            <span class="h-3 w-3 rounded-sm bg-gray-100 dark:bg-gray-800/50" />
                            <span class="h-3 w-3 rounded-sm bg-emerald-200 dark:bg-emerald-900/50" />
                            <span class="h-3 w-3 rounded-sm bg-emerald-400 dark:bg-emerald-700" />
                            <span class="h-3 w-3 rounded-sm bg-emerald-500 dark:bg-emerald-600" />
                            <span class="h-3 w-3 rounded-sm bg-emerald-600 dark:bg-emerald-500" />
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
                <div class="grid animate-fade-in-up gap-4 sm:grid-cols-2 lg:grid-cols-4" style="animation-delay: 260ms;">
                    <Link
                        :href="route('study.session')"
                        class="group relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 p-5 shadow-[0_4px_20px_rgb(0,0,0,0.04)] backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:border-white/5 dark:bg-gray-800/60 dark:hover:border-emerald-800/50"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/0 to-teal-500/0 opacity-0 transition-opacity duration-200 group-hover:from-emerald-500/5 group-hover:to-teal-500/5 group-hover:opacity-100" />
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-emerald-700 dark:text-gray-100 dark:group-hover:text-emerald-300">
                            {{ t('dashboard.cta.study') }}
                        </p>
                        <p v-if="dueRemaining > 0" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                            {{ t('dashboard.today.due_remaining') }}: {{ dueRemaining }}
                        </p>
                    </Link>

                    <Link
                        :href="route('interview.show')"
                        class="group relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 p-5 shadow-[0_4px_20px_rgb(0,0,0,0.04)] backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:border-white/5 dark:bg-gray-800/60 dark:hover:border-emerald-800/50"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/0 to-teal-500/0 opacity-0 transition-opacity duration-200 group-hover:from-emerald-500/5 group-hover:to-teal-500/5 group-hover:opacity-100" />
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-emerald-700 dark:text-gray-100 dark:group-hover:text-emerald-300">
                            {{ t('dashboard.cta.interview') }}
                        </p>
                    </Link>

                    <Link
                        :href="route('questions.index')"
                        class="group relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 p-5 shadow-[0_4px_20px_rgb(0,0,0,0.04)] backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:border-white/5 dark:bg-gray-800/60 dark:hover:border-emerald-800/50"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/0 to-teal-500/0 opacity-0 transition-opacity duration-200 group-hover:from-emerald-500/5 group-hover:to-teal-500/5 group-hover:opacity-100" />
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-emerald-700 dark:text-gray-100 dark:group-hover:text-emerald-300">
                            {{ t('dashboard.cta.questions') }}
                        </p>
                    </Link>

                    <Link
                        :href="route('settings.edit')"
                        class="group relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 p-5 shadow-[0_4px_20px_rgb(0,0,0,0.04)] backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:border-white/5 dark:bg-gray-800/60 dark:hover:border-emerald-800/50"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/0 to-teal-500/0 opacity-0 transition-opacity duration-200 group-hover:from-emerald-500/5 group-hover:to-teal-500/5 group-hover:opacity-100" />
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-emerald-700 dark:text-gray-100 dark:group-hover:text-emerald-300">
                            {{ t('dashboard.cta.settings') }}
                        </p>
                    </Link>
                </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
