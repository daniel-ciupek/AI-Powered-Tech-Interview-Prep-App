<script setup lang="ts">
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { useTheme } from '@/composables/useTheme';
import { Link } from '@inertiajs/vue3';

withDefaults(defineProps<{ minimal?: boolean }>(), { minimal: false });

useTheme();

const showingNavigationDropdown = ref(false);
</script>

<template>
    <!-- ═══════════════════════════════════════════════════════════
         MINIMAL mode — fullscreen app pages (Study, Questions…)
         No navbar bar. Only a floating logo icon → back to dashboard.
    ═══════════════════════════════════════════════════════════ -->
    <div
        v-if="minimal"
        class="relative min-h-screen overflow-hidden"
        style="background: linear-gradient(135deg, #1a0533 0%, #0d1f0f 55%, #032d1a 100%);"
    >
        <!-- Depth blob: white glow in centre -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 z-0"
            style="background: radial-gradient(ellipse 80% 50% at 50% 40%, rgba(255,255,255,0.04) 0%, transparent 70%);"
        />
        <!-- Blob purple top-right -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute z-0 h-[600px] w-[600px] rounded-full"
            style="top:-15%; right:-10%; background: radial-gradient(circle, #7a2bff 0%, transparent 65%); opacity:0.22; animation: blob-drift-1 22s ease-in-out infinite;"
        />
        <!-- Blob emerald bottom-left -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute z-0 h-[500px] w-[500px] rounded-full"
            style="bottom:-12%; left:-8%; background: radial-gradient(circle, #00c47a 0%, transparent 65%); opacity:0.2; animation: blob-drift-2 28s ease-in-out infinite;"
        />
        <!-- Blob teal mid-left accent -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute z-0 h-[300px] w-[300px] rounded-full"
            style="top:35%; left:-5%; background: radial-gradient(circle, #00f0ff 0%, transparent 65%); opacity:0.12; animation: blob-drift-3 18s ease-in-out infinite;"
        />

        <!-- Floating logo button (fixed, top-left) -->
        <Link
            :href="route('dashboard')"
            class="fixed left-4 top-4 z-50 flex h-12 w-12 items-center justify-center rounded-2xl border border-white/15 bg-white/10 shadow-lg shadow-black/30 backdrop-blur-md transition-all duration-200 hover:scale-110 hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
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
         FULL mode — Dashboard only (full navbar + nav links)
    ═══════════════════════════════════════════════════════════ -->
    <div
        v-else
        class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 dark:from-gray-900 dark:via-emerald-950/20 dark:to-gray-900"
    >
        <nav class="sticky top-0 z-30 border-b border-gray-200/50 bg-white/80 backdrop-blur-md dark:border-gray-800/50 dark:bg-gray-900/80">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center gap-8">
                        <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                            <ApplicationLogo class="block h-14 w-auto fill-current text-emerald-600 dark:text-emerald-400" />
                            <span class="hidden text-sm font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:block">Wirtualny Nauczyciel</span>
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
                                    <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200/60 bg-white/60 px-3.5 py-2 text-sm font-medium text-gray-700 backdrop-blur-sm transition hover:border-gray-300 hover:bg-white/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:border-gray-700/60 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800/80">
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
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-xl p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100/80 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:hover:bg-white/5 dark:hover:text-gray-300" :aria-expanded="showingNavigationDropdown" aria-label="Menu nawigacyjne">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                <div v-show="showingNavigationDropdown" class="sm:hidden border-t border-gray-200/50 bg-white/90 backdrop-blur-md dark:border-gray-800/50 dark:bg-gray-900/90">
                    <div class="space-y-0.5 px-2 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">{{ $t('nav.dashboard') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('study.session')" :active="route().current('study.session')">{{ $t('nav.study') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('questions.index')" :active="route().current('questions.index')">{{ $t('nav.questions') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('interview.show')" :active="route().current('interview.show')">{{ $t('nav.interview') }}</ResponsiveNavLink>
                    </div>
                    <div class="border-t border-gray-200/50 pb-2 pt-4 dark:border-gray-700/50">
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

        <header v-if="$slots.header" class="border-b border-gray-200/50 bg-white/60 backdrop-blur-sm dark:border-gray-800/50 dark:bg-gray-900/60">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
