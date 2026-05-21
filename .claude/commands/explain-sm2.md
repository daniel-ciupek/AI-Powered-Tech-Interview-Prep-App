---
description: Explains the SM2 algorithm state for a specific repetition record — shows current values, simulates next review for q=0..5, predicts next intervals.
argument-hint: "<repetition_id>"
---

Tłumaczę stan algorytmu SuperMemo-2 dla rekordu powtórek o ID: $ARGUMENTS

## Kroki

1. **Pobierz aktualny stan** rekordu z bazy:
   ```bash
   ./vendor/bin/sail artisan tinker --execute="
   \$r = App\Models\Repetition::with(['question', 'user'])->find($ARGUMENTS);
   if (!\$r) { echo 'Not found'; exit; }
   echo json_encode([
       'id' => \$r->id,
       'question' => \$r->question->content,
       'user' => \$r->user->name,
       'ease_factor' => \$r->ease_factor,
       'interval_days' => \$r->interval_days,
       'repetitions_count' => \$r->repetitions_count,
       'quality_last' => \$r->quality_last,
       'next_review_at' => \$r->next_review_at?->format('Y-m-d H:i'),
       'last_reviewed_at' => \$r->last_reviewed_at?->format('Y-m-d H:i'),
   ], JSON_PRETTY_PRINT);
   "
   ```

2. **Pobierz historię** powtórek (review_logs) dla tego rekordu:
   ```bash
   ./vendor/bin/sail artisan tinker --execute="
   App\Models\ReviewLog::where('repetition_id', $ARGUMENTS)
       ->orderByDesc('created_at')
       ->limit(10)
       ->get(['quality', 'ease_before', 'ease_after', 'interval_before', 'interval_after', 'created_at'])
       ->each(fn(\$l) => print(\$l->toJson() . PHP_EOL));
   "
   ```

3. **Symulacja:** wylicz co stałoby się po każdej możliwej ocenie (q = 0, 2, 3, 4, 5) używając `Sm2Engine`:
   ```bash
   ./vendor/bin/sail artisan tinker --execute="
   \$r = App\Models\Repetition::find($ARGUMENTS);
   \$engine = app(App\Services\Repetition\Sm2Engine::class);
   foreach ([0, 2, 3, 4, 5] as \$q) {
       \$result = \$engine->calculate(
           easeFactor: (float) \$r->ease_factor,
           intervalDays: \$r->interval_days,
           repetitionsCount: \$r->repetitions_count,
           quality: \$q,
       );
       echo \"q=\$q → interval=\".\$result->intervalDays.\"d, EF=\".\$result->easeFactor.\", reps=\".\$result->repetitionsCount.PHP_EOL;
   }
   "
   ```

4. **Wyjaśnij** każdy wynik po polsku, odnosząc się do wzoru z PROJECT.md sekcja 4.

## Format raportu

```markdown
# Stan algorytmu SM2 — Rekord #${ARGUMENTS}

## Aktualny stan
- **Pytanie:** "..."
- **Użytkownik:** ...
- **Ease Factor (EF):** 2.50
- **Interwał (dni):** 6
- **Liczba poprawnych powtórek z rzędu:** 2
- **Ostatnia ocena:** 4 (zielony — "znam")
- **Następna powtórka:** 2026-05-27 (za 6 dni)

## Historia (10 ostatnich)
| Data | Ocena | EF przed → po | Interwał przed → po |
|---|---|---|---|
| 2026-05-21 | 4 | 2.50 → 2.50 | 1 → 6 |
| 2026-05-20 | 4 | 2.50 → 2.50 | 0 → 1 |
| ... | ... | ... | ... |

## Symulacja: co jeśli teraz ocenisz...

| q (ocena) | Mapowanie | Nowy interwał | Nowy EF | Powtórki |
|---|---|---|---|---|
| 0 | (kompletnie zapomniał) | 1 dzień | 2.50 → 1.70 | 0 (reset) |
| 2 | **Czerwony** ("nie znam") | 1 dzień | 2.50 → 2.18 | 0 (reset) |
| 3 | (z trudem) | 15 dni | 2.50 → 2.36 | 3 |
| 4 | **Zielony** ("znam") | 15 dni | 2.50 → 2.50 | 3 |
| 5 | (idealnie) | 15 dni | 2.50 → 2.60 | 3 |

## Wyjaśnienie wzoru

Algorytm SM2 zachowuje się dziś tak:
- **q < 3:** reset progresji — interwał wraca do 1 dnia, repetitions_count = 0.
- **q ≥ 3:** interwał = `round(prev_interval × EF)` (dla repetitions ≥ 3),
  EF aktualizuje się: `EF_new = EF + (0.1 - (5-q)(0.08 + (5-q)·0.02))`.

Dla tego rekordu (`repetitions_count=2`), następny interwał to 6 dni (specjalna reguła dla 2. powtórki).
Trzecia powtórka będzie liczona jako `6 × EF = 6 × 2.5 = 15 dni`.

## Sugestie debugowania (jeśli stan wydaje się błędny)

- Sprawdź `review_logs` — czy każda zmiana jest tam zapisana?
- Sprawdź czy `RecordReviewAction` używa `Sm2Engine` (a nie inline math)?
- Sprawdź czy `Sm2Engine` ma testy pokrywające ten przypadek?
```
