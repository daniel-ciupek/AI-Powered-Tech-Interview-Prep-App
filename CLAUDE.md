# CLAUDE.md — Operational Instructions for Claude Code

> **TL;DR dla Claude:** Najpierw przeczytaj `PROJECT.md` (pełna specyfikacja produktu).
> Ten plik definiuje **zasady pracy**: jak komunikować, jak commitować, jakie konwencje, jakie hooki, jakich subagentów używać.

---

## 0. Najważniejsze zasady (top of mind)

1. **Najpierw przeczytaj `PROJECT.md`** — bez tego nie ma decyzji architektonicznych.
2. **Po polsku z użytkownikiem, po angielsku w kodzie.**
3. **Pytaj o `git commit` i `git push` ZAWSZE** — jednorazowa zgoda = jeden commit.
4. **Etapy są sekwencyjne** — nie zaczynamy Fazy N+1 dopóki Faza N nie przejdzie DoD.
5. **Pre-commit i pre-push hooki są święte** — nigdy `--no-verify` bez wyraźnej prośby.
6. **Sekrety NIGDY w kodzie.** `.env` zawsze w `.gitignore`.

---

## 1. Stack & Wersje

| Warstwa | Technologia | Wersja |
|---|---|---|
| Backend | Laravel | 12.x |
| PHP | PHP | 8.3+ |
| DB | PostgreSQL | 16 |
| Cache/Queue | Redis | 7 |
| Frontend SPA | Inertia.js | 2.x |
| UI | Vue | 3.4+ |
| CSS | Tailwind | 4.x |
| Auth | Laravel Breeze (Inertia+Vue preset) | latest |
| Testy | Pest PHP | 3.x |
| Static analysis | Larastan | level 8 |
| Format | Laravel Pint | latest |
| Środowisko | Laravel Sail (Docker) | latest |

Pełen stack: patrz `PROJECT.md` sekcja 13.

---

## 2. Zasady Komunikacji z Użytkownikiem

### 2.1. Język
- **Polski** w rozmowie z użytkownikiem (komunikaty, pytania, podsumowania).
- **Angielski** w kodzie: nazwy klas, metod, zmiennych, commits, komentarzy w kodzie.
- **Polski** w dokumentacji projektowej (`PROJECT.md`, `README.md`).

### 2.2. Forma pytań
- W **90%** przypadków używaj `AskUserQuestion` z 2-4 opcjami zamiast pytań otwartych.
- Pierwsza opcja zwykle z dopiskiem `(Recommended)` jeśli masz silne zdanie.
- Pytaj **przed** podjęciem decyzji architektonicznej, nie po fakcie.

### 2.3. Krótko i konkretnie
- Bez wodolejstwa, bez powtarzania siebie.
- Jedno zdanie statusu przed pierwszym narzędziem.
- Końcowe podsumowanie: maks. 2 zdania (co zmienione + co dalej).

---

## 3. Struktura Katalogów Projektu (konwencja)

```
.
├── app/
│   ├── Actions/{Domain}/        # 1 klasa = 1 zadanie biznesowe (invokable)
│   ├── Services/{Domain}/       # długo-żyjące serwisy (klienci, silniki)
│   ├── Http/
│   │   ├── Controllers/         # cienkie, tylko delegacja do Action
│   │   ├── Requests/            # FormRequest — zawsze walidacja
│   │   └── Resources/           # API Resources — formatowanie odpowiedzi
│   ├── Jobs/                    # joby kolejkowe
│   ├── Models/                  # Eloquent + relacje + scopes (bez business logic)
│   ├── Policies/                # autoryzacja
│   ├── Enums/                   # PHP 8.1+ backed enums
│   ├── Events/ & Listeners/     # eventy domenowe
│   ├── Exceptions/              # custom exceptions
│   └── Support/                 # helpers, value objects
├── resources/js/
│   ├── Pages/{Domain}/          # Inertia pages
│   ├── Components/              # reużywalne UI (PascalCase.vue)
│   ├── Composables/             # logika Vue 3 (use*.ts)
│   ├── stores/                  # Pinia (camelCase.ts)
│   └── Layouts/                 # AuthenticatedLayout.vue, GuestLayout.vue
├── tests/
│   ├── Feature/                 # Pest, integration-style
│   └── Unit/                    # Pest, izolowane (np. Sm2Engine)
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── .claude/
│   ├── agents/                  # subagenci (markdown z frontmatter)
│   ├── commands/                # custom slash commands
│   └── settings.json            # config harness
├── .github/workflows/           # CI/CD
├── .husky/                      # git hooks
├── CLAUDE.md                    # ten plik
├── PROJECT.md                   # spec produktu
└── README.md                    # dla GitHuba (z screenshotami)
```

