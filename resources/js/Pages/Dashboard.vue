<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAnimatedNumber } from '@/composables/useAnimatedNumber';

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
    if (count === 0) return 'bg-gray-100 dark:bg-slate-800/60';
    const max = Math.max(1, heatmapMax.value);
    const ratio = count / max;
    if (ratio < 0.25) return 'bg-emerald-200 dark:bg-emerald-900/70';
    if (ratio < 0.5) return 'bg-emerald-400 dark:bg-emerald-700';
    if (ratio < 0.75) return 'bg-emerald-500 dark:bg-emerald-600';
    return 'bg-emerald-600 dark:bg-emerald-500';
};

const heatmapTitle = (cell: { date: string; count: number }): string =>
    t('dashboard.heatmap.cell', { date: cell.date, count: cell.count });

// Animated counters
const { displayed: animStreak }     = useAnimatedNumber(() => props.stats.streak.current, 1400);
const { displayed: animReviewed }   = useAnimatedNumber(() => props.stats.today.reviewed, 1000);
const { displayed: animDue }        = useAnimatedNumber(() => props.stats.today.due_remaining, 1000);
const { displayed: animRetention }  = useAnimatedNumber(() => retentionPercent.value ?? 0, 1200);
const { displayed: animQuestions }  = useAnimatedNumber(() => props.stats.totals.questions, 1100);
const { displayed: animReviews }    = useAnimatedNumber(() => props.stats.totals.reviews, 1200);
const { displayed: animInterviews } = useAnimatedNumber(() => props.stats.totals.interviews, 1000);
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <!-- Page heading intentionally omitted — the Dashboard has its
             own hero greeting and the white layout bar clashes with the
             animated mesh background below. -->

        <!-- =====================================================================
             ROOT: animated mesh-gradient background
        ====================================================================== -->
        <div class="dashboard-root relative min-h-screen overflow-hidden">

            <!-- Mesh gradient background layer -->
            <div aria-hidden="true" class="dashboard-mesh-bg absolute inset-0 z-0" />

            <!-- Decorative blobs -->
            <div
                aria-hidden="true"
                class="dashboard-blob-1 pointer-events-none absolute z-0 h-[520px] w-[520px] rounded-full"
            />
            <div
                aria-hidden="true"
                class="dashboard-blob-2 pointer-events-none absolute z-0 h-[400px] w-[400px] rounded-full"
            />
            <div
                aria-hidden="true"
                class="dashboard-blob-3 pointer-events-none absolute z-0 h-[300px] w-[300px] rounded-full"
            />

            <!-- ================================================================
                 CONTENT
            ================================================================= -->
            <div class="relative z-10 py-10">
                <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

                    <!-- Greeting -->
                    <div class="animate-fade-in-up" style="animation-delay: 0ms;">
                        <h3 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                            {{ t('dashboard.greeting', { name: page.props.auth.user.name }) }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                            {{ t('dashboard.subtitle') }}
                        </p>
                    </div>

                    <!-- No API key banner -->
                    <div
                        v-if="!has_api_key"
                        class="animate-fade-in-up glass-card rounded-2xl border border-amber-200/60 bg-amber-50/80 p-4 dark:border-amber-700/40 dark:bg-amber-900/20"
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
                            class="mt-3 inline-flex items-center text-sm font-semibold text-amber-900 underline hover:text-amber-700 dark:text-amber-100 dark:hover:text-amber-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                        >
                            {{ t('dashboard.no_api_key.cta') }}
                        </Link>
                    </div>

                    <!-- ===========================================================
                         BENTO GRID — main stats
                         Mobile: single column
                         sm:  2 columns
                         lg:  4 columns, streak spans 2×2
                    ============================================================ -->
                    <div
                        class="animate-fade-in-up grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                        style="animation-delay: 80ms;"
                    >

                        <!-- ── STREAK HERO (2 col × 2 row on lg) ──────────────── -->
                        <div class="streak-card glass-card relative overflow-hidden rounded-3xl p-6 sm:col-span-2 lg:col-span-2 lg:row-span-2">
                            <!-- Rotating conic-gradient border layer -->
                            <div aria-hidden="true" class="streak-border-ring absolute inset-0 rounded-3xl" />
                            <!-- Inner content surface -->
                            <div class="relative z-10 h-full">
                                <!-- Flame icon -->
                                <div class="mb-4 flex items-center gap-3">
                                    <span
                                        class="motion-safe:animate-streak-pulse select-none text-4xl"
                                        role="img"
                                        aria-label="streak flame"
                                    >🔥</span>
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-slate-400">
                                        {{ t('dashboard.streak.title') }}
                                    </p>
                                </div>

                                <!-- Big animated number -->
                                <p class="streak-number bg-gradient-to-br from-emerald-400 via-teal-400 to-cyan-400 bg-clip-text text-7xl font-black tabular-nums leading-none text-transparent dark:from-emerald-300 dark:via-teal-300 dark:to-cyan-300">
                                    {{ animStreak }}
                                </p>
                                <p class="mt-1 text-base font-medium text-gray-600 dark:text-slate-300">
                                    {{ t('dashboard.streak.days', stats.streak.current) }}
                                </p>

                                <!-- Last studied -->
                                <p class="mt-4 text-xs text-gray-400 dark:text-slate-500">
                                    {{
                                        stats.streak.last_studied_at
                                            ? t('dashboard.streak.last_studied', { when: lastStudiedLabel() })
                                            : t('dashboard.streak.never')
                                    }}
                                </p>

                                <!-- Daily progress bar -->
                                <div class="mt-5">
                                    <div class="mb-1.5 flex items-center justify-between text-xs">
                                        <span class="font-medium text-gray-500 dark:text-slate-400">
                                            {{ t('dashboard.streak.today_done', { done: animReviewed, goal: dailyGoal }) }}
                                        </span>
                                        <span
                                            v-if="goalMet"
                                            class="font-semibold text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{ t('dashboard.streak.goal_met') }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-gray-400 dark:text-slate-500"
                                        >{{ dailyProgress }}%</span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100/80 dark:bg-slate-700/60">
                                        <div
                                            class="progress-bar h-full rounded-full transition-all duration-700"
                                            :class="goalMet ? 'progress-bar--shimmer bg-gradient-to-r from-emerald-400 via-teal-400 to-cyan-500' : 'bg-gradient-to-r from-emerald-500 to-teal-500'"
                                            :style="{ width: `${dailyProgress}%` }"
                                            role="progressbar"
                                            :aria-valuenow="dailyProgress"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── TODAY REVIEWED ────────────────────────────────── -->
                        <div class="glass-card metric-card relative overflow-hidden rounded-2xl p-5 group">
                            <div class="metric-card__accent bg-gradient-to-b from-sky-400 to-blue-500" />
                            <p class="pl-3 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-slate-400">
                                {{ t('dashboard.today.title') }}
                            </p>
                            <p class="mt-2 pl-3 text-4xl font-black tabular-nums text-gray-900 dark:text-white">
                                {{ animReviewed }}
                            </p>
                            <p class="mt-1 pl-3 text-xs text-gray-400 dark:text-slate-500">
                                {{ t('dashboard.today.due_remaining') }}: <span class="font-semibold text-sky-600 dark:text-sky-400">{{ animDue }}</span>
                            </p>
                            <!-- Hover sweep -->
                            <div aria-hidden="true" class="metric-card__sweep bg-gradient-to-br from-sky-500/10 to-blue-500/10" />
                        </div>

                        <!-- ── DUE REMAINING ──────────────────────────────────── -->
                        <div class="glass-card metric-card relative overflow-hidden rounded-2xl p-5 group">
                            <div class="metric-card__accent bg-gradient-to-b from-amber-400 to-orange-500" />
                            <p class="pl-3 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-slate-400">
                                {{ t('dashboard.today.due_remaining') }}
                            </p>
                            <p class="mt-2 pl-3 text-4xl font-black tabular-nums text-gray-900 dark:text-white">
                                {{ animDue }}
                            </p>
                            <p class="mt-1 pl-3 text-xs text-gray-400 dark:text-slate-500">
                                {{ t('dashboard.totals.reviews') }}: {{ animReviews }}
                            </p>
                            <div aria-hidden="true" class="metric-card__sweep bg-gradient-to-br from-amber-500/10 to-orange-500/10" />
                        </div>

                        <!-- ── RETENTION ──────────────────────────────────────── -->
                        <div class="glass-card metric-card relative overflow-hidden rounded-2xl p-5 group sm:col-span-2 lg:col-span-1">
                            <div class="metric-card__accent bg-gradient-to-b from-violet-400 to-purple-500" />
                            <p class="pl-3 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-slate-400">
                                {{ t('dashboard.retention.title') }}
                            </p>
                            <p
                                v-if="retentionPercent !== null"
                                class="mt-2 pl-3 text-4xl font-black tabular-nums text-gray-900 dark:text-white"
                            >
                                {{ animRetention }}<span class="text-xl text-gray-400 dark:text-slate-500">%</span>
                            </p>
                            <p v-else class="mt-2 pl-3 text-sm text-gray-500 dark:text-slate-400">
                                {{ t('dashboard.retention.no_data') }}
                            </p>
                            <p v-if="retentionPercent !== null" class="mt-1 pl-3 text-xs text-gray-400 dark:text-slate-500">
                                {{ t('dashboard.retention.sample', { n: stats.retention.sample_size }) }}
                            </p>
                            <div aria-hidden="true" class="metric-card__sweep bg-gradient-to-br from-violet-500/10 to-purple-500/10" />
                        </div>

                    </div><!-- /bento grid -->

                    <!-- ===========================================================
                         TOTALS ROW
                    ============================================================ -->
                    <div
                        class="animate-fade-in-up grid gap-4 sm:grid-cols-3"
                        style="animation-delay: 140ms;"
                    >
                        <div class="glass-card total-card group relative overflow-hidden rounded-2xl px-5 py-4">
                            <div class="total-card__glow" />
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">
                                {{ t('dashboard.totals.questions') }}
                            </p>
                            <p class="mt-1 text-3xl font-black tabular-nums text-gray-900 dark:text-white">
                                {{ animQuestions }}
                            </p>
                        </div>
                        <div class="glass-card total-card group relative overflow-hidden rounded-2xl px-5 py-4">
                            <div class="total-card__glow" />
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">
                                {{ t('dashboard.totals.reviews') }}
                            </p>
                            <p class="mt-1 text-3xl font-black tabular-nums text-gray-900 dark:text-white">
                                {{ animReviews }}
                            </p>
                        </div>
                        <div class="glass-card total-card group relative overflow-hidden rounded-2xl px-5 py-4">
                            <div class="total-card__glow" />
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">
                                {{ t('dashboard.totals.interviews') }}
                            </p>
                            <p class="mt-1 text-3xl font-black tabular-nums text-gray-900 dark:text-white">
                                {{ animInterviews }}
                            </p>
                        </div>
                    </div>

                    <!-- ===========================================================
                         HEATMAP
                    ============================================================ -->
                    <div
                        class="animate-fade-in-up glass-card rounded-2xl p-6"
                        style="animation-delay: 200ms;"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-slate-200">
                                {{ t('dashboard.heatmap.title') }}
                            </h4>
                            <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-slate-500">
                                <span>{{ t('dashboard.heatmap.legend_less') }}</span>
                                <span class="h-3 w-3 rounded-sm bg-gray-100 dark:bg-slate-800/60" />
                                <span class="h-3 w-3 rounded-sm bg-emerald-200 dark:bg-emerald-900/70" />
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
                                    :aria-label="heatmapTitle(cell)"
                                    :style="{ gridRowStart: dowIndex(cell.date) + 1 }"
                                    :class="intensityClass(cell.count)"
                                    class="h-4 w-4 cursor-default rounded-sm transition-colors duration-200 hover:scale-110 hover:brightness-110"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ===========================================================
                         QUICK ACTIONS
                    ============================================================ -->
                    <div
                        class="animate-fade-in-up grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                        style="animation-delay: 260ms;"
                    >

                        <!-- Study -->
                        <Link
                            :href="route('study.session')"
                            class="action-card glass-card group relative overflow-hidden rounded-2xl p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        >
                            <div aria-hidden="true" class="action-card__sweep bg-gradient-to-br from-emerald-500/15 to-teal-500/15" />
                            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 transition-transform duration-200 group-hover:scale-110 group-hover:bg-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300 dark:group-hover:bg-emerald-800/60">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 transition-colors duration-200 group-hover:text-emerald-700 dark:text-white dark:group-hover:text-emerald-300">
                                {{ t('dashboard.cta.study') }}
                            </p>
                            <p v-if="dueRemaining > 0" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                                {{ t('dashboard.today.due_remaining') }}: {{ dueRemaining }}
                            </p>
                        </Link>

                        <!-- Interview -->
                        <Link
                            :href="route('interview.show')"
                            class="action-card glass-card group relative overflow-hidden rounded-2xl p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                        >
                            <div aria-hidden="true" class="action-card__sweep bg-gradient-to-br from-violet-500/15 to-purple-500/15" />
                            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-600 transition-transform duration-200 group-hover:scale-110 group-hover:bg-violet-200 dark:bg-violet-900/50 dark:text-violet-300 dark:group-hover:bg-violet-800/60">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 transition-colors duration-200 group-hover:text-violet-700 dark:text-white dark:group-hover:text-violet-300">
                                {{ t('dashboard.cta.interview') }}
                            </p>
                        </Link>

                        <!-- Questions -->
                        <Link
                            :href="route('questions.index')"
                            class="action-card glass-card group relative overflow-hidden rounded-2xl p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                        >
                            <div aria-hidden="true" class="action-card__sweep bg-gradient-to-br from-cyan-500/15 to-sky-500/15" />
                            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600 transition-transform duration-200 group-hover:scale-110 group-hover:bg-cyan-200 dark:bg-cyan-900/50 dark:text-cyan-300 dark:group-hover:bg-cyan-800/60">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 transition-colors duration-200 group-hover:text-cyan-700 dark:text-white dark:group-hover:text-cyan-300">
                                {{ t('dashboard.cta.questions') }}
                            </p>
                        </Link>

                        <!-- Settings -->
                        <Link
                            :href="route('settings.edit')"
                            class="action-card glass-card group relative overflow-hidden rounded-2xl p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
                        >
                            <div aria-hidden="true" class="action-card__sweep bg-gradient-to-br from-slate-500/10 to-gray-500/10" />
                            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-600 transition-transform duration-200 group-hover:scale-110 group-hover:bg-gray-200 dark:bg-slate-700/60 dark:text-slate-300 dark:group-hover:bg-slate-600/60">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 transition-colors duration-200 group-hover:text-gray-700 dark:text-white dark:group-hover:text-slate-300">
                                {{ t('dashboard.cta.settings') }}
                            </p>
                        </Link>

                    </div><!-- /quick actions -->

                </div>
            </div>

        </div><!-- /dashboard-root -->
    </AuthenticatedLayout>
</template>

<style scoped>
/* ===========================================================================
   MESH GRADIENT BACKGROUND
   Multi-stop gradient that slowly animates background-position to give the
   impression of a living mesh. Light and dark variants.
=========================================================================== */
.dashboard-root {
    background-color: #f0fdf8; /* light base */
}

.dark .dashboard-root {
    background-color: #060a0e;
}

.dashboard-mesh-bg {
    background: linear-gradient(
        135deg,
        #d1fae5 0%,
        #99f6e4 15%,
        #a5f3fc 30%,
        #e0e7ff 45%,
        #f0fdf4 60%,
        #ccfbf1 75%,
        #d1fae5 100%
    );
    background-size: 400% 400%;
    animation: mesh-shift 28s ease infinite;
    opacity: 0.8;
}

.dark .dashboard-mesh-bg {
    background: linear-gradient(
        135deg,
        #022c22 0%,
        #0d3d2e 12%,
        #083344 24%,
        #1e1b4b 36%,
        #0f172a 50%,
        #052e16 62%,
        #0c4a3c 74%,
        #1a3a5c 86%,
        #022c22 100%
    );
    background-size: 400% 400%;
    opacity: 1;
}

@keyframes mesh-shift {
    0%   { background-position: 0% 50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-mesh-bg {
        animation: none;
        background-position: 0% 50%;
    }
}

/* ===========================================================================
   DECORATIVE BLOBS
=========================================================================== */
.dashboard-blob-1 {
    top: -160px;
    right: -140px;
    background: radial-gradient(circle, rgba(52, 211, 153, 0.35) 0%, transparent 70%);
    filter: blur(64px);
    animation: blob-drift-1 25s ease-in-out infinite;
}

.dark .dashboard-blob-1 {
    background: radial-gradient(circle, rgba(16, 185, 129, 0.18) 0%, transparent 70%);
}

.dashboard-blob-2 {
    bottom: 80px;
    left: -120px;
    background: radial-gradient(circle, rgba(34, 211, 238, 0.28) 0%, transparent 70%);
    filter: blur(56px);
    animation: blob-drift-2 32s ease-in-out infinite;
}

.dark .dashboard-blob-2 {
    background: radial-gradient(circle, rgba(6, 182, 212, 0.15) 0%, transparent 70%);
}

.dashboard-blob-3 {
    top: 40%;
    left: 45%;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
    filter: blur(48px);
    animation: blob-drift-3 20s ease-in-out infinite;
}

.dark .dashboard-blob-3 {
    background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, transparent 70%);
}

@keyframes blob-drift-1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%       { transform: translate(-30px, 40px) scale(1.08); }
    66%       { transform: translate(20px, -20px) scale(0.95); }
}

