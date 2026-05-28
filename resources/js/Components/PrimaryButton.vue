<template>
    <button
        class="btn-shimmer inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:from-emerald-400 hover:to-teal-500 hover:scale-[1.02] hover:shadow-[0_0_40px_rgba(16,185,129,0.6)] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 active:scale-[0.95] active:shadow-[0_0_8px_rgba(16,185,129,0.2)] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 dark:shadow-emerald-900/40 dark:focus-visible:ring-offset-gray-900"
        style="box-shadow: 0 0 20px rgba(16,185,129,0.35), 0 10px 15px -3px rgba(16,185,129,0.25);"
    >
        <slot />
    </button>
</template>
