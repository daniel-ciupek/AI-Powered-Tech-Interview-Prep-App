# PrepMind

[![CI](https://github.com/daniel-ciupek/AI-Powered-Tech-Interview-Prep-App/actions/workflows/ci.yml/badge.svg?branch=dev)](https://github.com/daniel-ciupek/AI-Powered-Tech-Interview-Prep-App/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D?logo=vue.js&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![PWA](https://img.shields.io/badge/PWA-installable-5A0FC8?logo=pwa&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

AI-powered tech-interview prep — spaced repetition and a conversational
interview simulator powered by your own Google Gemini key (BYOK).

PrepMind generates technical questions tailored to your level, schedules
them with the SM-2 algorithm, and lets you practise a live conversation
with an AI interviewer who later returns a written assessment. The app
is Polish-first with full English fallback, dark mode, installable as a
PWA, and ships with text-to-speech on every question and AI reply.

> **Status:** active development. Phase 5 (UX polish — dashboard,
> onboarding wizard, PWA, dark mode, TTS) is complete. Phase 6 polish is
> next.

## Features

- 🧠 **AI-generated questions** — Gemini 2.5 Flash, scoped to your
  difficulty (junior / mid / senior) and a free-text list of topics.
- 🔁 **Spaced repetition** — SM-2 engine schedules every reviewed
  question; daily study sessions surface only what is due.
- 💬 **Interview simulator** — multi-turn chat with an AI "Anna"
  recruiter persona, ending with a structured Polish assessment report
  (queued via Laravel jobs).
- 📊 **Personal dashboard** — streak, daily goal progress, 30-day
  retention rate, 12-week review heatmap.
- 🌗 **Dark mode** without FOUC — preference stored per-user, applied
  before paint via an inline script.
- 🌍 **Polish + English** — `vue-i18n` on the frontend, Laravel
  translations on the backend (errors, validation, emails).
- 📱 **Installable PWA** — manifest, icons, service worker, offline
  shell, `NetworkOnly` strategy so SPA navigations always get fresh
  HTML.
- 🔊 **Text-to-speech** — Web Speech API; one click on any question or
  AI reply.
- 🔐 **Encrypted BYOK** — your Gemini key is stored with
  `Crypt::encryptString`, hidden via `#[Hidden]`, never logged.

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 12 · PHP 8.3 |
| Database | PostgreSQL 16 (uses `jsonb`, `jsonb_array_elements_text`) |
| Cache & Queue | Redis 7 |
| Frontend | Inertia.js 2 · Vue 3 (Composition API + `<script setup lang="ts">`) |
| Styling | Tailwind CSS 3 with `darkMode: 'class'` |
| Auth | Laravel Breeze (Inertia+Vue preset) |
| AI | Google Gemini 2.5 Flash (`thinkingBudget=0`, forced JSON mime) |
| PWA | `vite-plugin-pwa` 1 with inline workbox runtime |
| Tests | Pest 3 (Feature + Unit), 159 tests, ≥93% coverage |
| Quality | Pint · Larastan level 8 · Husky + lint-staged · gitleaks · commitlint |
| Env | Laravel Sail (Docker) |

## Architecture

The app follows a thin-controller / fat-action pattern. Controllers
delegate to **invokable Action classes** (`app/Actions/{Domain}/…`)
which compose **Services** (`app/Services/{Domain}/…`) for cross-cutting
concerns — the Gemini client, SM-2 engine, AI response cache. Eloquent
models hold relationships and casts; business logic stays out.

```
HTTP → FormRequest → Controller → Action → Service → Model → DB
                                       ↓
                                     Event → Listener (streak, jobs)
```

- **Actions** are `final` and `__invoke`-able. Examples:
  `GenerateQuestionAction`, `RecordReviewAction`,
  `LoadDashboardStatsAction`, `CompleteOnboardingAction`.
- **Policies** enforce per-row authorisation
  (`QuestionPolicy`, `InterviewSessionPolicy`).
- **Rate limiters** named in `AppServiceProvider`
  (`interview-start`, `interview-message`, `question-generate`),
  attached to specific API routes.
- **AI cache** keyed on `(user_id, sha256(prompt))` so generated
  question content never crosses users.
- **Middleware** `EnsureUserIsOnboarded` gates feature pages until the
  4-step wizard is completed, while leaving `PATCH /settings` and
  `POST /settings/api-key` reachable from inside the wizard.

## Getting started

```bash
git clone https://github.com/daniel-ciupek/AI-Powered-Tech-Interview-Prep-App
cd "AI-Powered-Tech-Interview-Prep-App"
cp .env.example .env

./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail php artisan key:generate
./vendor/bin/sail php artisan migrate --seed
./vendor/bin/sail npm run dev
```

The app is served at <http://localhost>. Sign up, complete the 4-step
onboarding wizard (paste a Gemini API key from
[Google AI Studio](https://aistudio.google.com/apikey)), and you are in.

### PWA build

```bash
./vendor/bin/sail npm run build
```

This generates `public/build/`, then `scripts/post-build-pwa.mjs`
rewrites the service worker's precache URLs to absolute paths and
copies `sw.js` + `manifest.webmanifest` to the public root so the SW
can claim scope `/`.

## Development workflow

```bash
./vendor/bin/sail composer test:all       # Pint --test + Larastan + Pest
./vendor/bin/sail composer lint           # Pint auto-fix
./vendor/bin/sail composer analyse        # Larastan level 8
./vendor/bin/sail composer test           # Pest (parallel)
./vendor/bin/sail composer test:coverage  # ≥80% gate
```

Git hooks (Husky + lint-staged + commitlint + gitleaks) enforce style,
secret scanning and Conventional Commits on every commit; pre-push
re-runs the full Pest suite.

## Deployment

See [DEPLOY.md](DEPLOY.md) for the full production setup — Ubuntu 24.04 VPS
with native PHP-FPM, nginx, Postgres 16, Redis 7, supervisor for queues,
and a releases-style symlink deploy with one-line rollback.

## Internationalization

PrepMind is bilingual (Polish-first, English fallback) end-to-end.

- **Frontend** (Vue 3 + Inertia) — `resources/js/i18n/locales/{pl,en}/*.json`,
  auto-loaded by `resources/js/i18n/index.ts` through Vite's
  `import.meta.glob`. Each JSON file is a namespace, so `auth.json` is
  used as `$t('auth.login.submit')`. Mounted in `app.ts` with
  `legacy: false`, locale `pl`, fallback `en`.
- **Backend** (Laravel) — `lang/{pl,en}/*.php` (auth, validation,
  passwords, pagination + `messages.php` for AI/API copy). Controllers
  and FormRequests use `__('messages.…')`.

**Adding a new language:** copy `lang/pl/` → `lang/xx/`, copy
`resources/js/i18n/locales/pl/` → `…/xx/`, translate the values, set
`APP_LOCALE=xx`.

## Tag system

Topic tags on `interview_sessions.topic_tags` (jsonb) are **free-text** —
users type any topic. The Vue side uses `Components/FreeTextTagInput.vue`
(chip preview + HTML5 `<datalist>` autosuggest). `GET /api/tags`
aggregates the top 30 most-used tags via PostgreSQL's
`jsonb_array_elements_text` and caches for 10 minutes.

## License

[MIT](LICENSE)
