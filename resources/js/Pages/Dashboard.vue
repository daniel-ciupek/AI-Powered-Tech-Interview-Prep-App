<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useAnimatedNumber } from '@/composables/useAnimatedNumber';
import { useMilestoneConfetti } from '@/composables/useMilestoneConfetti';
import { vCardTilt } from '@/directives/cardTilt';
import { vMagneticButton } from '@/directives/magneticButton';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
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

const todayIso = computed(() => {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
});

function lastStudiedLabel(): string {
    const iso = props.stats.streak.last_studied_at;
    if (!iso) return t('dashboard.streak.never');
    return new Date(iso).toLocaleString(locale.value, { dateStyle: 'medium', timeStyle: 'short' });
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

// Animated counters — slightly longer durations for a more dramatic effect
const { displayed: animStreak }     = useAnimatedNumber(() => props.stats.streak.current, 1800);
const { displayed: animReviewed }   = useAnimatedNumber(() => props.stats.today.reviewed, 1400);
const { displayed: animDue }        = useAnimatedNumber(() => props.stats.today.due_remaining, 1400);
const { displayed: animRetention }  = useAnimatedNumber(() => retentionPercent.value ?? 0, 1600);
const { displayed: animQuestions }  = useAnimatedNumber(() => props.stats.totals.questions, 1500);
const { displayed: animReviews }    = useAnimatedNumber(() => props.stats.totals.reviews, 1600);
const { displayed: animInterviews } = useAnimatedNumber(() => props.stats.totals.interviews, 1400);

// Spotlight / focus mode
const focusedCard = ref<string | null>(null);

function toggleFocus(cardId: string): void {
    focusedCard.value = focusedCard.value === cardId ? null : cardId;
}

function clearFocus(): void {
    focusedCard.value = null;
}

function spotlightClass(cardId: string): string {
    if (!focusedCard.value) return '';
    return focusedCard.value === cardId ? 'card-focused' : 'card-dimmed';
}

// Avatar initials
const userName = computed(() => (page.props.auth.user as { name: string }).name ?? '');
const initials = computed(() => {
    const parts = userName.value.trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    return parts[0].slice(0, 2).toUpperCase();
});

// Particles on streak when current streak is meaningful (> 6 days)
const showStreakParticles = computed(() => props.stats.streak.current > 6);
const particleIndices = computed(() => Array.from({ length: 14 }, (_, i) => i));

// Background sparks — page-wide drifting particles (always on)
const sparkIndices = Array.from({ length: 22 }, (_, i) => i);

// Cursor spotlight — track mouse on dashboard root, update CSS variables
const dashRoot = ref<HTMLElement | null>(null);
function onDashMouseMove(e: MouseEvent): void {
    if (!dashRoot.value) return;
    const rect = dashRoot.value.getBoundingClientRect();
    dashRoot.value.style.setProperty('--mx', `${e.clientX - rect.left}px`);
    dashRoot.value.style.setProperty('--my', `${e.clientY - rect.top}px`);
}

// Milestone confetti — fires once per milestone (7, 14, 30, 60, 100, 200, 365)
onMounted(() => {
    const { fire } = useMilestoneConfetti();
    // small delay so user lands and sees aurora first, then party
    window.setTimeout(() => fire(props.stats.streak.current), 650);
});
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>

        <!-- =====================================================================
             ROOT: animated mesh-gradient background + cursor spotlight
        ====================================================================== -->
        <div
            ref="dashRoot"
            class="dashboard-root relative min-h-screen overflow-hidden"
            @click="clearFocus"
            @mousemove="onDashMouseMove"
        >

            <!-- Mesh gradient background layer -->
            <div aria-hidden="true" class="dashboard-mesh-bg absolute inset-0 z-0" />

            <!-- Decorative blobs -->
            <div aria-hidden="true" class="dashboard-blob-1 pointer-events-none absolute z-0 h-[520px] w-[520px] rounded-full" />
            <div aria-hidden="true" class="dashboard-blob-2 pointer-events-none absolute z-0 h-[400px] w-[400px] rounded-full" />
            <div aria-hidden="true" class="dashboard-blob-3 pointer-events-none absolute z-0 h-[300px] w-[300px] rounded-full" />
            <div aria-hidden="true" class="dashboard-blob-4 pointer-events-none absolute z-0 h-[460px] w-[460px] rounded-full" />
            <div aria-hidden="true" class="dashboard-blob-5 pointer-events-none absolute z-0 h-[360px] w-[360px] rounded-full" />

            <!-- Background floating sparks (page-wide drifting particles) -->
            <div aria-hidden="true" class="bg-sparks pointer-events-none absolute inset-0 z-0">
                <span
                    v-for="i in sparkIndices"
                    :key="i"
                    class="bg-spark"
                    :data-color="['emerald', 'teal', 'cyan', 'violet', 'pink'][i % 5]"
                    :style="{
                        left: `${(i * 17 + 7) % 95}%`,
                        top: `${(i * 23 + 11) % 88}%`,
                        animationDelay: `${(i * 0.7) % 8}s`,
                        animationDuration: `${6 + (i % 5) * 1.4}s`,
                    }"
                />
            </div>

            <!-- Cursor spotlight (follows mouse, illuminates dashboard) -->
            <div aria-hidden="true" class="cursor-spotlight pointer-events-none absolute inset-0 z-[1]" />

            <!-- ================================================================
                 CONTENT
            ================================================================= -->
            <div class="relative z-10 py-10">
                <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

                    <!-- ── GREETING ──────────────────────────────────────── -->
                    <div class="greeting-section animate-fade-in-up flex items-center gap-5" style="animation-delay: 0ms;">
                        <!-- Avatar (3D tilt + glow) -->
                        <div class="relative shrink-0">
                            <div
                                v-card-tilt="{ maxTilt: 14, scale: 1.06, perspective: 600 }"
                                class="avatar-glow flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 via-teal-500 to-cyan-500 shadow-lg shadow-emerald-500/30 dark:shadow-emerald-500/20"
                            >
                                <span class="select-none text-xl font-black text-white">{{ initials }}</span>
                            </div>
                            <div class="avatar-status absolute -bottom-1 -right-1 h-4 w-4 rounded-full border-2 border-white bg-emerald-400 dark:border-slate-900" />
                        </div>
                        <!-- Text -->
                        <div>
                            <h3 class="name-shimmer bg-gradient-to-r from-gray-900 via-emerald-600 to-teal-600 bg-clip-text text-3xl font-black leading-tight text-transparent dark:from-white dark:via-emerald-300 dark:to-teal-300">
                                {{ t('dashboard.greeting', { name: userName }) }}
                            </h3>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">
                                {{ t('dashboard.subtitle') }}
                            </p>
                        </div>
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
                         Mobile: single column | sm: 2 col | lg: 4 col (streak 2×2)
                    ============================================================ -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                        <!-- ── STREAK HERO (2 col × 2 row on lg) ──────────────── -->
                        <div
                            v-card-tilt="{ maxTilt: 5, scale: 1.015 }"
                            class="streak-card glass-card animate-fade-in-up relative cursor-pointer overflow-hidden rounded-3xl p-6 sm:col-span-2 lg:col-span-2 lg:row-span-2"
                            style="animation-delay: 80ms;"
                            :class="spotlightClass('streak')"
                            @click.stop="toggleFocus('streak')"
                        >
                            <div aria-hidden="true" class="streak-border-ring absolute inset-0 rounded-3xl" />

                            <!-- Living particles (only when streak > 6) -->
                            <div
                                v-if="showStreakParticles"
                                aria-hidden="true"
                                class="streak-particles pointer-events-none absolute inset-x-0 bottom-0 h-40 overflow-hidden"
                            >
                                <span
                                    v-for="i in particleIndices"
                                    :key="i"
                                    class="streak-particle absolute h-1.5 w-1.5 rounded-full"
                                    :style="{
                                        left: `${(i * 7.4 + 3) % 96}%`,
                                        animationDelay: `${(i * 0.42) % 3.4}s`,
                                        animationDuration: `${3 + (i % 4) * 0.6}s`,
                                    }"
                                />
                            </div>

                            <div class="relative z-10 h-full">
                                <div class="mb-4 flex items-center gap-3">
                                    <span class="motion-safe:animate-streak-pulse select-none text-4xl" role="img" aria-label="streak flame">🔥</span>
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-slate-400">
                                        {{ t('dashboard.streak.title') }}
                                    </p>
                                </div>

                                <p class="streak-number number-breathe bg-gradient-to-br from-emerald-400 via-teal-400 to-cyan-400 bg-clip-text text-7xl font-black tabular-nums leading-none text-transparent dark:from-emerald-300 dark:via-teal-300 dark:to-cyan-300">
                                    {{ animStreak }}
                                </p>
                                <p class="mt-1 text-base font-medium text-gray-600 dark:text-slate-300">
                                    {{ t('dashboard.streak.days', stats.streak.current) }}
                                </p>

                                <p class="mt-4 text-xs text-gray-400 dark:text-slate-500">
                                    {{
                                        stats.streak.last_studied_at
                                            ? t('dashboard.streak.last_studied', { when: lastStudiedLabel() })
                                            : t('dashboard.streak.never')
                                    }}
                                </p>

                                <div class="mt-5">
                                    <div class="mb-1.5 flex items-center justify-between text-xs">
                                        <span class="font-medium text-gray-500 dark:text-slate-400">
                                            {{ t('dashboard.streak.today_done', { done: animReviewed, goal: dailyGoal }) }}
                                        </span>
                                        <span v-if="goalMet" class="font-semibold text-emerald-600 dark:text-emerald-400">
                                            {{ t('dashboard.streak.goal_met') }}
                                        </span>
                                        <span v-else class="text-gray-400 dark:text-slate-500">{{ dailyProgress }}%</span>
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
                        <div
                            v-card-tilt
                            data-glow="sky"
                            class="glass-card metric-card animate-fade-in-up group relative cursor-pointer overflow-hidden rounded-2xl p-5"
                            style="animation-delay: 130ms;"
                            :class="spotlightClass('today')"
                            @click.stop="toggleFocus('today')"
                        >
                            <div class="metric-card__accent bg-gradient-to-b from-sky-400 to-blue-500" />
                            <p class="pl-3 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-slate-400">
                                {{ t('dashboard.today.title') }}
                            </p>
                            <p class="number-breathe mt-2 pl-3 text-4xl font-black tabular-nums text-gray-900 dark:text-white">
                                {{ animReviewed }}
                            </p>
                            <p class="mt-1 pl-3 text-xs text-gray-400 dark:text-slate-500">
                                {{ t('dashboard.today.due_remaining') }}: <span class="font-semibold text-sky-600 dark:text-sky-400">{{ animDue }}</span>
                            </p>
                            <div aria-hidden="true" class="metric-card__sweep bg-gradient-to-br from-sky-500/10 to-blue-500/10" />
                        </div>

                        <!-- ── DUE REMAINING ──────────────────────────────────── -->
                        <div
                            v-card-tilt
                            data-glow="amber"
                            class="glass-card metric-card animate-fade-in-up group relative cursor-pointer overflow-hidden rounded-2xl p-5"
                            style="animation-delay: 175ms;"
                            :class="spotlightClass('due')"
                            @click.stop="toggleFocus('due')"
                        >
                            <div class="metric-card__accent bg-gradient-to-b from-amber-400 to-orange-500" />
                            <p class="pl-3 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-slate-400">
                                {{ t('dashboard.today.due_remaining') }}
                            </p>
                            <p class="number-breathe mt-2 pl-3 text-4xl font-black tabular-nums text-gray-900 dark:text-white">
                                {{ animDue }}
                            </p>
                            <p class="mt-1 pl-3 text-xs text-gray-400 dark:text-slate-500">
                                {{ t('dashboard.totals.reviews') }}: {{ animReviews }}
                            </p>
                            <div aria-hidden="true" class="metric-card__sweep bg-gradient-to-br from-amber-500/10 to-orange-500/10" />
                        </div>

                        <!-- ── RETENTION (spans full bottom-right row on lg) ──── -->
                        <div
                            v-card-tilt="{ maxTilt: 5 }"
                            data-glow="violet"
                            class="glass-card metric-card animate-fade-in-up group relative cursor-pointer overflow-hidden rounded-2xl p-5 sm:col-span-2 lg:col-span-2"
                            style="animation-delay: 220ms;"
                            :class="spotlightClass('retention')"
                            @click.stop="toggleFocus('retention')"
                        >
                            <div class="metric-card__accent bg-gradient-to-b from-violet-400 to-purple-500" />
                            <p class="pl-3 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-slate-400">
                                {{ t('dashboard.retention.title') }}
                            </p>
                            <p v-if="retentionPercent !== null" class="mt-2 pl-3 text-4xl font-black tabular-nums text-gray-900 dark:text-white">
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
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div
                            v-card-tilt="{ maxTilt: 6 }"
                            class="glass-card total-card animate-fade-in-up group relative cursor-pointer overflow-hidden rounded-2xl px-5 py-4"
                            style="animation-delay: 265ms;"
                            :class="spotlightClass('total-q')"
                            @click.stop="toggleFocus('total-q')"
                        >
                            <div class="total-card__glow" />
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ t('dashboard.totals.questions') }}</p>
                            <p class="number-breathe mt-1 text-3xl font-black tabular-nums text-gray-900 dark:text-white">{{ animQuestions }}</p>
                        </div>
                        <div
                            v-card-tilt="{ maxTilt: 6 }"
                            class="glass-card total-card animate-fade-in-up group relative cursor-pointer overflow-hidden rounded-2xl px-5 py-4"
                            style="animation-delay: 300ms;"
                            :class="spotlightClass('total-r')"
                            @click.stop="toggleFocus('total-r')"
                        >
                            <div class="total-card__glow" />
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ t('dashboard.totals.reviews') }}</p>
                            <p class="number-breathe mt-1 text-3xl font-black tabular-nums text-gray-900 dark:text-white">{{ animReviews }}</p>
                        </div>
                        <div
                            v-card-tilt="{ maxTilt: 6 }"
                            class="glass-card total-card animate-fade-in-up group relative cursor-pointer overflow-hidden rounded-2xl px-5 py-4"
                            style="animation-delay: 335ms;"
                            :class="spotlightClass('total-i')"
                            @click.stop="toggleFocus('total-i')"
                        >
                            <div class="total-card__glow" />
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ t('dashboard.totals.interviews') }}</p>
                            <p class="number-breathe mt-1 text-3xl font-black tabular-nums text-gray-900 dark:text-white">{{ animInterviews }}</p>
                        </div>
                    </div>

                    <!-- ===========================================================
                         HEATMAP
                    ============================================================ -->
                    <div
                        class="animate-fade-in-up glass-card rounded-2xl p-6"
                        style="animation-delay: 380ms;"
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
                                    :class="[intensityClass(cell.count), { 'heatmap-today': cell.date === todayIso }]"
                                    class="h-4 w-4 cursor-default rounded-sm transition-colors duration-200 hover:scale-110 hover:brightness-110"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ===========================================================
                         QUICK ACTIONS
                    ============================================================ -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                        <Link
                            v-magnetic-button
                            :href="route('study.session')"
                            class="action-card glass-card animate-fade-in-up group relative overflow-hidden rounded-2xl p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                            style="animation-delay: 420ms;"
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

                        <Link
                            v-magnetic-button
                            :href="route('interview.show')"
                            class="action-card glass-card animate-fade-in-up group relative overflow-hidden rounded-2xl p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                            style="animation-delay: 455ms;"
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

                        <Link
                            v-magnetic-button
                            :href="route('questions.index')"
                            class="action-card glass-card animate-fade-in-up group relative overflow-hidden rounded-2xl p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                            style="animation-delay: 490ms;"
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

                        <Link
                            v-magnetic-button
                            :href="route('settings.edit')"
                            class="action-card glass-card animate-fade-in-up group relative overflow-hidden rounded-2xl p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
                            style="animation-delay: 525ms;"
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
   CURSOR SPOTLIGHT — radial gradient follows mouse across dashboard
