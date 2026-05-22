<script setup lang="ts">
import ApiKeyInput from '@/Components/ApiKeyInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface Settings {
    preferred_difficulty: 'junior' | 'mid' | 'senior';
    daily_goal: number;
    theme: 'light' | 'dark' | 'system';
}

const props = defineProps<{
    settings: Settings;
    has_api_key: boolean;
    status?: string;
}>();

const form = useForm<Settings>({
    preferred_difficulty: props.settings.preferred_difficulty,
    daily_goal: props.settings.daily_goal,
    theme: props.settings.theme,
});

function submit(): void {
    form.patch(route('settings.update'));
}

const difficulties = [
    { value: 'junior', label: 'Junior' },
    { value: 'mid', label: 'Mid' },
    { value: 'senior', label: 'Senior' },
] as const;

const themes = [
    { value: 'light', label: 'Light' },
    { value: 'dark', label: 'Dark' },
    { value: 'system', label: 'System' },
] as const;
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">

                <!-- Success message -->
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="status === 'settings-updated'"
                        class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
                    >
                        Settings saved.
                    </p>
                </Transition>

                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Interview Preferences -->
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900">
                            Interview Preferences
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Adjust the difficulty level and your daily practice goal.
                        </p>

                        <!-- Difficulty -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700">
                                Preferred Difficulty
                            </label>
                            <div class="mt-2 flex gap-3">
                                <label
                                    v-for="d in difficulties"
                                    :key="d.value"
                                    class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition"
                                    :class="form.preferred_difficulty === d.value
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-300 text-gray-700 hover:border-indigo-400'"
                                >
                                    <input
                                        type="radio"
                                        :value="d.value"
                                        v-model="form.preferred_difficulty"
                                        class="sr-only"
                                    />
                                    {{ d.label }}
                                </label>
                            </div>
                            <p v-if="form.errors.preferred_difficulty" class="mt-1 text-sm text-red-600">
                                {{ form.errors.preferred_difficulty }}
                            </p>
                        </div>

                        <!-- Daily goal -->
                        <div class="mt-6">
                            <label
                                for="daily_goal"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Daily Goal (questions per day)
                            </label>
                            <input
                                id="daily_goal"
                                type="number"
                                min="1"
                                max="50"
                                v-model.number="form.daily_goal"
                                class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            <p v-if="form.errors.daily_goal" class="mt-1 text-sm text-red-600">
                                {{ form.errors.daily_goal }}
                            </p>
                        </div>
                    </div>

                    <!-- Appearance -->
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900">
                            Appearance
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Choose the colour theme for the interface.
                        </p>

                        <div class="mt-4 flex gap-3">
                            <label
                                v-for="t in themes"
                                :key="t.value"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition"
                                :class="form.theme === t.value
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                    : 'border-gray-300 text-gray-700 hover:border-indigo-400'"
                            >
                                <input
                                    type="radio"
                                    :value="t.value"
                                    v-model="form.theme"
                                    class="sr-only"
                                />
                                {{ t.label }}
                            </label>
                        </div>
                        <p v-if="form.errors.theme" class="mt-1 text-sm text-red-600">
                            {{ form.errors.theme }}
                        </p>
                    </div>

                    <!-- API Key -->
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <ApiKeyInput :has-api-key="has_api_key" />
                    </div>

                    <!-- Save -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                        >
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
