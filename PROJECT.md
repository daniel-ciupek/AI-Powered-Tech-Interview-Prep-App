# PrepMind — AI-Powered Tech Interview Prep App

> Spersonalizowany trener rozmów rekrutacyjnych dla programistów — łączy moc **Gemini API** z **algorytmem inteligentnych powtórek (SuperMemo-2)**, aby uczyć cię tylko tego, czego jeszcze nie umiesz.

---

## Spis Treści

1. [Wizja Produktu](#1-wizja-produktu)
2. [Metryki Sukcesu](#2-metryki-sukcesu)
3. [Główne Funkcjonalności](#3-główne-funkcjonalności)
4. [Specyfikacja Algorytmu SM2](#4-specyfikacja-algorytmu-sm2)
5. [Schemat Bazy Danych](#5-schemat-bazy-danych)
6. [Strategia Integracji z Gemini API](#6-strategia-integracji-z-gemini-api)
7. [Architektura Backendowa](#7-architektura-backendowa)
8. [Architektura Frontendowa](#8-architektura-frontendowa)
9. [Wymagania UX/UI](#9-wymagania-uxui)
10. [Bezpieczeństwo](#10-bezpieczeństwo)
11. [Wydajność i Skalowalność](#11-wydajność-i-skalowalność)
12. [Fazy Rozwoju](#12-fazy-rozwoju)
13. [Stack Technologiczny](#13-stack-technologiczny)
14. [Świadomie pomijamy w MVP](#14-świadomie-pomijamy-w-mvp)

---

## 1. Wizja Produktu

### Problem
Programiści przygotowujący się do rozmów rekrutacyjnych zwykle uczą się chaotycznie: czytają losowe artykuły, oglądają filmy, nie wracają do trudnych zagadnień w optymalnych momentach. Tradycyjne fiszki (Anki) wymagają ręcznego tworzenia treści, a generyczne quizy nie odzwierciedlają realiów rozmów technicznych.

### Persona Użytkownika
**Junior/Mid Backend Developer** (PHP/Laravel) z polskiego rynku pracy, który:
- aktywnie szuka pracy lub planuje zmianę za 3-6 miesięcy,
- chce uczyć się efektywnie w krótkich sesjach (15-30 min dziennie),
- ma własny klucz Gemini API (model BYOK — *Bring Your Own Key*),
- ceni nowoczesny UX (dark mode, animacje, skróty klawiszowe).

### Cel
Stworzyć aplikację, która:
1. **Generuje** spersonalizowane pytania rekrutacyjne (Gemini API) na podstawie wybranego stacku i poziomu.
2. **Planuje** powtórki w optymalnych momentach (algorytm SM2).
3. **Symuluje** pełną rozmowę rekrutacyjną z AI-rekruterem.
4. Pokazuje umiejętności programisty na poziomie **mid/senior** w portfolio GitHub.

---

## 2. Metryki Sukcesu

### Techniczne (CV-driven)
| Metryka | Cel |
|---|---|
| Pokrycie testami (domena) | ≥ 90% |
| Pokrycie testami (całość) | ≥ 80% |
| Larastan level | 8 (max) |
| Lighthouse Performance | ≥ 90 |
| Lighthouse Accessibility | ≥ 90 |
| Czas odpowiedzi p95 (bez wywołań AI) | < 200 ms |
| Czas startu kontenera (Sail) | < 30 s |

### Produktowe
- Użytkownik od kliknięcia "Zaloguj" do pierwszej ocenionej fiszki: **< 30 sekund**.
- Nowy użytkownik bez konfiguracji widzi onboarding w **< 3 ekranach**.
- Sesja nauki na 10 fiszek: **< 5 minut** (bez czytania długich opisów).

---

## 3. Główne Funkcjonalności

### 3.1. System Inteligentnych Powtórek (SM2)
Każde pytanie ma przypisaną datę następnej powtórki (`next_review_at`). Po obejrzeniu odpowiedzi użytkownik klika **zielony** ("Znam") lub **czerwony** ("Nie znam"). Algorytm SM2 (szczegóły w sekcji 4) wylicza nowy interwał.

### 3.2. Dynamiczne Generowanie Pytań (Gemini API)
Backend dynamicznie buduje *System Prompt* dla Gemini, wymuszając odpowiedź w ścisłym formacie JSON. Pytania zapisywane są w bazie i podlegają systemowi powtórek.

### 3.3. Symulator Rozmowy Rekrutacyjnej (Stateful Chat)
Pełna konwersacja z AI w roli "wymagającego seniora rekrutera". Historia zapamiętana w bazie, na końcu generowany jest **raport audytowy** (mocne strony, braki, sugestie do nauki).

### 3.4. Tagi i Kategorie (`spatie/laravel-tags`)
Polimorficzne tagi pozwalają taggować pytania i sesje wieloma technologiami jednocześnie (np. `laravel`, `docker`, `mysql`). Filtrowanie i przeglądanie po tagach.

### 3.5. Poziomy Trudności
Enum PHP 8.1: `Junior`, `Mid`, `Senior`. Wpływa na prompt do Gemini i prezentację UI.

### 3.6. Text-to-Speech (TTS)
- **MVP**: przeglądarkowe **Web Speech API** (darmowe, w JS).
- **Future**: opcjonalne ElevenLabs / Google TTS (cachowane MP3 na dysku).

### 3.7. Dashboard z Statystykami
- Streak (dni z rzędu z nauki).
- Retention rate (% pytań ocenionych "Znam" w 2. powtórce).
- Weak topics (tagi z najniższym retention).
- Wykres aktywności tygodniowej (heatmap GitHub-style).

### 3.8. BYOK — Bring Your Own Key
Użytkownik wkleja klucz Gemini API w profilu. Klucz **zaszyfrowany** przez `Crypt::encryptString()` w bazie; deszyfrowany wyłącznie w pamięci RAM podczas wywołania.

---

## 4. Specyfikacja Algorytmu SM2

Klasyczny algorytm **SuperMemo-2** (Piotr Woźniak, 1987). Implementacja: `App\Services\Repetition\Sm2Engine`.

### Parametry
| Pole | Typ | Domyślnie | Opis |
|---|---|---|---|
| `ease_factor` (EF) | DECIMAL(4,2) | 2.50 | Współczynnik łatwości; min 1.30 |
| `interval_days` | INT | 0 | Bieżący interwał w dniach |
| `repetitions_count` | INT | 0 | Liczba poprawnych powtórek z rzędu |
| `quality_last` (q) | INT (0-5) | — | Ostatnia ocena |

### Mapowanie UI → quality
| Klik UI | q | Znaczenie |
|---|---|---|
| Czerwony ("Nie znam") | **2** | Pamiętane z trudem / nieprawidłowo |
| Zielony ("Znam") | **4** | Poprawnie, z wysiłkiem |
| (Tryb AI-eval) | 0-5 | Gemini ocenia odpowiedź tekstową |

### Wzór
```
JEŚLI q < 3:
    repetitions_count = 0
    interval_days = 1
W PRZECIWNYM RAZIE:
    repetitions_count += 1
    JEŚLI repetitions_count == 1: interval_days = 1
    JEŚLI repetitions_count == 2: interval_days = 6
    INACZEJ:                       interval_days = round(prev_interval * EF)

EF_new = EF + (0.1 - (5 - q) * (0.08 + (5 - q) * 0.02))
EF_new = max(EF_new, 1.30)

next_review_at = now() + interval_days
```

### Edge cases
- Pierwsza ocena (`repetitions_count == 0`) z q ≥ 3 → interval = 1 dzień.
- Quality 0-2 zawsze resetuje progres (interval = 1 dzień).
- EF nigdy nie spada poniżej 1.30 (zabezpieczenie przed nieskończonym kółkiem).
- Każda zmiana zapisywana w tabeli `review_logs` (audit trail).

---

## 5. Schemat Bazy Danych

> **Baza:** PostgreSQL 16. Wszystkie tabele z `id` BIGSERIAL, `created_at`, `updated_at` (timestamps Laravel).

### 5.1. `users` (rozszerzenie domyślnej Breeze)
| Kolumna | Typ | Notatki |
|---|---|---|
| `id` | BIGSERIAL PK | |
| `name`, `email`, `password`, `email_verified_at` | (Breeze defaults) | |
| `gemini_api_key_encrypted` | TEXT NULL | `Crypt::encryptString` |
| `preferred_difficulty` | VARCHAR(10) | enum: junior/mid/senior |
| `daily_goal` | SMALLINT DEFAULT 10 | Pytań dziennie |
| `streak_count` | SMALLINT DEFAULT 0 | |
| `last_studied_at` | TIMESTAMP NULL | |
| `theme` | VARCHAR(10) DEFAULT 'dark' | dark/light/system |

### 5.2. `questions`
| Kolumna | Typ | Notatki |
|---|---|---|
| `id` | BIGSERIAL PK | |
| `user_id` | BIGINT FK → users.id ON DELETE CASCADE | |
| `content` | TEXT NOT NULL | Treść pytania |
| `expected_answer` | TEXT NULL | Wzorcowa odpowiedź (opcjonalna) |
| `expected_keywords` | JSONB DEFAULT '[]' | Słowa kluczowe do oceny |
| `difficulty` | VARCHAR(10) NOT NULL | enum |
| `source` | VARCHAR(20) NOT NULL DEFAULT 'ai_generated' | ai_generated / user_created |
| `created_at`, `updated_at` | TIMESTAMPS | |

**Indeksy:** `(user_id, difficulty)`, `(user_id, created_at DESC)`.

### 5.3. `repetitions`
| Kolumna | Typ | Notatki |
|---|---|---|
| `id` | BIGSERIAL PK | |
| `user_id` | BIGINT FK | |
| `question_id` | BIGINT FK ON DELETE CASCADE | |
| `ease_factor` | DECIMAL(4,2) DEFAULT 2.50 | |
| `interval_days` | INT DEFAULT 0 | |
| `repetitions_count` | INT DEFAULT 0 | |
| `quality_last` | SMALLINT NULL | |
| `next_review_at` | TIMESTAMP NOT NULL | |
| `last_reviewed_at` | TIMESTAMP NULL | |

**Indeksy:** `UNIQUE(user_id, question_id)`, `(user_id, next_review_at)` (kluczowy dla sesji nauki).

### 5.4. `review_logs` (audit trail)
| Kolumna | Typ |
|---|---|
| `id`, `repetition_id` FK | |
| `quality` SMALLINT | |
| `ease_before`, `ease_after` DECIMAL(4,2) | |
| `interval_before`, `interval_after` INT | |
| `created_at` TIMESTAMP | |

### 5.5. `interview_sessions`
| Kolumna | Typ | Notatki |
|---|---|---|
| `id` | BIGSERIAL PK | |
| `user_id` | BIGINT FK | |
| `topic_tags` | JSONB | Lista tagów wybranych na start |
| `difficulty` | VARCHAR(10) | |
| `status` | VARCHAR(20) | active / completed / abandoned |
| `final_report` | TEXT NULL | Markdown z oceną AI |
| `tokens_used_total` | INT DEFAULT 0 | Sum tokens for cost tracking |
| `started_at`, `ended_at` | TIMESTAMP | |

### 5.6. `interview_messages`
| Kolumna | Typ |
|---|---|
| `id` | BIGSERIAL PK |
| `session_id` | BIGINT FK ON DELETE CASCADE |
| `role` | VARCHAR(10) — enum: user/assistant/system |
| `content` | TEXT NOT NULL |
| `tokens_used` | INT DEFAULT 0 |
| `created_at` | TIMESTAMP |

**Indeks:** `(session_id, created_at)`.

### 5.7. `ai_response_cache`
Cache deterministycznych odpowiedzi AI, by oszczędzić tokeny.

| Kolumna | Typ |
|---|---|
| `id` | BIGSERIAL PK |
| `prompt_hash` | CHAR(64) UNIQUE — SHA256 |
| `response` | JSONB NOT NULL |
| `model` | VARCHAR(50) |
| `tokens_in`, `tokens_out` | INT |
| `created_at` | TIMESTAMP |

**TTL:** 7 dni (cron czyści stare wpisy).

### 5.8. Spatie Tags
Standardowe tabele `tags` i `taggables` z paczki `spatie/laravel-tags` — używane do tagowania `questions` i `interview_sessions`.

---

## 6. Strategia Integracji z Gemini API

### 6.1. System Prompt — Generator Pytania
```
Jesteś wymagającym seniorem rekruterem technicznym z 10-letnim doświadczeniem.
Przeprowadzasz rozmowę kwalifikacyjną na poziomie [DIFFICULTY] z kandydatem
aplikującym na stanowisko [Backend Developer].

Wygeneruj JEDNO praktyczne pytanie łączące wiedzę z zakresu: [TAGS_LIST].
Pytanie ma być:
- konkretne (nie ogólnikowe),
- testujące zrozumienie, nie zapamiętane definicje,
- możliwe do odpowiedzi w 2-5 minutach.

Odpowiedz WYŁĄCZNIE w formacie JSON, bez markdown, bez komentarzy:
{
  "question": "...",
  "expected_answer": "...",
  "expected_keywords": ["...", "...", "..."],
  "difficulty": "[DIFFICULTY]"
}
```

### 6.2. System Prompt — Symulator Rozmowy
```
Wcielasz się w rolę "Anny" — wymagającego, ale życzliwego seniora
rekrutera technicznego. Prowadzisz rozmowę kwalifikacyjną na poziomie
[DIFFICULTY] na stanowisko Backend Developer (specjalizacja: [TAGS_LIST]).

Zasady:
1. Zadawaj pytania pojedynczo, czekaj na odpowiedź.
2. Drąż temat dopytując "dlaczego?", "a jak byś zrobił to inaczej?".
3. Jeśli kandydat nie wie, pomóż mu naprowadzeniem (ale zanotuj brak).
4. Po 8-10 wymianach lub na komendę użytkownika "kończymy" — wygeneruj raport.

Odpowiadaj naturalnie po polsku. Nie używaj markdown w trakcie rozmowy.
```

### 6.3. Walidacja Odpowiedzi
- Schemat JSON walidowany przez `justinrainbow/json-schema` lub custom FormRequest.
- Jeśli Gemini zwróci nieprawidłowy JSON → 1 retry z poprawką w prompcie ("Twoja poprzednia odpowiedź była nieprawidłowym JSON. Spróbuj ponownie zwracając WYŁĄCZNIE JSON").
- Po 2 nieudanych próbach → wyjątek `InvalidGeminiResponseException` + log + komunikat dla użytkownika.

### 6.4. Retry & Backoff
```
HTTP::retry(3, function ($exception, $request) {
    return $exception instanceof ConnectionException
        || ($exception instanceof RequestException && $exception->response->status() >= 500);
}, throw: false)
->withOptions(['delay' => fn($attempt) => 1000 * 2 ** $attempt]) // 1s, 2s, 4s
```

### 6.5. Rate Limiting
- **Per-user limit:** 60 zapytań/min (Laravel `RateLimiter`).
- **Soft daily limit:** dashboard pokazuje % zużycia (np. "Zużyłeś 12 000 / 50 000 tokenów dzisiaj").
- **Hard daily limit:** ustawialny w profilu (domyślnie wyłączony).

### 6.6. Cache Odpowiedzi (`ai_response_cache`)
- Klucz: `sha256(prompt + tags_sorted + difficulty + model)`.
- TTL: 7 dni.
- **Tylko dla generowania pytań** (chat ma być świeży za każdym razem).
- Oszczędność: powtórne generowanie tego samego zestawu tagów = 0 tokenów.

### 6.7. Koszty
- Tracking `tokens_used` w każdej wiadomości i sesji.
- Pricing Gemini przeliczany w `App\Services\Gemini\CostCalculator`.
- Dashboard: "Twoje zużycie w tym miesiącu: 0.12 USD".

---

## 7. Architektura Backendowa

### 7.1. Warstwy
```
HTTP Request
   ↓
Route → Controller (thin, ~5 linii)
   ↓
FormRequest (walidacja)
   ↓
Action / Service (logika biznesowa)
   ↓
Model (Eloquent) / External API (Gemini)
   ↓
API Resource (formatowanie odpowiedzi)
   ↓
HTTP Response (Inertia / JSON)
```

### 7.2. Kluczowe klasy
| Klasa | Odpowiedzialność |
|---|---|
| `App\Services\Gemini\GeminiClient` | Niski-poziomowy klient HTTP |
| `App\Services\Gemini\PromptBuilder` | Buduje System Prompty |
| `App\Services\Gemini\ResponseValidator` | Walidacja JSON schema |
| `App\Services\Gemini\CostCalculator` | Liczy zużycie tokenów |
| `App\Services\Repetition\Sm2Engine` | Pure logic algorytmu SM2 |
| `App\Actions\Questions\GenerateQuestionAction` | Orkiestruje generowanie pytania |
| `App\Actions\Repetition\RecordReviewAction` | Zapisuje review + update SM2 |
| `App\Actions\Interview\StartInterviewAction` | Inicjuje sesję |
| `App\Actions\Interview\SendMessageAction` | Wysyła wiadomość do Gemini |
| `App\Actions\Interview\GenerateReportAction` | Tworzy raport końcowy |
| `App\Jobs\GenerateInterviewReportJob` | Asynchroniczny raport |
| `App\Jobs\GenerateBulkQuestionsJob` | Bulk generation w tle |

### 7.3. Eventy & Listenery
| Event | Listener | Efekt |
|---|---|---|
| `QuestionReviewed` | `UpdateUserStreak` | Aktualizuje `streak_count`, `last_studied_at` |
| `QuestionReviewed` | `LogReviewMetric` | Insert do `review_logs` |
| `InterviewCompleted` | `QueueReportGeneration` | Dispatch jobu raportu |

### 7.4. Policies (autoryzacja)
- `QuestionPolicy` — user widzi tylko swoje pytania.
- `RepetitionPolicy` — to samo.
- `InterviewSessionPolicy` — sesja należy tylko do użytkownika.

### 7.5. API Resources
Wszystkie odpowiedzi Inertia/JSON przechodzą przez Resources (np. `QuestionResource`) — nie ma "raw model serialization".

---

## 8. Architektura Frontendowa (Inertia 2 + Vue 3 + Tailwind 4)

### 8.1. Struktura katalogów
```
resources/js/
├── Pages/
│   ├── Auth/             # Breeze defaults (Login, Register, ResetPassword)
│   ├── Dashboard/        # Index.vue (statystyki, streak)
│   ├── Study/            # Session.vue (review jednej fiszki)
│   ├── Interview/        # Index.vue, Chat.vue, Report.vue
│   ├── Questions/        # Index.vue (lista), Show.vue, Create.vue
│   └── Settings/         # Profile.vue, ApiKey.vue, Preferences.vue
├── Components/
│   ├── UI/               # Button.vue, Card.vue, Input.vue, Modal.vue
│   ├── Question/         # QuestionCard.vue, AnswerReveal.vue, RatingButtons.vue
│   ├── Chat/             # MessageBubble.vue, TypingIndicator.vue
│   └── Stats/            # StreakWidget.vue, RetentionChart.vue
├── Composables/
│   ├── useSpeechSynthesis.ts    # Web Speech API wrapper
│   ├── useKeyboardShortcuts.ts  # Skróty klawiszowe globalnie
│   ├── useAiStream.ts           # SSE/streaming dla czatu
│   └── useDarkMode.ts           # Theme switcher
├── stores/               # Pinia (tylko dla ciężkiego stanu — np. czat)
│   └── interviewSession.ts
└── Layouts/              # AuthenticatedLayout.vue, GuestLayout.vue
```

### 8.2. Komponenty UI
- Baza: **shadcn-vue** (radix-vue + Tailwind) lub własne komponenty na Headless UI.
- Ikony: **Lucide Vue** (lekkie, nowoczesne).
- Animacje: **Vue Transition** + **@vueuse/motion** lub własne CSS.
- Wykresy: **Chart.js** lub **ApexCharts** (heatmap aktywności).

### 8.3. Skróty Klawiszowe (sesja nauki)
| Klawisz | Akcja |
|---|---|
| `Space` | Odsłoń odpowiedź |
| `→` / `J` | Znam (zielony) |
| `←` / `F` | Nie znam (czerwony) |
| `N` | Następne pytanie |
| `S` | Czytaj na głos (TTS) |
| `?` | Pokaż listę skrótów |

### 8.4. State Management
- **Inertia props** dla normalnego stanu (formularze, listy).
- **Pinia** tylko dla utrzymania kontekstu czatu (wiele wiadomości w jednym widoku, optimistic updates).
- **localStorage** dla preferencji UI (theme, ulubione tagi).

---

## 9. Wymagania UX/UI

### 9.1. Estetyka
- **Dark mode jako domyślny** (target audience: programiści).
- **Glassmorphism** + subtle gradients (nowoczesny look 2026).
- **Typografia:** Inter (UI) + JetBrains Mono (kod w pytaniach).
- **Paleta:** ciemne tła (#0a0a0f), akcent niebieski/fioletowy (#6366f1), zielony sukcesu (#22c55e), czerwony błędu (#ef4444).

### 9.2. Responsywność
- **Mobile-first.** Layout działa od 360px (małe Androidy).
- Breakpointy Tailwind: sm/md/lg/xl/2xl.
- Sesja nauki użyteczna jednym kciukiem na telefonie.

### 9.3. PWA (Phase 5)
- Service Worker dla offline review (cache ostatnich 50 pytań).
- Manifest z ikoną, instalowalna z przeglądarki.
- Push notifications (opcjonalne — przypomnienie o codziennej powtórce).

### 9.4. Accessibility (WCAG 2.1 AA)
- Kontrast tekstu ≥ 4.5:1.
- Wszystkie interakcje dostępne z klawiatury.
- ARIA labels na ikonach-przyciskach.
- Focus visible (custom ring zamiast browser default).
- Screen reader friendly (sekcje `<main>`, `<nav>`, `<aside>`).
- `prefers-reduced-motion` respektowany (wyłączenie animacji).

### 9.5. Onboarding
3-ekranowy onboarding po pierwszym logowaniu:
1. "Witaj! Wklej swój klucz Gemini API" (z linkiem do generowania).
2. "Wybierz technologie, których chcesz się uczyć" (chips z tagami).
3. "Wybierz poziom trudności" (3 karty).

---

## 10. Bezpieczeństwo

### 10.1. Sekrety
- `.env` **NIGDY** w repo (sprawdzane przez gitleaks w pre-commit).
- Klucz Gemini API: zaszyfrowany (`Crypt::encryptString`), w pamięci tylko podczas wywołania, **nigdy** w logach.
- Klucz aplikacji (`APP_KEY`) generowany per środowisko.

### 10.2. CSRF & Sesje
- Sanctum w trybie SPA dla cookie-based auth.
- CSRF token na każdym mutacyjnym żądaniu (Inertia obsługuje automatycznie).
- Session lifetime: 2h aktywności, "remember me" 30 dni.

### 10.3. Walidacja
- **Każdy** input przez FormRequest.
- Eloquent Mass Assignment chroniony przez `$fillable` whitelist (nigdy `$guarded = []`).
- XSS: odpowiedzi AI (markdown) renderowane przez bezpieczny parser (`league/commonmark` z safe mode), nigdy `v-html` z surowym AI output.

### 10.4. Rate Limiting
- Auth endpoints: 5 prób/min.
- Generowanie pytań: 30/min per user.
- Globalny throttle: 60 req/min per IP.

### 10.5. Headers
Middleware ustawiający:
- `Content-Security-Policy` (strict).
- `X-Frame-Options: DENY`.
- `X-Content-Type-Options: nosniff`.
- `Referrer-Policy: strict-origin-when-cross-origin`.
- `Strict-Transport-Security` (na produkcji).

### 10.6. Dependencies
- `composer audit` w CI (sprawdza CVE).
- `npm audit` w CI.
- Dependabot na GitHubie (auto-PR dla minor/patch).

---

## 11. Wydajność i Skalowalność

### 11.1. Baza danych
- Indeksy: `next_review_at`, `(user_id, next_review_at)`, `(user_id, difficulty)`, `prompt_hash` UNIQUE.
- N+1 wykrywane przez Larastan + `Model::preventLazyLoading()` w `App\Providers\AppServiceProvider` w trybie non-production.
- Paginacja **wszędzie** (lista pytań, historia sesji, logi).

### 11.2. Cache
- **Redis** dla cache, queue, session.
- Cache statystyk dashboard (TTL 5 min).
- Cache odpowiedzi AI (TTL 7 dni, sekcja 6.6).

### 11.3. Kolejki
- Raport końcowy rozmowy → `GenerateInterviewReportJob` (queue: `reports`).
- Bulk generation pytań → `GenerateBulkQuestionsJob` (queue: `ai-heavy`, retry 3x).
- Worker: `php artisan queue:work --tries=3 --max-time=3600`.

### 11.4. Frontend
- Code splitting (Vite, lazy-loaded Pages).
- Image optimization (WebP, lazy load).
- Critical CSS inline, reszta async.

---

## 12. Fazy Rozwoju

Każda faza ma **Definition of Done (DoD)**:
- ✅ Wszystkie testy zielone (Pest).
- ✅ Larastan poziom 8 bez błędów.
- ✅ Laravel Pint bez warningów.
- ✅ CI (GitHub Actions) zielony.
- ✅ Manualny smoke test (golden path + 1 edge case).
- ✅ Code review subagentem `senior-reviewer`.
- ✅ Commit z tagiem `phase-X-complete`.

### Faza 0 — Bootstrap (1-2 dni)
| # | Zadanie |
|---|---|
| 0.1 | `composer create-project laravel/laravel .` (Laravel 12) |
| 0.2 | Konfiguracja Laravel Sail (PostgreSQL 16, Redis, Mailpit) |
| 0.3 | `.gitignore` + scan gitleaks (sprawdzenie czystego startu) |
| 0.4 | Breeze (Inertia + Vue 3 preset) + Tailwind 4 |
| 0.5 | Husky + lint-staged + commitlint + hooki pre-commit/pre-push |
| 0.6 | GitHub Actions: `ci.yml` (Pest, Pint, Larastan, npm build) |

### Faza 1 — Core Auth & Settings (2-3 dni)
| # | Zadanie |
|---|---|
| 1.1 | Rozszerzenie tabeli `users` (migration: gemini_api_key, daily_goal, theme) |
| 1.2 | Strona Settings/Profile (Inertia + Vue) |
| 1.3 | Komponent ApiKeyInput z szyfrowaniem (FormRequest, encrypt/decrypt) |
| 1.4 | Test endpointa zapisu/odczytu klucza (encrypted at rest) |

### Faza 2 — Generator Pytań (3-4 dni)
| # | Zadanie |
|---|---|
| 2.1 | `GeminiClient` + retry/backoff + obsługa błędów |
| 2.2 | `PromptBuilder` + `ResponseValidator` |
| 2.3 | Migracja `questions` + Model + Policy + Resource |
| 2.4 | `GenerateQuestionAction` + endpoint POST /api/questions/generate |
| 2.5 | Strona Questions/Index + Pages/Study (jedno pytanie) |
| 2.6 | Tagi (Spatie) — wybór technologii w UI |
| 2.7 | Cache odpowiedzi AI (`ai_response_cache`) |

### Faza 3 — System Powtórek (3-4 dni)
| # | Zadanie |
|---|---|
| 3.1 | Migracja `repetitions`, `review_logs` + Modele |
| 3.2 | `Sm2Engine` — pure logic z testami jednostkowymi (cel: 100% coverage) |
| 3.3 | `RecordReviewAction` + endpoint POST /api/repetitions/{id}/review |
| 3.4 | Event `QuestionReviewed` + Listener `UpdateUserStreak` |
| 3.5 | Daily session endpoint: GET /api/study/today (pytania z `next_review_at <= now`) |
| 3.6 | UI sesji: skróty klawiszowe, animacje, "Następne pytanie" |

### Faza 4 — Symulator Rozmowy (4-5 dni)
| # | Zadanie |
|---|---|
| 4.1 | Migracje `interview_sessions`, `interview_messages` + Modele |
| 4.2 | `StartInterviewAction`, `SendMessageAction` |
| 4.3 | Chat UI (Vue) z optimistic updates + Pinia store |
| 4.4 | Streaming odpowiedzi Gemini (SSE) — composable `useAiStream` |
| 4.5 | `GenerateReportAction` + `GenerateInterviewReportJob` (queue) |
| 4.6 | Strona Interview/Report (markdown rendering safe) |

### Faza 5 — TTS + Polish (2-3 dni)
| # | Zadanie |
|---|---|
| 5.1 | Composable `useSpeechSynthesis` (Web Speech API) |
| 5.2 | Dark mode toggle + system preference detection |
| 5.3 | PWA: manifest + service worker (offline cache) |
| 5.4 | Dashboard: streak, retention, heatmap |
| 5.5 | Onboarding 3-step wizard |

### Faza 6 — Hardening & CV-ready (2-3 dni)
| # | Zadanie |
|---|---|
| 6.1 | Dopisanie testów do 80% coverage (domena 90%) |
| 6.2 | README.md z screenshotami, diagramem architektury, instrukcją instalacji |
| 6.3 | Demo video (asciinema lub krótki GIF) |
| 6.4 | Final security audit (subagent `security-auditor`) |
| 6.5 | Deploy guide (np. na Railway / Fly.io / VPS) |

---

## 13. Stack Technologiczny

| Warstwa | Technologia | Wersja | Rola |
|---|---|---|---|
| Backend | Laravel | 12.x | Framework |
| Język | PHP | 8.3+ | Strict types, readonly props |
| Frontend SPA | Inertia.js | 2.x | SPA bez osobnego API |
| UI Framework | Vue | 3.4+ | Composition API |
| CSS | Tailwind CSS | 4.x | Utility-first |
| Komponenty | shadcn-vue / Headless UI | latest | Accessible primitives |
| Baza | PostgreSQL | 16 | Relacyjna |
| Cache/Queue | Redis | 7 | Performance |
| Auth | Laravel Breeze | latest | Inertia preset |
| Testy | Pest PHP | 3.x | DX |
| Static analysis | Larastan (PHPStan) | level 8 | Type safety |
| Formatter | Laravel Pint | latest | Auto-format |
| Tagi | spatie/laravel-tags | latest | Polimorficzne tagi |
| Markdown | league/commonmark | latest | Safe rendering |
| AI | Gemini API | gemini-2.0-flash (recommended) | LLM |
| Środowisko | Laravel Sail (Docker) | latest | Dev environment |
| CI/CD | GitHub Actions | — | Test & build |
| Git Hooks | Husky + lint-staged | latest | Pre-commit checks |
| Commit format | Conventional Commits + commitlint | latest | Spójność |
| Sekrety | gitleaks | latest | Skan w pre-commit |
| State (FE) | Pinia | 2.x | Tylko gdzie potrzebne |
| Ikony | Lucide Vue | latest | UI icons |

---

## 14. Świadomie pomijamy w MVP

Aby uniknąć *scope creep*:

- ❌ **Płatności / subskrypcje** — to BYOK, użytkownik nie płaci nam.
- ❌ **Multi-tenancy / Teams** — pojedynczy użytkownik.
- ❌ **Społeczność / sharing pytań** — prywatne pytania per user.
- ❌ **Mobile app native (React Native / Flutter)** — PWA wystarczy.
- ❌ **OAuth (Google, GitHub)** — Breeze email/password na start.
- ❌ **i18n (wielojęzyczność UI)** — tylko polski; angielski label w kodzie.
- ❌ **ElevenLabs TTS** — Web Speech API w MVP.
- ❌ **WebSockets (Reverb)** — Inertia + polling/SSE wystarczy.
- ❌ **Export do Anki/CSV** — *może* w Phase 7+.
- ❌ **Spaced repetition advanced (FSRS, Anki-style)** — klasyczne SM2 w MVP.

Te punkty mogą być **roadmapą po MVP**, ale nie blokują wypchnięcia projektu na GitHuba.

---

## Załącznik A: Nazwa projektu

**PrepMind** — finalna nazwa robocza. Alternatywy do rozważenia: *LarAnki*, *DevRecruiter*, *InterviewIQ*.

## Załącznik B: Inspiracje
- Anki (algorytm)
- Pramp / Interviewing.io (UX rozmowy)
- LeetCode (system trudności)
- Duolingo (streak, gamification)