=========================================================================== */
.cursor-spotlight {
    background: radial-gradient(
        circle 480px at var(--mx, 50%) var(--my, 50%),
        rgba(16, 185, 129, 0.18) 0%,
        rgba(20, 184, 166, 0.10) 25%,
        rgba(6, 182, 212, 0.05) 45%,
        transparent 65%
    );
    mix-blend-mode: screen;
    transition: background 90ms linear;
}

.dark .cursor-spotlight {
    background: radial-gradient(
        circle 520px at var(--mx, 50%) var(--my, 50%),
        rgba(16, 185, 129, 0.28) 0%,
        rgba(20, 184, 166, 0.15) 25%,
        rgba(6, 182, 212, 0.08) 45%,
        transparent 65%
    );
    mix-blend-mode: screen;
}

@media (prefers-reduced-motion: reduce) {
    .cursor-spotlight { display: none; }
}

/* ===========================================================================
   BACKGROUND SPARKS — drifting particles across the whole page
=========================================================================== */
.bg-sparks {
    overflow: hidden;
}

.bg-spark {
    position: absolute;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    opacity: 0;
    animation-name: spark-drift;
    animation-timing-function: ease-in-out;
    animation-iteration-count: infinite;
    pointer-events: none;
}

.bg-spark[data-color="emerald"] {
    background: radial-gradient(circle, rgba(52, 211, 153, 1) 0%, rgba(16, 185, 129, 0.4) 50%, transparent 80%);
    box-shadow: 0 0 10px rgba(52, 211, 153, 0.7), 0 0 20px rgba(16, 185, 129, 0.4);
}
.bg-spark[data-color="teal"] {
    background: radial-gradient(circle, rgba(94, 234, 212, 1) 0%, rgba(20, 184, 166, 0.4) 50%, transparent 80%);
    box-shadow: 0 0 10px rgba(94, 234, 212, 0.7), 0 0 22px rgba(20, 184, 166, 0.4);
}
.bg-spark[data-color="cyan"] {
    background: radial-gradient(circle, rgba(103, 232, 249, 1) 0%, rgba(6, 182, 212, 0.4) 50%, transparent 80%);
    box-shadow: 0 0 12px rgba(103, 232, 249, 0.75), 0 0 24px rgba(6, 182, 212, 0.45);
}
.bg-spark[data-color="violet"] {
    background: radial-gradient(circle, rgba(196, 181, 253, 1) 0%, rgba(139, 92, 246, 0.4) 50%, transparent 80%);
    box-shadow: 0 0 12px rgba(196, 181, 253, 0.7), 0 0 22px rgba(139, 92, 246, 0.4);
}
.bg-spark[data-color="pink"] {
    background: radial-gradient(circle, rgba(249, 168, 212, 1) 0%, rgba(236, 72, 153, 0.4) 50%, transparent 80%);
    box-shadow: 0 0 10px rgba(249, 168, 212, 0.6), 0 0 20px rgba(236, 72, 153, 0.3);
}