---

## 4. Konwencje Nazewnicze

| Element | Konwencja | Przykład |
|---|---|---|
| Klasy PHP | PascalCase | `GenerateQuestionAction` |
| Metody / zmienne PHP | camelCase | `recordReview()`, `$easeFactor` |
| Konstanty | UPPER_SNAKE_CASE | `MAX_RETRY_ATTEMPTS` |
| Tabele DB | snake_case plural | `interview_sessions` |
| Kolumny DB | snake_case | `next_review_at` |
| Migracje | `YYYY_MM_DD_HHMMSS_action_table.php` | `2026_05_21_120000_create_questions_table.php` |
| Enums | PascalCase, values lowercase | `enum Difficulty { case Junior = 'junior'; ... }` |
| Eventy | Past tense, PascalCase | `QuestionReviewed` |
| Joby | Imperative, PascalCase + `Job` | `GenerateInterviewReportJob` |
| Actions | Imperative + `Action` | `RecordReviewAction` |
| Services | PascalCase + role | `Sm2Engine`, `GeminiClient` |
| Pliki Vue | PascalCase | `QuestionCard.vue` |
| Composables | `use*` | `useSpeechSynthesis.ts` |
| Pinia stores | camelCase | `interviewSession.ts` |
| API endpoints | kebab-case, plural | `/api/interview-sessions` |
| Inertia routes | dot-notation | `questions.index`, `study.session` |

---

## 5. Workflow & Etapowanie

### 5.1. Struktura fazy
- Każda **Faza** ma podpunkty: X.1, X.2, X.3...
- Pracujemy **sekwencyjnie** — kończymy X.1 przed X.2.
- Każdy podpunkt: kod + test + Pint + Larastan **muszą być zielone**.
- **Po każdym podpunkcie:** commit + push (drobny, atomowy commit po uzyskaniu zgody).
- Rytm: `kod → testy zielone → Pint ✅ → commit → push → następny podpunkt`.
- Częste commity = czytelna historia w GitHubie (ważne dla rekruterów i CI).

### 5.2. Definition of Done (DoD) etapu
Faza N nie jest "skończona" dopóki:
- ✅ `vendor/bin/pest` — wszystkie testy zielone.
- ✅ `vendor/bin/pint --test` — bez warningów.
- ✅ `vendor/bin/phpstan analyse --level=8` — 0 błędów.
- ✅ `npm run build` — bez błędów.
- ✅ CI GitHub Actions zielony.
- ✅ Manualny smoke test (poprosić użytkownika o weryfikację).
- ✅ Code review subagentem `senior-reviewer` — komentarze rozwiązane.
- ✅ Tag git `phase-X-complete` po commitcie końcowym.

### 5.2.5. Procedura po każdym podpunkcie (X.1, X.2...)
1. Uruchom szybki check: `vendor/bin/pint --test` + `vendor/bin/pest`.
2. Zapytaj o zgodę na commit (`feat/fix/test(scope): X.N — krótki opis`).
3. Zapytaj o zgodę na push (`git push origin main`).
4. Sprawdź CI — jeśli czerwony, napraw ZANIM zaczniesz kolejny podpunkt.

### 5.3. Procedura na koniec etapu
1. Uruchom `composer test:all` (custom script — Pint + Larastan + Pest).
2. Poproś usera o smoke test (lista konkretnych rzeczy do kliknięcia).
3. Wezwij `senior-reviewer` subagenta (Agent tool).
4. Rozwiąż uwagi.
5. Zapytaj o zgodę na finalny commit.
6. Tag: `git tag phase-X-complete`.

---

## 6. Reguły Git (KRYTYCZNE)

