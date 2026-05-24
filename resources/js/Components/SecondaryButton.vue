<script setup lang="ts">
withDefaults(
    defineProps<{
        type?: 'button' | 'submit' | 'reset';
    }>(),
    {
        type: 'button',
    },
);
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center gap-2 rounded-xl border border-gray-200/60 bg-white/70 px-5 py-2.5 text-sm font-semibold text-gray-700 backdrop-blur-sm shadow-sm transition-all duration-200 hover:bg-white/90 hover:border-gray-300 hover:scale-[1.02] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 dark:border-gray-700/60 dark:bg-slate-800/50 dark:text-gray-300 dark:hover:bg-slate-800/80 dark:hover:border-gray-600 dark:focus-visible:ring-offset-gray-900"
    >
        <slot />
    </button>
</template>
