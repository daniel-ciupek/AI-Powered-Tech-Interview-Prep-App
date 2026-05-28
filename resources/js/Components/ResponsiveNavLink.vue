<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'block w-full ps-4 pe-4 py-2.5 border-l-2 border-emerald-500 text-start text-sm font-medium text-emerald-700 bg-emerald-50/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition duration-150 ease-in-out dark:text-emerald-300 dark:bg-emerald-900/20 dark:border-emerald-500'
        : 'block w-full ps-4 pe-4 py-2.5 border-l-2 border-transparent text-start text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50/70 hover:border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition duration-150 ease-in-out dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-white/5 dark:hover:border-gray-600',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
