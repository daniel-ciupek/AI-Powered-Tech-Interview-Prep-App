<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Internationalization

PrepMind is bilingual (Polish-first, English fallback) end-to-end.

**Frontend (Vue 3 + Inertia)** — translations live in `resources/js/i18n/locales/{pl,en}/*.json` and are auto-loaded by `resources/js/i18n/index.ts` through Vite's `import.meta.glob`. Each JSON file becomes a namespace, so `auth.json` is referenced as `$t('auth.login.submit')`. The plugin is mounted in `app.ts` with `legacy: false` (Composition API), default locale `pl`, fallback `en`.

**Backend (Laravel)** — translations live in `lang/{pl,en}/*.php` (auth, validation, passwords, pagination + custom `messages.php` for AI/API error copy). Controllers and FormRequests use `__('messages.…')`. `config/app.php` defaults `locale=pl`, `fallback_locale=en`, `faker_locale=pl_PL`; override via `APP_LOCALE` in `.env`.

**Adding a new language:**
1. Copy `lang/pl/` → `lang/xx/` and translate the PHP arrays.
2. Copy `resources/js/i18n/locales/pl/` → `…/xx/` and translate the JSON files (keep the keys).
3. Set `APP_LOCALE=xx` in `.env` (and `i18n.locale` in `resources/js/i18n/index.ts` if you want it as the runtime default).

## Tag system

Topic tags on `interview_sessions.topic_tags` (jsonb) are **free-text** — users type any topic they want. The Vue side uses `Components/FreeTextTagInput.vue` (chip preview + HTML5 `<datalist>` autosuggest). The backend `GET /api/tags` aggregates the top 30 most-used tags from the existing `interview_sessions` rows (Postgres `jsonb_array_elements_text`) and caches them for 10 minutes. There is no separate tags table — the `spatie/laravel-tags` package was removed in favour of this simpler model.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
