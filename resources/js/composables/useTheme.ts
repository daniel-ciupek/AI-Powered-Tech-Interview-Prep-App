import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

export type ThemePreference = 'light' | 'dark' | 'system';

const STORAGE_KEY = 'prepmind:theme';

const prefersDark = (): boolean =>
    window.matchMedia('(prefers-color-scheme: dark)').matches;

const resolveEffectiveTheme = (preference: ThemePreference): 'light' | 'dark' =>
    preference === 'system' ? (prefersDark() ? 'dark' : 'light') : preference;

const applyToRoot = (theme: 'light' | 'dark'): void => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.style.colorScheme = theme;
};

const readStoredPreference = (): ThemePreference | null => {
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);
        return value === 'light' || value === 'dark' || value === 'system' ? value : null;
    } catch {
        return null;
    }
};

function readSharedTheme(): ThemePreference | null {
    try {
        const value = (usePage().props as Record<string, unknown>).theme;
        return value === 'light' || value === 'dark' || value === 'system' ? value : null;
    } catch {
        return null;
    }
}

export function useTheme() {
    const preference = ref<ThemePreference>(
        readStoredPreference() ?? readSharedTheme() ?? 'system',
    );

    const effective = computed(() => resolveEffectiveTheme(preference.value));

    function setPreference(value: ThemePreference): void {
        preference.value = value;
        try {
            window.localStorage.setItem(STORAGE_KEY, value);
        } catch {
            // storage may be disabled
        }
        applyToRoot(resolveEffectiveTheme(value));
    }

    let mediaQuery: MediaQueryList | null = null;
    const onSystemChange = (): void => {
        if (preference.value === 'system') {
            applyToRoot(resolveEffectiveTheme('system'));
        }
    };

    onMounted(() => {
        applyToRoot(effective.value);
        mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', onSystemChange);
    });

    onUnmounted(() => {
        mediaQuery?.removeEventListener('change', onSystemChange);
    });

    watch(preference, () => applyToRoot(effective.value));

    return { preference, effective, setPreference };
}

export function bootstrapTheme(): void {
    const stored = readStoredPreference();
    const inline = (window as unknown as { __INITIAL_THEME__?: ThemePreference }).__INITIAL_THEME__;
    const initial: ThemePreference = stored ?? inline ?? 'system';
    applyToRoot(resolveEffectiveTheme(initial));
}