@keyframes spark-drift {
    0%   { opacity: 0; transform: translate(0, 0) scale(0.3); }
    10%  { opacity: 0.95; transform: translate(-12px, -8px) scale(1); }
    35%  { opacity: 0.6; transform: translate(18px, -28px) scale(0.8); }
    65%  { opacity: 0.85; transform: translate(-22px, -45px) scale(1.05); }
    90%  { opacity: 0.3; transform: translate(12px, -72px) scale(0.6); }
    100% { opacity: 0; transform: translate(0, -90px) scale(0.2); }
}

@media (prefers-reduced-motion: reduce) {
    .bg-sparks { display: none; }
}

/* ===========================================================================
   MESH GRADIENT BACKGROUND
=========================================================================== */
.dashboard-root {
    background-color: #f0fdf8;
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
    .dashboard-mesh-bg { animation: none; background-position: 0% 50%; }
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

.dashboard-blob-4 {
    top: 55%;
    right: -100px;
    background: radial-gradient(circle, rgba(45, 212, 191, 0.30) 0%, transparent 70%);
    filter: blur(56px);
    animation: blob-drift-4 38s ease-in-out infinite;
    animation-delay: -8s;
}

.dark .dashboard-blob-4 {
    background: radial-gradient(circle, rgba(20, 184, 166, 0.14) 0%, transparent 70%);
}

.dashboard-blob-5 {
    top: 15%;
    left: 30%;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.22) 0%, transparent 70%);
    filter: blur(52px);
    animation: blob-drift-5 45s ease-in-out infinite;
    animation-delay: -15s;
}

