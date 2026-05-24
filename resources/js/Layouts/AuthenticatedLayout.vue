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
    <div
        class="min-h-screen"
        :class="minimal
            ? 'layout-minimal relative overflow-hidden'
            : 'bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 dark:from-gray-900 dark:via-emerald-950/20 dark:to-gray-900'"
    >
        <!-- Animated mesh + blobs — only in minimal (fullscreen) mode -->
        <template v-if="minimal">
            <div aria-hidden="true" class="layout-mesh-bg pointer-events-none absolute inset-0 z-0" />
            <div aria-hidden="true" class="layout-blob-1 pointer-events-none absolute z-0 h-[520px] w-[520px] rounded-full" />
            <div aria-hidden="true" class="layout-blob-2 pointer-events-none absolute z-0 h-[400px] w-[400px] rounded-full" />
        </template>

        <div :class="minimal ? 'relative z-10 flex min-h-screen flex-col' : ''">
            <!-- ── MINIMAL navbar (logo only → back to dashboard) ── -->
            <nav
                v-if="minimal"
                class="sticky top-0 z-30 border-b border-white/20 bg-white/50 backdrop-blur-md dark:border-white/10 dark:bg-gray-900/50"
            >
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center">
                        <Link
                            :href="route('dashboard')"
                            class="flex shrink-0 items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        >
                            <ApplicationLogo class="block h-14 w-auto fill-current text-emerald-600 dark:text-emerald-400" />
                            <span class="hidden text-sm font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:block">
                                Wirtualny Nauczyciel
                            </span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- ── FULL navbar (dashboard only) ── -->
            <nav
                v-else
                class="sticky top-0 z-30 border-b border-gray-200/50 bg-white/80 backdrop-blur-md dark:border-gray-800/50 dark:bg-gray-900/80"
            >
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex items-center gap-8">
                            <!-- Logo -->
                            <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-2.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 rounded-lg">
                                <ApplicationLogo class="block h-14 w-auto fill-current text-emerald-600 dark:text-emerald-400" />
                                <span class="hidden text-sm font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:block">Wirtualny Nauczyciel</span>
                            </Link>

                            <!-- Navigation Links -->
                            <div class="hidden items-center gap-1 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    {{ $t('nav.dashboard') }}
                                </NavLink>
                                <NavLink :href="route('study.session')" :active="route().current('study.session')">
                                    {{ $t('nav.study') }}
                                </NavLink>
                                <NavLink :href="route('questions.index')" :active="route().current('questions.index')">
                                    {{ $t('nav.questions') }}
                                </NavLink>
                                <NavLink :href="route('interview.show')" :active="route().current('interview.show')">
                                    {{ $t('nav.interview') }}
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200/60 bg-white/60 px-3.5 py-2 text-sm font-medium text-gray-700 backdrop-blur-sm transition hover:border-gray-300 hover:bg-white/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:border-gray-700/60 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800/80"
                                        >
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

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-xl p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100/80 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:hover:bg-white/5 dark:hover:text-gray-300"
                                :aria-expanded="showingNavigationDropdown"
                                aria-label="Menu nawigacyjne"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
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

            <!-- Page Heading (full mode only) -->
            <header
                v-if="$slots.header && !minimal"
                class="border-b border-gray-200/50 bg-white/60 backdrop-blur-sm dark:border-gray-800/50 dark:bg-gray-900/60"
            >
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.layout-minimal {
    background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 30%, #ffffff 60%, #f0fdfa 100%);
}
:global(.dark) .layout-minimal {
    background: linear-gradient(135deg, #020617 0%, #051b11 35%, #0f172a 70%, #020617 100%);
}
.layout-mesh-bg {
    background: linear-gradient(240deg, #6ee7b7, #a7f3d0, #f0fdf4, #d1fae5, #ecfdf5, #99f6e4);
    background-size: 400% 400%;
    animation: mesh-shift 22s ease infinite;
    opacity: 0.5;
}
:global(.dark) .layout-mesh-bg {
    background: linear-gradient(240deg, #022c22, #064e3b, #0f172a, #022c22, #0c1a0e, #134e4a);
    background-size: 400% 400%;
    animation: mesh-shift 22s ease infinite;
    opacity: 0.7;
}
.layout-blob-1 {
    top: -8%;
    right: -4%;
    background: radial-gradient(circle, #6ee7b7 0%, transparent 70%);
    animation: blob-drift-1 20s ease-in-out infinite;
    opacity: 0.4;
}
:global(.dark) .layout-blob-1 {
    opacity: 0.18;
}
.layout-blob-2 {
    bottom: -10%;
    left: -5%;
    background: radial-gradient(circle, #5eead4 0%, transparent 70%);
    animation: blob-drift-2 25s ease-in-out infinite;
    opacity: 0.35;
}
:global(.dark) .layout-blob-2 {
    opacity: 0.14;
}
</style>
