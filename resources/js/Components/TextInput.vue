<script setup lang="ts">
import { onMounted, ref } from 'vue';

const model = defineModel<string>({ required: true });

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        class="block w-full rounded-xl border border-gray-200/60 bg-white/70 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 backdrop-blur-sm shadow-sm transition-all duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50 dark:border-gray-700/60 dark:bg-slate-800/50 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:ring-emerald-500"
        v-model="model"
        ref="input"
    />
</template>