.dark .dashboard-blob-5 {
    background: radial-gradient(circle, rgba(139, 92, 246, 0.12) 0%, transparent 70%);
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

@keyframes blob-drift-4 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    35%       { transform: translate(-50px, -25px) scale(1.08); }
    70%       { transform: translate(30px, 35px) scale(0.92); }
}

@keyframes blob-drift-5 {
    0%, 100% { transform: translate(0, 0) scale(0.98); }
    25%       { transform: translate(35px, -40px) scale(1.05); }
    60%       { transform: translate(-30px, 20px) scale(1.12); }
    85%       { transform: translate(20px, 30px) scale(0.95); }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-blob-1,
    .dashboard-blob-2,
    .dashboard-blob-3,
    .dashboard-blob-4,
    .dashboard-blob-5 { animation: none; }
}

/* ===========================================================================
   AVATAR GLOW
=========================================================================== */
.avatar-glow {
    box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.25), 0 8px 24px rgba(16, 185, 129, 0.30);
    animation: avatar-pulse 3.5s ease-in-out infinite;
}

@keyframes avatar-pulse {
    0%, 100% { box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.25), 0 8px 24px rgba(16, 185, 129, 0.30); }
    50%       { box-shadow: 0 0 0 6px rgba(52, 211, 153, 0.15), 0 8px 32px rgba(16, 185, 129, 0.45); }
}