@keyframes blob-drift-2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    40%       { transform: translate(40px, -30px) scale(1.1); }
    70%       { transform: translate(-20px, 20px) scale(0.92); }
}

@keyframes blob-drift-3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50%       { transform: translate(-40px, 30px) scale(1.12); }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-blob-1,
    .dashboard-blob-2,
    .dashboard-blob-3 {
        animation: none;
    }
}

/* ===========================================================================
   GLASS CARD — base glassmorphism token
   Strong backdrop-blur + gradient border via pseudo-element
=========================================================================== */
.glass-card {
    position: relative;
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(24px) saturate(200%);
    -webkit-backdrop-filter: blur(24px) saturate(200%);
    border: 1px solid rgba(16, 185, 129, 0.18);
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.9) inset,
        0 8px 24px -8px rgba(15, 118, 110, 0.18),
        0 2px 6px -2px rgba(15, 23, 42, 0.08),
        0 0 0 1px rgba(255, 255, 255, 0.4);
    transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
}

.dark .glass-card {
    background: rgba(15, 23, 42, 0.78);
    border: 1px solid rgba(16, 185, 129, 0.22);
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.06) inset,
        0 16px 32px -12px rgba(0, 0, 0, 0.6),
        0 4px 12px -2px rgba(0, 0, 0, 0.35),
        0 0 0 1px rgba(16, 185, 129, 0.08);
}

