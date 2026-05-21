---
description: Generates Pest 3 test skeleton for a specified class. Auto-detects whether to create Feature or Unit test based on class type.
argument-hint: "<ClassName> [test-type:feature|unit]"
---

Generuję szkielet testu Pest 3 dla klasy: $ARGUMENTS

## Kroki

1. **Parsuj argument** — wyodrębnij nazwę klasy (np. `Sm2Engine`, `GenerateQuestionAction`, `QuestionController`).

2. **Znajdź docelową klasę** w `app/`:
   ```bash
   find app/ -name "${CLASS_NAME}.php" -type f
   ```

3. **Auto-decyzja typ testu:**
   - `app/Services/**` lub `app/Support/**` → **Unit test** w `tests/Unit/`
   - `app/Actions/**` → **Feature test** w `tests/Feature/Actions/`
   - `app/Http/Controllers/**` → **Feature test** w `tests/Feature/Http/`
   - `app/Jobs/**`, `app/Listeners/**`, `app/Policies/**` → **Feature test**
   - `app/Models/**` (jeśli ma scopes/accessors) → **Unit test**
   - Inne → zapytaj użytkownika

4. **Przeczytaj klasę** aby zrozumieć publiczne API.

5. **Wygeneruj szkielet testu** — patrz wzory poniżej.

6. **Uruchom test** aby potwierdzić że szkielet kompiluje się:
   ```bash
   ./vendor/bin/sail pest tests/[Path]/[ClassName]Test.php
   ```

## Wzory szkieletów

### Unit Test (pure logic, np. Sm2Engine)
```php
<?php
declare(strict_types=1);

use App\Services\Repetition\Sm2Engine;

beforeEach(function () {
    $this->engine = new Sm2Engine();
});

describe('Sm2Engine', function () {
    describe('happy path', function () {
        it('returns correct result for first review with q=4', function () {
            $result = $this->engine->calculate(
                easeFactor: 2.5,
                intervalDays: 0,
                repetitionsCount: 0,
                quality: 4,
            );

            expect($result->intervalDays)->toBe(1);
            expect($result->repetitionsCount)->toBe(1);
        });
    });

    describe('edge cases', function () {
        it('resets when quality < 3', function () {
            // ...
        })->todo();

        it('keeps EF at minimum 1.30', function () {
            // ...
        })->todo();
    });
});
```

### Feature Test (Action)
```php
<?php
declare(strict_types=1);

use App\Models\User;
use App\Models\Question;
use App\Actions\Questions\GenerateQuestionAction;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            // mocked Gemini response structure
        ]),
    ]);
});

it('persists a question after successful Gemini call', function () {
    $user = User::factory()->create();

    $action = app(GenerateQuestionAction::class);
    $question = $action->execute($user, ['laravel'], Difficulty::Mid);

    expect($question)->toBeInstanceOf(Question::class);
    expect(Question::count())->toBe(1);
});

it('handles Gemini API errors gracefully', function () {
    // ...
})->todo();
```

### Feature Test (Controller / HTTP)
```php
<?php
declare(strict_types=1);

use App\Models\User;

it('requires authentication', function () {
    $this->postJson('/api/questions/generate')->assertUnauthorized();
});

it('validates required tags', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/questions/generate', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tags']);
});

it('generates a question with valid input', function () {
    // ...
})->todo();
```

### Feature Test (Policy)
```php
<?php
declare(strict_types=1);

use App\Models\User;
use App\Models\Question;
use App\Policies\QuestionPolicy;

it('allows owner to view their question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();

    $policy = new QuestionPolicy();

    expect($policy->view($user, $question))->toBeTrue();
});

it('denies non-owner', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $question = Question::factory()->for($owner)->create();

    expect((new QuestionPolicy())->view($stranger, $question))->toBeFalse();
});
```

## Output

Po wygenerowaniu:
1. **Pokaż ścieżkę** do nowego pliku testowego.
2. **Pokaż listę** zadań `->todo()` które wymagają implementacji.
3. **Uruchom** test (powinien przejść — wszystkie testy są albo trywialne albo TODO).
4. **Zasugeruj** następne kroki (które TODO są najważniejsze).

## Anti-patterns

- ❌ Nie generuj testów hitujących prawdziwe API — zawsze `Http::fake()`.
- ❌ Nie generuj testów polegających na konkretnym ID (1, 2, 3) — używaj factory.
- ❌ Nie generuj testów zakładających stan z poprzednich testów — `RefreshDatabase`.
