---
description: Audits the current phase against Definition of Done — runs tests, linter, static analysis, security scan, and invokes senior-reviewer subagent.
argument-hint: "[phase-number]"
---

Audytuję obecny etap projektu PrepMind. Sprawdzam Definition of Done z CLAUDE.md sekcja 5.2.

**Numer fazy (jeśli podany przez użytkownika):** $ARGUMENTS

## Kroki

1. **Identyfikacja bieżącej fazy** — sprawdź ostatni tag git (`git tag -l 'phase-*'`) i bieżący stan plików.

2. **Uruchom pełen pakiet jakości:**
   ```bash
   ./vendor/bin/sail composer lint:check
   ./vendor/bin/sail composer analyse
   ./vendor/bin/sail composer test:coverage
   ./vendor/bin/sail npm run build
   ```

3. **Sprawdź czystość gita:**
   ```bash
   git status
   git log $(git describe --tags --abbrev=0 2>/dev/null || echo HEAD~10)..HEAD --oneline
   ```

4. **Sprawdź sekrety:**
   ```bash
   gitleaks detect --source . --no-banner --redact
   ```

5. **Sprawdź CI:** Status ostatniego workflow run przez `gh run list --limit 5`.

6. **Wezwij `security-auditor` subagenta** dla pełnego audytu OWASP.

7. **Wezwij `senior-reviewer` subagenta** dla finalnego code review.

## Format raportu

```markdown
# Audyt Fazy [N]

## Wyniki automatyczne
| Check | Status |
|---|---|
| Pint | ✅/❌ |
| Larastan level 8 | ✅/❌ |
| Pest (coverage) | ✅/❌ (XX%) |
| Frontend build | ✅/❌ |
| gitleaks | ✅/❌ |
| CI ostatni run | ✅/❌ |

## Audyt bezpieczeństwa (security-auditor)
[skrót, pełny raport w odpowiedzi subagenta]

## Code review (senior-reviewer)
[werdykt + must-fix items]

## Definition of Done
- [ ] Wszystkie testy zielone
- [ ] Pint OK
- [ ] Larastan level 8 OK
- [ ] CI zielony
- [ ] Smoke test wykonany przez użytkownika
- [ ] Code review zakończone
- [ ] Brak issues `must-fix`

## Werdykt
**GOTOWE DO TAGOWANIA** lub **WYMAGA POPRAWEK** + lista zadań.
```

Po zakończeniu audytu — JEŚLI wszystko zielone — **zapytaj użytkownika** o zgodę na:
1. Finalny commit (Conventional Commit `chore: complete phase X`).
2. Tag `phase-X-complete`.
3. Push (jeśli user chce).