.glass-card:hover {
    transform: translateY(-3px) scale(1.008);
    border-color: rgba(16, 185, 129, 0.35);
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.9) inset,
        0 20px 40px -12px rgba(15, 118, 110, 0.28),
        0 6px 14px -4px rgba(15, 23, 42, 0.12),
        0 0 0 1px rgba(16, 185, 129, 0.18);
}

.dark .glass-card:hover {
    border-color: rgba(16, 185, 129, 0.45);
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.08) inset,
        0 24px 48px -12px rgba(0, 0, 0, 0.75),
        0 8px 16px -4px rgba(0, 0, 0, 0.45),
        0 0 0 1px rgba(16, 185, 129, 0.25),
        0 0 32px rgba(16, 185, 129, 0.15);
}

/* ===========================================================================
   STREAK CARD — conic-gradient rotating border
=========================================================================== */
.streak-card {
    background: rgba(255, 255, 255, 0.88);
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.9) inset,
        0 16px 40px -12px rgba(16, 185, 129, 0.25),
        0 6px 16px -4px rgba(15, 23, 42, 0.1),
        0 0 0 1px rgba(16, 185, 129, 0.2);
}

.dark .streak-card {
    background: rgba(9, 17, 28, 0.85);
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.07) inset,
        0 24px 56px -12px rgba(0, 0, 0, 0.7),
        0 0 60px rgba(16, 185, 129, 0.12),
        0 0 0 1px rgba(16, 185, 129, 0.18);
}

