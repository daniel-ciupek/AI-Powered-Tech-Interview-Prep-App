---
description: Generates Gemini API cost report — per-user token usage, monthly trends, cache hit rate, and projected costs.
argument-hint: "[user_id|--all] [--month=YYYY-MM]"
---

Generuję raport kosztów Gemini API dla projektu PrepMind.

**Argumenty:** $ARGUMENTS

## Kroki

1. **Parsuj argumenty:**
   - Brak / `--all` → raport globalny (wszyscy użytkownicy).
   - `<user_id>` → raport per użytkownik.
   - `--month=YYYY-MM` → konkretny miesiąc (domyślnie: bieżący).

2. **Pobierz dane z bazy:**
   ```bash
   ./vendor/bin/sail artisan tinker --execute="
   \$query = App\Models\InterviewSession::query()
       ->selectRaw('user_id, SUM(tokens_used_total) as total_tokens, COUNT(*) as sessions')
       ->whereMonth('created_at', date('m'))
       ->whereYear('created_at', date('Y'))
       ->groupBy('user_id');
   echo \$query->get()->toJson(JSON_PRETTY_PRINT);
   "
   ```

3. **Sprawdź cache hit rate** (oszczędność tokenów):
   ```bash
   ./vendor/bin/sail artisan tinker --execute="
   \$cached = DB::table('ai_response_cache')->count();
   \$totalQuestions = App\Models\Question::where('source', 'ai_generated')->count();
   \$hitRate = \$totalQuestions > 0 ? round((\$cached / \$totalQuestions) * 100, 2) : 0;
   echo \"Cache entries: \$cached, AI questions: \$totalQuestions, hit rate: {\$hitRate}%\" . PHP_EOL;
   "
   ```

4. **Wylicz koszt** używając `App\Services\Gemini\CostCalculator`:
   - Gemini 2.0 Flash pricing (przybliżone, sprawdź aktualne):
     - Input: $0.075 / 1M tokens
     - Output: $0.30 / 1M tokens
   - Gemini 2.0 Pro pricing (jeśli używane):
     - Input: $1.25 / 1M tokens
     - Output: $5.00 / 1M tokens

5. **Identyfikuj heavy users** — top 5 użytkowników po zużyciu.

6. **Trend miesięczny** — porównanie z poprzednimi miesiącami.

## Format raportu

```markdown
# Gemini API Cost Report

**Okres:** [YYYY-MM]
**Scope:** [Global / User #N]

## Podsumowanie
- **Total tokens (in):** XXX,XXX
- **Total tokens (out):** XXX,XXX
- **Total cost (USD):** $X.XX
- **Liczba sesji rozmowy:** XX
- **Liczba wygenerowanych pytań:** XX
- **Cache hit rate:** XX% (oszczędność: ~$X.XX)

## Per User Top 5
| User | Tokens | Cost | Sessions |
|---|---|---|---|
| #1 (john@...) | 50,000 | $0.05 | 10 |
| ... | ... | ... | ... |

## Trend miesięczny (ostatnie 6 miesięcy)
```
[Wykres ASCII / lista]
2026-05: $1.50 (████░░░░░░░░ 30%)
2026-04: $0.80
2026-03: $0.40
...
```

## Cache Efficiency
- Wpisów w cache: XXX
- Cache TTL: 7 dni
- Najczęściej cachowane kombinacje tagów:
  1. `[laravel, mysql]` (mid) — X hits
  2. `[docker]` (junior) — X hits
  3. ...

## Rekomendacje
- [Jeśli cache hit < 30%] Cache hit rate jest niski. Rozważ wydłużenie TTL lub
  agresywniejszy klucz cache.
- [Jeśli koszt rośnie] Heavy users dominują koszty. Rozważ soft daily limit.
- [Jeśli sesje krótkie] Symulacje rozmowy są przerywane wcześnie — zbadaj UX.

## Projekcja
Przy obecnym tempie zużycia, miesięczny koszt projektowany na: $X.XX
(scaling factor: dni_pozostałe_w_miesiącu / dni_które_minęły)
```

## Notatka

Pamiętaj że Gemini pricing zmienia się — sprawdź aktualne stawki na
https://ai.google.dev/pricing przed publikacją raportu.

Wartości pricing przechowywane są w `config/gemini.php`:
```php
'pricing' => [
    'gemini-2.0-flash' => [
        'input_per_1m' => 0.075,
        'output_per_1m' => 0.30,
    ],
    'gemini-2.0-pro' => [
        'input_per_1m' => 1.25,
        'output_per_1m' => 5.00,
    ],
],
```
