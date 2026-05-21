---
description: Comprehensive secret leak scan — gitleaks + grep patterns + git history check + .env safety verification.
---

Wykonuję pełny audyt sekretów w repozytorium PrepMind.

## Kroki

1. **gitleaks scan bieżącego stanu:**
   ```bash
   gitleaks detect --source . --no-banner --redact -v
   ```

2. **gitleaks scan historii gita (CAŁA historia, nie tylko HEAD):**
   ```bash
   gitleaks detect --source . --no-banner --redact -v --log-opts="--all"
   ```

3. **Sprawdź czy `.env` jest w `.gitignore`:**
   ```bash
   grep -E "^\.env$|^\.env\..*$" .gitignore
   ```

4. **Sprawdź czy `.env` NIGDY nie był w historii:**
   ```bash
   git log --all --full-history -- .env
   ```
   Wynik MUSI być pusty. Jeśli `.env` kiedyś trafił do repo → wymagana rotacja kluczy + `git filter-repo`.

5. **Sprawdź `.env.example` — czy nie ma w nim prawdziwych wartości:**
   ```bash
   cat .env.example
   ```
   Wszystkie wartości MUSZĄ być placeholderami (`your-key-here`, puste, `secret`).

6. **Grep pod kątem typowych wzorców sekretów w kodzie:**
   ```bash
   grep -rnE "(api[_-]?key|secret|password|token|bearer)\s*[=:]\s*['\"][A-Za-z0-9+/=_-]{16,}" \
     --include="*.php" --include="*.ts" --include="*.vue" --include="*.js" \
     --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=.git .
   ```

7. **Grep pod kątem AWS keys, Google API keys, Stripe keys:**
   ```bash
   grep -rnE "AKIA[0-9A-Z]{16}|AIza[0-9A-Za-z_-]{35}|sk_(test|live)_[0-9a-zA-Z]{24,}" \
     --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=.git .
   ```

8. **Sprawdź czy w logach nie ma zaszyfrowanych kluczy:**
   ```bash
   grep -rE "Log::[a-z]+\(.*api[_-]?key" --include="*.php" app/
   ```
   Wynik MUSI być pusty.

9. **Sprawdź uprawnienia plików konfiguracyjnych:**
   ```bash
   ls -la .env* 2>/dev/null || echo "No .env files (good for repo state)"
   ```

## Format raportu

```markdown
# Secret Leak Audit — [DATA]

## Status ogólny
**🟢 CZYSTO** lub **🔴 ZNALEZIONO PROBLEMY**

## Wyniki kroków
1. gitleaks (bieżący): ✅/❌ — [N findings]
2. gitleaks (historia): ✅/❌ — [N findings]
3. `.env` w `.gitignore`: ✅/❌
4. `.env` w historii: ✅ NIE / ❌ TAK (krytyczne!)
5. `.env.example` czysty: ✅/❌
6. Wzorce sekretów w kodzie: ✅/❌ — [lokalizacje]
7. AWS/Google/Stripe patterns: ✅/❌
8. Logi z kluczami: ✅/❌

## Znalezione problemy
[lista z lokalizacjami]

## Akcje wymagane
1. [konkretna akcja]
2. ...
```

## Jeśli znaleziono sekret w historii gita
**ZATRZYMAJ SIĘ.** Nie próbuj usunąć samodzielnie. Poinformuj użytkownika i zaproponuj:
1. **Natychmiast** zrotować klucz (regeneracja po stronie usługi).
2. Użyć `git filter-repo` aby usunąć sekret z całej historii.
3. Force-push (po zgodzie usera) — `git push --force` na wszystkie branche.
4. Powiadomić wszystkich kontrybutorów aby zrobili `git clone` na nowo.