.dark .streak-card:hover {
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.08) inset,
        0 32px 64px -12px rgba(0, 0, 0, 0.8),
        0 0 96px rgba(16, 185, 129, 0.22),
        0 0 0 1px rgba(16, 185, 129, 0.35);
}

/* Rotating border via a pseudo-element underneath the card content.
   We overlay a conic-gradient absolutely, then inset 2px to show the ring. */
.streak-border-ring {
    z-index: 0;
    padding: 2px;
    background: conic-gradient(
        from var(--streak-angle, 0deg),
        #10b981,
        #06b6d4,
        #6366f1,
        #8b5cf6,
        #10b981
    );
    animation: rotate-border 6s linear infinite;
    -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    border-radius: inherit;
}

@keyframes rotate-border {
    to { --streak-angle: 360deg; }
}

@media (prefers-reduced-motion: reduce) {
    .streak-border-ring {
        animation: none;
        background: linear-gradient(135deg, #10b981, #06b6d4, #6366f1);
    }
}

/* Large gradient number glow in dark */
.dark .streak-number {
    filter: drop-shadow(0 0 20px rgba(52, 211, 153, 0.4));
}

/* Flame pulse */
@keyframes streak-pulse {
    0%, 100% { transform: scale(1) rotate(-3deg); }
    50%       { transform: scale(1.15) rotate(3deg); }
}

.motion-safe\:animate-streak-pulse {
    animation: streak-pulse 2.4s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .motion-safe\:animate-streak-pulse {
        animation: none;
    }
}

/* ===========================================================================
   PROGRESS BAR SHIMMER
=========================================================================== */
.progress-bar--shimmer {
    position: relative;
    overflow: hidden;
}

.progress-bar--shimmer::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(255, 255, 255, 0.55) 45%,
        rgba(255, 255, 255, 0.8) 50%,
        rgba(255, 255, 255, 0.55) 55%,
        transparent 100%
    );
    animation: shimmer-slide 2.2s ease-in-out infinite;
}

