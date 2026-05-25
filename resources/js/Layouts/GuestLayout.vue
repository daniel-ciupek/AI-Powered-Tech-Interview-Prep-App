<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useTheme } from '@/composables/useTheme';
import { Link } from '@inertiajs/vue3';

useTheme();
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-x-hidden bg-gradient-to-br from-emerald-50 via-white to-teal-50 px-4 py-12 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950/40">

        <!-- Decorative background blobs (fixed so they don't scroll) -->
        <div aria-hidden="true" class="pointer-events-none fixed -top-24 -right-24 h-96 w-96 rounded-full bg-emerald-300/30 blur-3xl dark:bg-emerald-500/10 -z-10" />
        <div aria-hidden="true" class="pointer-events-none fixed -bottom-20 -left-20 h-80 w-80 rounded-full bg-teal-400/20 blur-3xl dark:bg-teal-500/10 -z-10" />

        <!-- Logo -->
        <div class="relative mb-8 animate-fade-in-up" style="animation-delay: 0ms;">
            <Link
                href="/"
                class="flex flex-col items-center gap-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 rounded-xl p-2"
            >
                <ApplicationLogo class="h-16 w-16 fill-current text-emerald-600 drop-shadow-lg dark:text-emerald-400" />
                <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-lg font-bold tracking-tight text-transparent dark:from-emerald-400 dark:to-teal-400">
                    PrepMind
                </span>
            </Link>
        </div>

        <!-- Glass card -->
        <div
            class="relative w-full max-w-md animate-fade-in-up overflow-hidden rounded-3xl border border-white/40 bg-white/70 p-8 shadow-xl shadow-emerald-100/40 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/50 dark:shadow-emerald-950/30"
            style="animation-delay: 80ms;"
        >
            <slot />
        </div>
    </div>
</template>
