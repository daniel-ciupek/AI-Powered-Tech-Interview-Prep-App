<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
        <h3 class="text-lg font-medium text-gray-900">Gemini API Key</h3>
        <p class="mt-1 text-sm text-gray-600">
            Your key is stored encrypted and never exposed.
            Get one at
            <a
                href="https://aistudio.google.com/apikey"
                target="_blank"
                rel="noopener noreferrer"
                class="text-indigo-600 underline hover:text-indigo-500"
            >
                Google AI Studio
            </a>.
        </p>

        <!-- Key is set, form hidden -->
        <div v-if="hasApiKey && !showForm" class="mt-4 flex items-center gap-4">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                API key configured
            </span>
            <button
                type="button"
                @click="showForm = true"
                class="text-sm text-indigo-600 underline hover:text-indigo-500"
            >
                Change
            </button>
            <form @submit.prevent="remove">
                <button
                    type="submit"
                    :disabled="removeForm.processing"
                    class="text-sm text-red-600 underline hover:text-red-500 disabled:opacity-50"
                >
                    Remove
                </button>
            </form>
        </div>

        <!-- Input form -->
        <form v-if="showForm" @submit.prevent="save" class="mt-4 space-y-3">
            <div>
                <label for="api_key" class="block text-sm font-medium text-gray-700">
                    API Key
                </label>
                <input
                    id="api_key"
                    type="password"
                    autocomplete="off"
                    v-model="saveForm.api_key"
                    placeholder="AIza..."
                    class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                <p v-if="saveForm.errors.api_key" class="mt-1 text-sm text-red-600">
                    {{ saveForm.errors.api_key }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="saveForm.processing || saveForm.api_key.length === 0"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                >
                    Save Key
                </button>
                <button
                    v-if="hasApiKey"
                    type="button"
                    @click="showForm = false; saveForm.reset()"
                    class="text-sm text-gray-500 hover:text-gray-700"
                >
                    Cancel
                </button>
            </div>
        </form>

        <!-- No key set yet -->
        <p v-if="!hasApiKey && !showForm" class="mt-3 text-sm text-gray-500">
            No API key set.
        </p>
    </div>
</template>