@media (prefers-reduced-motion: reduce) {
    .avatar-glow { animation: none; }
}

/* ===========================================================================
   SPOTLIGHT / FOCUS MODE
   Clicked card pops to front; all others dim and shrink
=========================================================================== */
.card-focused {
    transform: translateY(-10px) scale(1.05) !important;
    z-index: 20;
    border-color: rgba(16, 185, 129, 0.65) !important;
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.95) inset,
        0 36px 72px -12px rgba(15, 118, 110, 0.50),
        0 12px 28px -4px rgba(15, 23, 42, 0.20),
        0 0 0 1.5px rgba(16, 185, 129, 0.40),
        0 0 72px rgba(16, 185, 129, 0.30) !important;
    transition: all 380ms cubic-bezier(0.34, 1.56, 0.64, 1) !important;
}

.dark .card-focused {
    border-color: rgba(16, 185, 129, 0.70) !important;
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.10) inset,
        0 40px 80px -12px rgba(0, 0, 0, 0.90),
        0 0 120px rgba(16, 185, 129, 0.40),
        0 0 0 1.5px rgba(16, 185, 129, 0.50) !important;
}

.card-dimmed {
    transform: scale(0.94) translateY(4px) !important;
    opacity: 0.38 !important;
    filter: blur(0.4px) saturate(0.6) !important;
    pointer-events: none;
    transition: all 380ms cubic-bezier(0.34, 1.56, 0.64, 1) !important;
}

