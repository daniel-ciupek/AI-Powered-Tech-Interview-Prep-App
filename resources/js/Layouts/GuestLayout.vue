<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useTheme } from '@/composables/useTheme';
import { Link } from '@inertiajs/vue3';

useTheme();
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 px-4 py-12 dark:from-gray-900 dark:via-emerald-950/20 dark:to-gray-900">

        <!-- Decorative background blobs -->
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-32 h-[500px] w-[500px] rounded-full bg-emerald-200/30 blur-3xl dark:bg-emerald-900/20" />
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -left-32 h-[400px] w-[400px] rounded-full bg-teal-200/30 blur-3xl dark:bg-teal-900/15" />
        <div aria-hidden="true" class="pointer-events-none absolute top-1/2 left-1/2 h-[300px] w-[300px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-100/20 blur-2xl dark:bg-emerald-950/20" />

        <!-- Logo -->
        <div class="relative mb-8 animate-fade-in-up" style="animation-delay: 0ms;">
            <Link
                href="/"
                class="flex flex-col items-center gap-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 rounded-xl p-2"
            >
                <ApplicationLogo class="h-16 w-16 fill-current text-emerald-600 drop-shadow-lg dark:text-emerald-400" />
                <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">
                    Wirtualny Nauczyciel
                </span>
            </Link>
        </div>

        <!-- Glass card -->
        <div
            class="relative w-full max-w-md animate-fade-in-up overflow-hidden rounded-2xl border border-white/40 bg-white/82 p-8 shadow-xl shadow-emerald-100/40 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/60 dark:shadow-emerald-950/30"
            style="animation-delay: 80ms;"
        >
            <slot />
        </div>
    </div>
</template>
