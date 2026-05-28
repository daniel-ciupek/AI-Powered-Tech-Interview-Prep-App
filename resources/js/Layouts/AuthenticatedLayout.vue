<script setup lang="ts">
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { useTheme } from '@/composables/useTheme';
import { Link } from '@inertiajs/vue3';

withDefaults(defineProps<{ minimal?: boolean }>(), { minimal: false });

const { effective } = useTheme();

const showingNavigationDropdown = ref(false);

const minimalBg = computed(() =>
    effective.value === 'dark'
        ? 'linear-gradient(135deg, #1a0533 0%, #0d1f0f 55%, #032d1a 100%)'
        : 'linear-gradient(135deg, #f0fdf4 0%, #ffffff 55%, #ecfdf5 100%)',
);
</script>

<template>
    <!-- ═══════════════════════════════════════════════════════════
         MINIMAL mode — fullscreen app pages (Study, Questions…)
         No navbar bar. Only a floating logo icon → back to dashboard.
    ═══════════════════════════════════════════════════════════ -->
    <div
        v-if="minimal"
        class="relative min-h-screen overflow-x-hidden"
        :style="{ background: minimalBg }"
    >
        <!-- Depth blob: white glow in centre (fixed = out of flow, won't cause scroll) -->
        <div
            aria-hidden="true"
            class="pointer-events-none fixed inset-0 z-0"
            :style="effective === 'dark'
                ? 'background: radial-gradient(ellipse 80% 50% at 50% 40%, rgba(255,255,255,0.04) 0%, transparent 70%);'
                : 'background: radial-gradient(ellipse 80% 50% at 50% 40%, rgba(16,185,129,0.06) 0%, transparent 70%);'"
        />
        <!-- Blob purple top-right -->
        <div
            aria-hidden="true"
            class="pointer-events-none fixed z-0 h-[600px] w-[600px] rounded-full opacity-[0.07] dark:opacity-[0.22]"
            style="top:-15%; right:-10%; background: radial-gradient(circle, #7a2bff 0%, transparent 65%); animation: blob-drift-1 22s ease-in-out infinite;"
        />
        <!-- Blob emerald bottom-left -->
        <div
            aria-hidden="true"
            class="pointer-events-none fixed z-0 h-[500px] w-[500px] rounded-full opacity-[0.08] dark:opacity-[0.2]"
            style="bottom:-12%; left:-8%; background: radial-gradient(circle, #00c47a 0%, transparent 65%); animation: blob-drift-2 28s ease-in-out infinite;"
        />
        <!-- Blob teal mid-left accent -->
        <div
            aria-hidden="true"
            class="pointer-events-none fixed z-0 h-[300px] w-[300px] rounded-full opacity-[0.05] dark:opacity-[0.12]"
            style="top:35%; left:-5%; background: radial-gradient(circle, #00f0ff 0%, transparent 65%); animation: blob-drift-3 18s ease-in-out infinite;"
        />

        <!-- Floating logo button (fixed, top-left) -->
        <Link
            :href="route('dashboard')"
            class="fixed left-4 top-4 z-50 flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-200 bg-white/80 shadow-lg shadow-gray-200/60 backdrop-blur-md transition-all duration-200 hover:scale-110 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 dark:border-white/15 dark:bg-white/10 dark:shadow-black/30 dark:hover:bg-white/20"
            :title="$t('nav.dashboard')"
            aria-label="Powrót do pulpitu"
        >
            <ApplicationLogo class="h-8 w-8" />
        </Link>

        <!-- Page content -->
        <main class="relative z-10 min-h-screen">
            <slot />
        </main>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         FULL mode — Dashboard and main nav pages
    ═══════════════════════════════════════════════════════════ -->
    <div v-else class="auth-layout-root min-h-screen">

        <!-- Fixed aurora blobs — visible under glass nav and below -->
        <div aria-hidden="true" class="auth-blob auth-blob-1 pointer-events-none fixed z-0" />
        <div aria-hidden="true" class="auth-blob auth-blob-2 pointer-events-none fixed z-0" />
        <div aria-hidden="true" class="auth-blob auth-blob-3 pointer-events-none fixed z-0" />
        <div aria-hidden="true" class="auth-blob auth-blob-4 pointer-events-none fixed z-0" />

        <!-- Glass navbar -->
        <nav class="auth-nav sticky top-0 z-30">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center gap-8">
                        <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                            <ApplicationLogo class="block h-14 w-auto fill-current text-emerald-600 dark:text-emerald-400" />
                            <span class="nav-brand hidden text-sm font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:block">Wirtualny Nauczyciel</span>
                        </Link>

                        <div class="hidden items-center gap-1 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">{{ $t('nav.dashboard') }}</NavLink>
                            <NavLink :href="route('study.session')" :active="route().current('study.session')">{{ $t('nav.study') }}</NavLink>
                            <NavLink :href="route('questions.index')" :active="route().current('questions.index')">{{ $t('nav.questions') }}</NavLink>
                            <NavLink :href="route('interview.show')" :active="route().current('interview.show')">{{ $t('nav.interview') }}</NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button type="button" class="nav-user-btn inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-gray-300 dark:hover:text-gray-100">
                                        <span class="max-w-[120px] truncate">{{ $page.props.auth.user.name }}</span>
                                        <svg class="h-4 w-4 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="px-1 py-1">
                                        <DropdownLink :href="route('profile.edit')">{{ $t('nav.profile') }}</DropdownLink>
                                        <DropdownLink :href="route('settings.edit')">{{ $t('nav.settings') }}</DropdownLink>
                                        <div class="my-1 border-t border-gray-100 dark:border-gray-800" />
                                        <DropdownLink :href="route('logout')" method="post" as="button">{{ $t('nav.logout') }}</DropdownLink>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-xl p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-white/20 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:hover:bg-white/10 dark:hover:text-gray-300" :aria-expanded="showingNavigationDropdown" aria-label="Menu nawigacyjne">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                <div v-show="showingNavigationDropdown" class="nav-mobile-menu sm:hidden">
                    <div class="space-y-0.5 px-2 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">{{ $t('nav.dashboard') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('study.session')" :active="route().current('study.session')">{{ $t('nav.study') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('questions.index')" :active="route().current('questions.index')">{{ $t('nav.questions') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('interview.show')" :active="route().current('interview.show')">{{ $t('nav.interview') }}</ResponsiveNavLink>
                    </div>
                    <div class="border-t border-white/15 pb-2 pt-4 dark:border-white/8">
                        <div class="px-4 pb-2">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $page.props.auth.user.name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $page.props.auth.user.email }}</div>
                        </div>
                        <div class="space-y-0.5 px-2">
                            <ResponsiveNavLink :href="route('profile.edit')">{{ $t('nav.profile') }}</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('settings.edit')">{{ $t('nav.settings') }}</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">{{ $t('nav.logout') }}</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </Transition>
        </nav>

        <header v-if="$slots.header" class="auth-header">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main class="relative z-10">
            <slot />
        </main>
    </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   FULL-MODE LAYOUT ROOT — animated mesh background
═══════════════════════════════════════════════════════════════ */
.auth-layout-root {
    background-color: #f0fdf8;
    background: linear-gradient(
        135deg,
        #ecfdf5 0%,
        #f8fffe 35%,
        #eff6ff 65%,
        #f0fdf4 100%
    );
    background-size: 300% 300%;
    animation: mesh-shift 32s ease infinite;
}

.dark .auth-layout-root {
    background-color: #060a0e;
    background: linear-gradient(
        135deg,
        #060a0e 0%,
        #070c0a 50%,
        #07090f 100%
    );
    animation: none;
}

@media (prefers-reduced-motion: reduce) {
    .auth-layout-root { animation: none; background-position: 0% 50%; }
}

/* ═══════════════════════════════════════════════════════════════
   AURORA BLOBS — fixed, visible under glass nav and in content
═══════════════════════════════════════════════════════════════ */
.auth-blob {
    border-radius: 50%;
    filter: blur(72px);
}

.auth-blob-1 {
    width: 660px;
    height: 660px;
    top: -200px;
    right: -160px;
    background: radial-gradient(circle, rgba(52, 211, 153, 0.32) 0%, transparent 65%);
    animation: blob-drift-1 28s ease-in-out infinite;
}

.dark .auth-blob-1 {
    background: radial-gradient(circle, rgba(16, 185, 129, 0.14) 0%, transparent 65%);
}

.auth-blob-2 {
    width: 540px;
    height: 540px;
    bottom: -120px;
    left: -130px;
    background: radial-gradient(circle, rgba(34, 211, 238, 0.26) 0%, transparent 65%);
    animation: blob-drift-2 36s ease-in-out infinite;
}

.dark .auth-blob-2 {
    background: radial-gradient(circle, rgba(6, 182, 212, 0.12) 0%, transparent 65%);
}

.auth-blob-3 {
    width: 420px;
    height: 420px;
    top: 20%;
    left: 38%;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.18) 0%, transparent 65%);
    animation: blob-drift-3 24s ease-in-out infinite;
    animation-delay: -6s;
}

.dark .auth-blob-3 {
    background: radial-gradient(circle, rgba(139, 92, 246, 0.10) 0%, transparent 65%);
}

.auth-blob-4 {
    width: 380px;
    height: 380px;
    top: 55%;
    right: 5%;
    background: radial-gradient(circle, rgba(45, 212, 191, 0.22) 0%, transparent 65%);
    animation: blob-drift-1 40s ease-in-out infinite;
    animation-delay: -12s;
}

.dark .auth-blob-4 {
    background: radial-gradient(circle, rgba(20, 184, 166, 0.11) 0%, transparent 65%);
}

@media (prefers-reduced-motion: reduce) {
    .auth-blob { animation: none; }
}

/* ═══════════════════════════════════════════════════════════════
   GLASS NAVBAR
═══════════════════════════════════════════════════════════════ */
.auth-nav {
    position: relative;
    background: rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(28px) saturate(180%);
    -webkit-backdrop-filter: blur(28px) saturate(180%);
}

.dark .auth-nav {
    background: rgba(8, 14, 26, 0.28);
}

/* Gradient bottom accent line — replaces hard border */
.auth-nav::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(16, 185, 129, 0.55) 25%,
        rgba(20, 184, 166, 0.40) 50%,
        rgba(6, 182, 212, 0.30) 75%,
        transparent 100%
    );
    pointer-events: none;
}