@media (prefers-reduced-motion: reduce) {
    .card-focused { transform: none !important; }
    .card-dimmed  { transform: none !important; filter: none !important; }
}

/* ===========================================================================
   STREAK CARD
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

.dark .streak-number {
    filter: drop-shadow(0 0 20px rgba(52, 211, 153, 0.4));
}

@keyframes streak-pulse {
    0%, 100% { transform: scale(1) rotate(-3deg); }
    50%       { transform: scale(1.15) rotate(3deg); }
}

.motion-safe\:animate-streak-pulse {
    animation: streak-pulse 2.4s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .motion-safe\:animate-streak-pulse { animation: none; }
}

/* ===========================================================================
   LIVING HALO — breathing drop-shadow on metric & total cards
   (independent of box-shadow & transform, so no conflict with hover/focus)
=========================================================================== */
.metric-card.animate-fade-in-up,
.total-card.animate-fade-in-up {
    animation:
        fade-in-up 0.45s ease-out both,
        card-aura 5s ease-in-out infinite;
}

@keyframes card-aura {
    0%, 100% { filter: drop-shadow(0 0 6px rgba(16, 185, 129, 0.18)); }
    50%       { filter: drop-shadow(0 4px 22px rgba(20, 184, 166, 0.32)); }
}

.dark .metric-card.animate-fade-in-up,
.dark .total-card.animate-fade-in-up {
    animation:
        fade-in-up 0.45s ease-out both,
        card-aura-dark 5s ease-in-out infinite;
}