### 6.1. Autoryzacja
- **ZAWSZE pytaj przed `git commit`** — każdy commit, po podpunkcie i na koniec fazy.
- **ZAWSZE pytaj przed `git push`** — push następuje od razu po każdym commicie (nie czekamy do końca fazy).
- Jednorazowa zgoda na commit = jeden konkretny commit; na push = jeden konkretny push.
- **NIGDY** `git push --force` na `main` bez wyraźnej, świadomej prośby.
- **NIGDY** `git commit --no-verify` ani `git push --no-verify` (chyba że user wprost prosi z uzasadnieniem — wtedy zapisz feedback memory).
- **NIGDY** `git reset --hard`, `git clean -fd`, `git branch -D` bez potwierdzenia.

### 6.2. Conventional Commits
Format: `type(scope): short description`
- `feat(repetition): implement SM2 algorithm`
- `fix(gemini): handle invalid JSON response`
- `chore(deps): bump laravel/pint`
- `test(sm2): cover edge case for q=0`
- `docs(readme): add architecture diagram`
- `refactor(actions): extract PromptBuilder from GenerateQuestionAction`

Dozwolone typy: `feat`, `fix`, `chore`, `test`, `docs`, `refactor`, `perf`, `style`, `build`, `ci`.

### 6.3. Branche
- `main` — stabilna gałąź (chroniona).
- `feature/X.Y-short-description` — pojedynczy podpunkt fazy.
- `fix/short-description` — bugfix poza fazą.
- `chore/short-description` — sprzątanie, deps.

### 6.4. Pull Request workflow
- PR z feature branch do `main`.
- Wymagany zielony CI.
- Self-review przed mergem.
- Squash & merge (jeden commit per feature na `main`).

---

## 7. Reguły Bezpieczeństwa

### 7.1. Sekrety
- **`.env` zawsze w `.gitignore`.** Tylko `.env.example` w repo (bez wartości).
- **NIGDY** nie wpisuj kluczy, haseł, tokenów do kodu, komentarzy, testów.
- Klucz Gemini użytkownika: `Crypt::encryptString()` przy zapisie, deszyfrowanie tylko w pamięci, **nigdy** w logach.
- Przed każdym commitem **gitleaks** w pre-commit hook.
- Jeśli sekret kiedyś trafił do repo: rotacja (regeneracja po stronie usługi) + `git filter-repo` + force-push (po zgodzie usera).

### 7.2. Walidacja
- **Każdy** input z UI/API → FormRequest.
- Eloquent: zawsze `$fillable` (whitelist), **nigdy** `$guarded = []`.
- AI output: parsowany przez safe markdown parser (`league/commonmark` w trybie strict), **nigdy** `v-html` z surowym AI textem.

### 7.3. Headers & CSRF
- Sanctum SPA mode dla cookie auth.
- CSRF token na każdym mutacyjnym żądaniu (Inertia auto-obsługuje).
- CSP, X-Frame-Options, X-Content-Type-Options w middleware.

### 7.4. Dependencies
- Przed każdym mergem do `main`: `composer audit` + `npm audit`.
- Dependabot na GitHubie auto-PR dla minor/patch.
- Major updates: ręczna review + test full suite.

---

## 8. Reguły Jakości Kodu

### 8.1. PHP
- `declare(strict_types=1);` na każdym pliku.
- Typed properties, typed return values **wszędzie**.
- Bez `mixed` jeśli da się uniknąć.
- Bez `magic strings` — używaj enums i constants.
- Bez fat controllerów: kontroler ma 3-7 linii, deleguje do Action.
- Bez business logic w Modelach (poza scope/accessor/mutator).
- Bez inline SQL — Eloquent / Query Builder.

### 8.2. Vue/TS
- Composition API (`<script setup lang="ts">`).
- TypeScript wszędzie (`.ts`, nie `.js`).
- Props typed (defineProps z interface).
- Emits typed (defineEmits).
- Składaki (composables) zamiast mixins.

### 8.3. Testy
- **Pest 3** (`tests/Feature/`, `tests/Unit/`).
- **Cel pokrycia:** domena ≥ 90% (Actions, Services, Engines), ogólne ≥ 80%.
- Testy jednostkowe dla `Sm2Engine` — pokryj wszystkie edge cases (q=0..5, repetitions 0..2, EF min).
- Mocki Gemini API: `Http::fake()` zamiast prawdziwych wywołań.
- Każdy bug fix = test który go reprodukuje.

