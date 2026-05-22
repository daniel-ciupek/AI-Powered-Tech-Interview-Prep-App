<script setup lang="ts">
defineProps<{
    tags: string[];
    selectedTags: string[];
    maxTags?: number;
}>();

const emit = defineEmits<{
    'update:selectedTags': [tags: string[]];
}>();

function toggle(tag: string, selected: string[], max: number): void {
    if (selected.includes(tag)) {
        emit('update:selectedTags', selected.filter((t) => t !== tag));
    } else if (selected.length < max) {
        emit('update:selectedTags', [...selected, tag]);
    }
}
</script>

<template>
    <div>
        <p class="mb-2 text-sm font-medium text-gray-700">
            Topics
            <span class="text-gray-400 font-normal">(pick up to {{ maxTags ?? 5 }})</span>
        </p>
        <div class="flex flex-wrap gap-2">
            <button
                v-for="tag in tags"
                :key="tag"
                type="button"
                @click="toggle(tag, selectedTags, maxTags ?? 5)"
                :disabled="!selectedTags.includes(tag) && selectedTags.length >= (maxTags ?? 5)"
                class="rounded-full border px-3 py-1 text-xs font-medium transition"
                :class="selectedTags.includes(tag)
                    ? 'border-indigo-600 bg-indigo-600 text-white'
                    : 'border-gray-300 text-gray-600 hover:border-indigo-400 hover:text-indigo-600 disabled:opacity-40 disabled:cursor-not-allowed'"
            >
                {{ tag }}
            </button>
        </div>
        <p v-if="selectedTags.length > 0" class="mt-2 text-xs text-gray-500">
            Selected: {{ selectedTags.join(', ') }}
        </p>
    </div>
</template>
