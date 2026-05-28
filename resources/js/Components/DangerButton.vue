<template>
    <button
        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-red-500 to-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/25 transition-all duration-200 hover:from-red-600 hover:to-rose-700 hover:scale-[1.02] hover:shadow-red-500/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 dark:shadow-red-900/40 dark:focus-visible:ring-offset-gray-900"
    >
        <slot />
    </button>
</template>
