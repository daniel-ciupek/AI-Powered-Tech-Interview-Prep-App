<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        suggestions?: string[];
        maxTags?: number;
        maxLength?: number;
    }>(),
    {
        suggestions: () => [],
        maxTags: 5,
        maxLength: 50,
    },
);

const emit = defineEmits<{
    'update:modelValue': [tags: string[]];
}>();

const input = ref('');
const warning = ref<string | null>(null);
const datalistId = `tag-suggestions-${Math.random().toString(36).slice(2, 9)}`;

const limitReached = computed(() => props.modelValue.length >= props.maxTags);

const filteredSuggestions = computed(() => {
    const selected = new Set(props.modelValue.map((t) => t.toLowerCase()));
    return props.suggestions
        .filter((s) => !selected.has(s.toLowerCase()))
        .slice(0, 30);
});

function normalize(value: string): string {
    return value.trim().toLowerCase().replace(/\s+/g, ' ');
}

function commit(): void {
    const value = normalize(input.value);
    if (value === '') return;

    if (value.length > props.maxLength) {
        warning.value = t('common.tags.too_long', { max: props.maxLength });
        return;
    }

    if (limitReached.value) {
        warning.value = t('common.tags.limit_reached', { max: props.maxTags });
        return;
    }

    const already = props.modelValue.some((tag) => tag.toLowerCase() === value);
    if (!already) {
        emit('update:modelValue', [...props.modelValue, value]);
    }
    input.value = '';
    warning.value = null;
}

function remove(tag: string): void {
    emit('update:modelValue', props.modelValue.filter((t) => t !== tag));
    warning.value = null;
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        commit();
        return;
    }

    if (event.key === 'Backspace' && input.value === '' && props.modelValue.length > 0) {
        emit('update:modelValue', props.modelValue.slice(0, -1));
        warning.value = null;
    }
}
</script>

<template>
    <div>
        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ t('common.tags.label') }}
            <span class="font-normal text-gray-400 dark:text-gray-500">
                {{ t('common.tags.hint', { max: maxTags }) }}
            </span>
        </p>

        <div
            class="flex flex-wrap gap-2 rounded-lg border border-gray-300 bg-white p-2 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition dark:border-gray-600 dark:bg-gray-700"
        >
            <span
                v-for="tag in modelValue"
                :key="tag"
                class="inline-flex items-center gap-1.5 rounded-full bg-indigo-600 px-3 py-1 text-xs font-medium text-white dark:bg-indigo-500"
            >
                {{ tag }}
                <button
                    type="button"
                    class="rounded-full text-indigo-100 transition hover:text-white focus:outline-none"
                    :aria-label="t('common.tags.remove', { tag })"
                    @click="remove(tag)"
                >
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </span>

            <input
                v-model="input"
                type="text"
                :list="datalistId"
                :placeholder="modelValue.length === 0 ? t('common.tags.placeholder') : ''"
                :disabled="limitReached"
                :maxlength="maxLength"
                autocomplete="off"
                class="flex-1 min-w-[8rem] border-0 bg-transparent p-1 text-sm outline-none focus:ring-0 disabled:opacity-50 dark:text-gray-100 dark:placeholder-gray-400"
                @keydown="onKeydown"
                @blur="commit"
            />

            <datalist :id="datalistId">
                <option v-for="s in filteredSuggestions" :key="s" :value="s" />
            </datalist>
        </div>

        <p v-if="warning" class="mt-2 text-xs text-amber-600 dark:text-amber-400">
            {{ warning }}
        </p>
    </div>
</template>