@keyframes card-aura-dark {
    0%, 100% { filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.22)); }
    50%       { filter: drop-shadow(0 6px 28px rgba(20, 184, 166, 0.40)); }
}

@media (prefers-reduced-motion: reduce) {
    .metric-card.animate-fade-in-up,
    .total-card.animate-fade-in-up,
    .dark .metric-card.animate-fade-in-up,
    .dark .total-card.animate-fade-in-up {
        animation: fade-in-up 0.45s ease-out both;
    }
}

/* ===========================================================================
   LIVING TYPOGRAPHY — number weight breathe + name shimmer
=========================================================================== */
.number-breathe {
    font-variation-settings: 'wght' 800;
    animation: number-breathe 4.2s ease-in-out infinite;
}

@keyframes number-breathe {
    0%, 100% { font-variation-settings: 'wght' 700; letter-spacing: 0; }
    50%       { font-variation-settings: 'wght' 850; letter-spacing: -0.01em; }
}

.name-shimmer {
    background-size: 220% auto;
    animation: name-shimmer 7s linear infinite;
}

@keyframes name-shimmer {
    0%   { background-position: 0% 50%; }
    100% { background-position: 220% 50%; }
}

@media (prefers-reduced-motion: reduce) {
    .number-breathe,
    .name-shimmer { animation: none; }
}

/* ===========================================================================
   HEATMAP — today cell pulse
=========================================================================== */
.heatmap-today {
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.55), 0 0 12px rgba(16, 185, 129, 0.45);
    animation: today-pulse 2.1s ease-in-out infinite;
    z-index: 1;
}

@keyframes today-pulse {
    0%, 100% {
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.5), 0 0 8px rgba(16, 185, 129, 0.35);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.85), 0 0 18px rgba(16, 185, 129, 0.6);
        transform: scale(1.18);
    }
}

@media (prefers-reduced-motion: reduce) {
    .heatmap-today { animation: none; transform: none; }
}

/* ===========================================================================
   STREAK PARTICLES — sparks rising from streak card (always-on, > 6 days)
=========================================================================== */
.streak-particle {
    bottom: 0;
    background: radial-gradient(circle, rgba(94, 234, 212, 1) 0%, rgba(45, 212, 191, 0.6) 45%, transparent 80%);
    box-shadow: 0 0 6px rgba(45, 212, 191, 0.7), 0 0 12px rgba(16, 185, 129, 0.4);
    opacity: 0;
    animation-name: particle-rise;
    animation-timing-function: ease-out;
    animation-iteration-count: infinite;
}

.dark .streak-particle {
    background: radial-gradient(circle, rgba(110, 231, 183, 1) 0%, rgba(52, 211, 153, 0.7) 45%, transparent 80%);
    box-shadow: 0 0 8px rgba(52, 211, 153, 0.85), 0 0 16px rgba(16, 185, 129, 0.55);
}

@keyframes particle-rise {
    0%   { transform: translate(0, 0) scale(0.6); opacity: 0; }
    15%  { opacity: 1; transform: translate(0, -8px) scale(1); }
    60%  { transform: translate(calc(var(--drift, 6px)), -64px) scale(0.85); opacity: 0.8; }
    100% { transform: translate(calc(var(--drift, -10px)), -120px) scale(0.3); opacity: 0; }
}

@media (prefers-reduced-motion: reduce) {
    .streak-particles { display: none; }
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
    .progress-bar--shimmer::after { animation: none; }
}

/* ===========================================================================
   METRIC CARDS
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

.metric-card:hover .metric-card__sweep { opacity: 1; }

.dark .metric-card:hover {
    box-shadow:
        0 12px 48px rgba(0, 0, 0, 0.6),
        0 0 0 1px rgba(255, 255, 255, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.07);
}

/* ===========================================================================
   TOTAL CARDS
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

.total-card:hover .total-card__glow { opacity: 1; }

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

.action-card:hover .action-card__sweep { opacity: 1; }

.action-card:hover {
    transform: translateY(-3px) scale(1.02);
}

.action-card:active {
    transform: scale(0.97);
    transition-duration: 80ms;
}

/* ===========================================================================
   PER-CARD COLOR HOVER HALOS (data-glow attribute)
=========================================================================== */
.metric-card[data-glow="sky"]:hover {
    border-color: rgba(56, 189, 248, 0.55) !important;
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.9) inset,
        0 20px 44px -12px rgba(14, 165, 233, 0.32),
        0 0 0 1px rgba(56, 189, 248, 0.30),
        0 0 48px rgba(56, 189, 248, 0.30) !important;
}

