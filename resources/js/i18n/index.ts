import { createI18n, type I18nOptions } from 'vue-i18n';

type LocaleMessages = NonNullable<I18nOptions['messages']>;
type Namespace = LocaleMessages[string];

const modules = import.meta.glob<{ default: Namespace }>(
    './locales/**/*.json',
    { eager: true },
);

const messages: LocaleMessages = {};

for (const path in modules) {
    const match = /\.\/locales\/([^/]+)\/([^/]+)\.json$/.exec(path);
    if (!match) continue;

    const [, locale, namespace] = match;
    const bucket = (messages[locale] ??= {}) as Record<string, Namespace>;
    bucket[namespace] = modules[path].default;
}

export const i18n = createI18n({
    legacy: false,
    locale: 'pl',
    fallbackLocale: 'en',
    messages,
});
