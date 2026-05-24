<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2.5 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-emerald-50 hover:text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700 focus:outline-none dark:text-gray-200 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-300 dark:focus:bg-emerald-900/30"
    >
        <slot />
    </Link>
</template>