.dark .metric-card[data-glow="sky"]:hover {
    border-color: rgba(56, 189, 248, 0.65) !important;
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.08) inset,
        0 24px 56px -12px rgba(0, 0, 0, 0.75),
        0 0 64px rgba(56, 189, 248, 0.32) !important;
}

.metric-card[data-glow="amber"]:hover {
    border-color: rgba(251, 191, 36, 0.55) !important;
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.9) inset,
        0 20px 44px -12px rgba(245, 158, 11, 0.32),
        0 0 0 1px rgba(251, 191, 36, 0.32),
        0 0 48px rgba(251, 191, 36, 0.32) !important;
}

.dark .metric-card[data-glow="amber"]:hover {
    border-color: rgba(251, 191, 36, 0.65) !important;
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.08) inset,
        0 24px 56px -12px rgba(0, 0, 0, 0.75),
        0 0 64px rgba(251, 191, 36, 0.32) !important;
}

.metric-card[data-glow="violet"]:hover {
    border-color: rgba(167, 139, 250, 0.55) !important;
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.9) inset,
        0 20px 44px -12px rgba(139, 92, 246, 0.32),
        0 0 0 1px rgba(167, 139, 250, 0.32),
        0 0 48px rgba(167, 139, 250, 0.32) !important;
}

.dark .metric-card[data-glow="violet"]:hover {
    border-color: rgba(167, 139, 250, 0.70) !important;
    box-shadow:
        0 1px 0 0 rgba(255, 255, 255, 0.08) inset,
        0 24px 56px -12px rgba(0, 0, 0, 0.75),
        0 0 64px rgba(167, 139, 250, 0.36) !important;
}

/* ===========================================================================
   LIQUID FILL on action cards (replaces simple sweep)
=========================================================================== */
.action-card__sweep {
    transform: translateY(100%) scale(1.4);
    opacity: 0.65;
    transition:
        transform 720ms cubic-bezier(0.34, 1.56, 0.64, 1),
        opacity 320ms ease;
}

.action-card:hover .action-card__sweep {
    transform: translateY(0) scale(1);
    opacity: 1;
}

.action-card::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    border-radius: inherit;
    background: linear-gradient(
        130deg,
        transparent 20%,
        rgba(255, 255, 255, 0.5) 48%,
        rgba(255, 255, 255, 0.0) 52%,
        transparent 80%
    );
    transform: translateX(-120%);
    opacity: 0;
    transition: transform 700ms cubic-bezier(0.34, 1.56, 0.64, 1), opacity 200ms ease;
}

.dark .action-card::after {
    background: linear-gradient(
        130deg,
        transparent 20%,
        rgba(255, 255, 255, 0.20) 48%,
        rgba(255, 255, 255, 0.0) 52%,
        transparent 80%
    );
}

.action-card:hover::after {
    transform: translateX(120%);
    opacity: 1;
}

@media (prefers-reduced-motion: reduce) {
    .action-card__sweep,
    .action-card::after { transition: none; transform: none; opacity: 0; }
    .action-card:hover .action-card__sweep { transform: none; opacity: 0.4; }
    .action-card:hover::after { transform: none; opacity: 0; }
}

/* ===========================================================================
   GREETING — avatar status dot live pulse
=========================================================================== */
.avatar-status {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    animation: status-pulse 2.2s ease-in-out infinite;
}

@keyframes status-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.75), 0 0 6px rgba(34, 197, 94, 0.45); }
    70%  { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0),  0 0 14px rgba(34, 197, 94, 0.5); }
    100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0),     0 0 6px rgba(34, 197, 94, 0.45); }
}

@media (prefers-reduced-motion: reduce) {
    .avatar-status { animation: none; }
}

/* ===========================================================================
   GREETING SECTION — floating breathing
=========================================================================== */
.greeting-section {
    will-change: transform;
}
</style>