@keyframes shimmer-slide {
    0%   { transform: translateX(-120%); }
    100% { transform: translateX(220%); }
}

@media (prefers-reduced-motion: reduce) {
    .progress-bar--shimmer::after {
        animation: none;
    }
}

/* ===========================================================================
   METRIC CARDS (today / due / retention)
=========================================================================== */
.metric-card__accent {
    position: absolute;
    inset-block: 0;
    left: 0;
    width: 4px;
    border-radius: 2px 0 0 2px;
}

.metric-card__sweep {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 220ms ease;
    border-radius: inherit;
    pointer-events: none;
}

.metric-card:hover .metric-card__sweep {
    opacity: 1;
}

.dark .metric-card:hover {
    box-shadow:
        0 12px 48px rgba(0, 0, 0, 0.6),
        0 0 0 1px rgba(255, 255, 255, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.07);
}

/* ===========================================================================
   TOTAL CARDS — subtle inner glow on hover
=========================================================================== */
.total-card__glow {
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: radial-gradient(ellipse at 50% 0%, rgba(16, 185, 129, 0.12) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 300ms ease;
    pointer-events: none;
}

.total-card:hover .total-card__glow {
    opacity: 1;
}

.dark .total-card:hover {
    box-shadow:
        0 12px 48px rgba(0, 0, 0, 0.6),
        0 0 40px rgba(16, 185, 129, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.07);
}

/* ===========================================================================
   ACTION CARDS
=========================================================================== */
.action-card {
    cursor: pointer;
    text-decoration: none;
}

.action-card__sweep {
    position: absolute;
    inset: 0;
    opacity: 0;
    border-radius: inherit;
    transition: opacity 240ms ease;
    pointer-events: none;
}

.action-card:hover .action-card__sweep {
    opacity: 1;
}

.action-card:hover {
    transform: translateY(-3px) scale(1.02);
}

.action-card:active {
    transform: scale(0.97);
    transition-duration: 80ms;
}
</style>