### 8.4. Code review checklist (samokontrola przed commitem)
- [ ] Pint przeszedł
- [ ] Larastan level 8 zielony
- [ ] Pest zielony
- [ ] Brak dead code (nieużywane importy, zakomentowane fragmenty)
- [ ] Brak TODO bez issue w GitHubie
- [ ] Brak magic numbers (są w konstantach/configu)
- [ ] Komunikaty błędów po polsku (UI) / angielsku (logi)
- [ ] N+1 sprawdzone (eager loading gdzie potrzeba)

---

## 9. Git Hooks (Husky + lint-staged)

> Instalacja: `npm install -D husky lint-staged @commitlint/cli @commitlint/config-conventional`
> Konfiguracja w `package.json` i `.husky/`.

### 9.1. pre-commit
Kolejność wykonania:
1. **Pint** na zmienionych PHP plikach (auto-fix).
2. **Larastan** na zmienionych PHP plikach (tylko diff).
3. **gitleaks** scan całego diff (wykrywanie sekretów).
4. **Sprawdzenie `.gitignore`**: czy nowe pliki sensitive nie próbują przejść.

Jeśli którykolwiek krok faila → commit zablokowany, użytkownik dostaje czytelny komunikat.

### 9.2. commit-msg
- **commitlint** sprawdza Conventional Commits.
- Niepoprawny format → commit zablokowany.

### 9.3. pre-push
- **Pełny Pest suite** (`vendor/bin/pest --parallel`).
- Jeśli choć jeden test faila → push zablokowany.

### 9.4. Skrypty composer/npm (do `composer.json`)
```json
"scripts": {
    "lint": "vendor/bin/pint",
    "lint:check": "vendor/bin/pint --test",
    "analyse": "vendor/bin/phpstan analyse --level=8",
    "test": "vendor/bin/pest --parallel",
    "test:coverage": "vendor/bin/pest --coverage --min=80",
    "test:all": ["@lint:check", "@analyse", "@test"]
}
```

---

## 10. CI/CD (GitHub Actions)

### 10.1. `.github/workflows/ci.yml`
Trigger: `push` do każdej gałęzi, `pull_request` do `main`.

Jobs:
- **`backend`**:
  - matrix: PHP 8.3
  - steps: checkout → setup PHP → composer install → Pint check → Larastan → Pest (with coverage) → upload coverage
- **`frontend`**:
  - steps: checkout → setup Node 22 → npm ci → npm run build → npm run lint (opcjonalnie eslint)
- **`security`**:
  - steps: composer audit, npm audit, gitleaks scan

### 10.2. `.github/workflows/docker-build.yml`
- Buduje obraz z Sail Dockerfile.
- Smoke test: kontener startuje, `/health` endpoint zwraca 200.

### 10.3. (Opcjonalnie) `.github/workflows/codeql.yml`
- Skan bezpieczeństwa CodeQL na pushu do `main`.

---

## 11. Subagenci

W `.claude/agents/` definiujemy 7 wyspecjalizowanych subagentów. Każdy ma frontmatter `name`, `description`, `tools`, `model` (haiku/sonnet/opus zależnie od trudności zadania).

| Subagent | Plik | Kiedy używać |
|---|---|---|
| `laravel-architect` | `.claude/agents/laravel-architect.md` | Projektowanie modułów backendu, migracji, decyzje architektoniczne |
| `ui-ux-designer` | `.claude/agents/ui-ux-designer.md` | Projektowanie komponentów Vue, Tailwind, animacje, a11y |
| `qa-engineer` | `.claude/agents/qa-engineer.md` | Pisanie testów Pest, mocki, coverage |
| `security-auditor` | `.claude/agents/security-auditor.md` | Audyty bezpieczeństwa, OWASP, sekrety |
| `prompt-engineer` | `.claude/agents/prompt-engineer.md` | Projektowanie i iteracja System Promptów dla Gemini |
| `db-modeler` | `.claude/agents/db-modeler.md` | Schematy DB, indeksy, optymalizacje |
| `senior-reviewer` | `.claude/agents/senior-reviewer.md` | Code review na końcu fazy ("udaje" rekrutera) |

---

## 12. Custom Slash Commands

W `.claude/commands/`:

| Komenda | Plik | Cel |
|---|---|---|
| `/audit-stage` | `.claude/commands/audit-stage.md` | Sprawdza DoD bieżącej fazy |
| `/check-secrets` | `.claude/commands/check-secrets.md` | Skan gitleaks + ręczna inspekcja |
| `/generate-test` | `.claude/commands/generate-test.md` | Szkielet testu Pest dla wskazanej klasy |
| `/explain-sm2` | `.claude/commands/explain-sm2.md` | Tłumaczy stan algorytmu SM2 dla danego pytania |
| `/cost-report` | `.claude/commands/cost-report.md` | Szacuje zużycie Gemini API |

---

## 13. Co zawsze robię PRZED zmianą

1. **Czytam `PROJECT.md`** jeśli to pierwsza sesja lub minęło dużo czasu.
2. **Czytam plik(i) docelowe w pełni** (nie excerpts) — kontekst jest święty.
3. **Sprawdzam, czy nie istnieje już podobne rozwiązanie** (grep, ls) — DRY.
4. **Pytam o ambiguities** zamiast zgadywać (AskUserQuestion).
5. **Plan przed kodem** dla zadań > 30 minut pracy.

---

## 14. Co zawsze robię PO zmianie

1. Uruchamiam `composer test:all` jeśli dotknąłem PHP.
2. Uruchamiam `npm run build` jeśli dotknąłem JS/Vue.
3. **Raportuję krótko**: co zmienione (1-2 zdania), co dalej (1 zdanie).
4. Jeśli niedokończone — wyraźnie zaznaczam "to-do" zamiast udawać że gotowe.

---

## 15. Anti-patterns (czego NIE robić)

- ❌ Fat controllers (>15 linii).
- ❌ Business logic w Modelach.
- ❌ Inline SQL.
- ❌ `--no-verify` na commit/push.
- ❌ Commity typu "wip", "fix stuff", "asdf".
- ❌ Generowanie kodu którego nie rozumiem (zwłaszcza JSON Schemy, regexpów, kryptografii).
- ❌ Mockowanie czegoś, co nie istnieje — najpierw stwórz interfejs.
- ❌ Premature optimization — najpierw działa, potem szybko.
- ❌ Premature abstraction — DRY tylko gdy 3+ razy to samo.
- ❌ Komentarze opisujące CO robi kod (nazwy mają to robić); komentarze tylko gdy DLACZEGO jest nieoczywiste.
- ❌ Nadmiarowe logi (`Log::info('test')` w produkcji).
- ❌ Try/catch który tylko `return null` — błędy mają być widoczne lub obsłużone celowo.
- ❌ `git push --force` bez pytania.
- ❌ Pomijanie testów ("zrobię później").

---

## 16. Skróty czas-pracy (quick reference)

| Zadanie | Komenda |
|---|---|
| Start Sail | `./vendor/bin/sail up -d` |
| Stop Sail | `./vendor/bin/sail down` |
| Migracje | `./vendor/bin/sail artisan migrate` |
| Seed | `./vendor/bin/sail artisan db:seed` |
| Test | `./vendor/bin/sail pest` |
| Test z coverage | `./vendor/bin/sail composer test:coverage` |
| Pint | `./vendor/bin/sail composer lint` |
| Larastan | `./vendor/bin/sail composer analyse` |
| Pełen check | `./vendor/bin/sail composer test:all` |
| Frontend build | `./vendor/bin/sail npm run build` |
| Frontend dev | `./vendor/bin/sail npm run dev` |
| Queue worker | `./vendor/bin/sail artisan queue:work` |
| Tinker | `./vendor/bin/sail artisan tinker` |

---

## 17. Decyzje już zatwierdzone (nie pytaj ponownie)

| Pytanie | Decyzja | Data |
|---|---|---|
| Frontend stack | Inertia.js + Vue 3 + Tailwind 4 | 2026-05-21 |
| Auth | Laravel Breeze (Inertia+Vue preset) | 2026-05-21 |
| Testy | Pest PHP 3 | 2026-05-21 |
| Baza | PostgreSQL 16 | 2026-05-21 |
| Środowisko | Laravel Sail | 2026-05-21 |
| Język komunikacji | PL z userem, EN w kodzie | 2026-05-21 |
| Format commitów | Conventional Commits | 2026-05-21 |
| Hooki | Husky + lint-staged + commitlint + gitleaks | 2026-05-21 |
