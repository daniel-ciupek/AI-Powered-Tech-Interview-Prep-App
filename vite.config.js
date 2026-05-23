import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: false,
            includeAssets: [
                'favicon.ico',
                'icons/icon-192.png',
                'icons/icon-512.png',
                'icons/icon-maskable-512.png',
                'icons/apple-touch-icon.png',
                'icons/favicon-32.png',
                'offline.html',
            ],
            manifest: {
                name: 'PrepMind',
                short_name: 'PrepMind',
                description: 'AI-powered tech interview prep — spaced repetition + simulator.',
                lang: 'pl',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                orientation: 'portrait',
                theme_color: '#4F46E5',
                background_color: '#ffffff',
                icons: [
                    {
                        src: '/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-maskable-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
            workbox: {
                inlineWorkboxRuntime: true,
                navigateFallback: '/offline.html',
                navigateFallbackDenylist: [
                    /^\/api\//,
                    /^\/login/,
                    /^\/register/,
                    /^\/logout/,
                    /^\/forgot-password/,
                    /^\/reset-password/,
                    /^\/verify-email/,
                    /^\/confirm-password/,
                    /^\/email\//,
                ],
                globPatterns: ['**/*.{js,css,woff2}'],
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.bunny\.net\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'prepmind-fonts',
                            expiration: {
                                maxEntries: 16,
                                maxAgeSeconds: 60 * 60 * 24 * 90,
                            },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        urlPattern: /\/icons\/.*\.png$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'prepmind-icons',
                            expiration: {
                                maxEntries: 16,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                        },
                    },
                ],
            },
            devOptions: {
                enabled: false,
            },
        }),
    ],
});