.dark .auth-nav::after {
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(16, 185, 129, 0.32) 25%,
        rgba(20, 184, 166, 0.22) 50%,
        rgba(6, 182, 212, 0.16) 75%,
        transparent 100%
    );
}

/* Brand text subtle shimmer effect */
.nav-brand {
    background: linear-gradient(
        90deg,
        #111827 0%,
        #059669 45%,
        #0d9488 55%,
        #111827 100%
    );
    background-size: 220% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: nav-brand-shimmer 10s linear infinite;
}

.dark .nav-brand {
    background: linear-gradient(
        90deg,
        #f1f5f9 0%,
        #34d399 45%,
        #2dd4bf 55%,
        #f1f5f9 100%
    );
    background-size: 220% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

@keyframes nav-brand-shimmer {
    0%   { background-position: 0% 50%; }
    100% { background-position: 220% 50%; }
}

@media (prefers-reduced-motion: reduce) {
    .nav-brand { animation: none; -webkit-text-fill-color: unset; background: none; color: inherit; }
}

/* User button glass pill */
.nav-user-btn {
    background: rgba(255, 255, 255, 0.35);
    border: 1px solid rgba(16, 185, 129, 0.20);
    backdrop-filter: blur(12px);
    transition: background 200ms ease, border-color 200ms ease, transform 200ms ease;
}

.nav-user-btn:hover {
    background: rgba(255, 255, 255, 0.55);
    border-color: rgba(16, 185, 129, 0.40);
    transform: translateY(-1px);
}

.dark .nav-user-btn {
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.18);
}

.dark .nav-user-btn:hover {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.35);
}

/* Mobile menu glass panel */
.nav-mobile-menu {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
}

.dark .nav-mobile-menu {
    background: rgba(10, 16, 30, 0.90);
}

/* ═══════════════════════════════════════════════════════════════
   HEADER SLOT
═══════════════════════════════════════════════════════════════ */
.auth-header {
    position: relative;
    background: rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(16, 185, 129, 0.12);
    z-index: 10;
}

.dark .auth-header {
    background: rgba(8, 14, 26, 0.22);
    border-bottom: 1px solid rgba(16, 185, 129, 0.08);
}
</style>
