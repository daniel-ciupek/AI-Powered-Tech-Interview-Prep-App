<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    hasApiKey: boolean;
}>();

const showForm = ref(!props.hasApiKey);

const saveForm = useForm({ api_key: '' });
const removeForm = useForm({});

function save(): void {
    saveForm.post(route('settings.api-key.store'), {
        onSuccess: () => {
            saveForm.reset();
            showForm.value = false;
        },
    });
}

function remove(): void {
    removeForm.delete(route('settings.api-key.destroy'));
}
</script>

<template>
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t('settings.api_key.header') }}</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ t('settings.api_key.intro_prefix') }}
            {{ t('settings.api_key.intro_link_prefix') }}
            <a
                href="https://aistudio.google.com/apikey"
                target="_blank"
                rel="noopener noreferrer"
                class="text-indigo-600 underline hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ t('settings.api_key.intro_link_text') }}
            </a>.
        </p>

        <!-- Key is set, form hidden -->
        <div v-if="hasApiKey && !showForm" class="mt-4 flex items-center gap-4">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800 dark:bg-green-900/40 dark:text-green-300">
                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                {{ t('settings.api_key.configured') }}
            </span>
            <button
                type="button"
                @click="showForm = true"
                class="text-sm text-indigo-600 underline hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ t('settings.api_key.change') }}
            </button>
            <form @submit.prevent="remove">
                <button
                    type="submit"
                    :disabled="removeForm.processing"
                    class="text-sm text-red-600 underline hover:text-red-500 disabled:opacity-50 dark:text-red-400 dark:hover:text-red-300"
                >
                    {{ t('settings.api_key.remove') }}
                </button>
            </form>
        </div>

        <!-- Input form -->
        <form v-if="showForm" @submit.prevent="save" class="mt-4 space-y-3">
            <div>
                <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ t('settings.api_key.label') }}
                </label>
                <input
                    id="api_key"
                    type="password"
                    autocomplete="off"
                    v-model="saveForm.api_key"
                    placeholder="AIza..."
                    class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500"
                />
                <p v-if="saveForm.errors.api_key" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ saveForm.errors.api_key }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="saveForm.processing || saveForm.api_key.length === 0"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                >
                    {{ t('settings.api_key.save') }}
                </button>
                <button
                    v-if="hasApiKey"
                    type="button"
                    @click="showForm = false; saveForm.reset()"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    {{ t('settings.api_key.cancel') }}
                </button>
            </div>
        </form>

        <!-- No key set yet -->
        <p v-if="!hasApiKey && !showForm" class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            {{ t('settings.api_key.none_set') }}
        </p>
    </div>
</template>
