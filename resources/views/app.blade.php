<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#4F46E5" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#1f2937" media="(prefers-color-scheme: dark)">
        <meta name="description" content="AI-powered tech interview prep — spaced repetition + simulator.">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="PrepMind">

        <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.webmanifest">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{-- Apply theme before paint to avoid flash --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('prepmind:theme');
                    var serverPref = @json(auth()->user()?->theme->value ?? null);
                    var pref = stored || serverPref || 'system';
                    var dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', dark);
                    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
                    window.__INITIAL_THEME__ = pref;
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        @inertia
    </body>
</html>
