<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium leading-5 text-emerald-700 bg-emerald-50 border border-emerald-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition duration-150 ease-in-out dark:text-emerald-300 dark:bg-emerald-900/30 dark:border-emerald-800'
        : 'inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium leading-5 text-gray-500 hover:text-gray-900 hover:bg-gray-100/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:text-gray-100 dark:hover:bg-white/5',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
