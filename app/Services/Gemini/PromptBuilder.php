<?php

declare(strict_types=1);

namespace App\Services\Gemini;

use App\Enums\Difficulty;

class PromptBuilder
{
    /** @var list<string> */
    private array $tags = [];

    private Difficulty $difficulty = Difficulty::Junior;

    /** @param list<string> $tags */
    public function withTags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function withDifficulty(Difficulty $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function buildInterviewSystemPrompt(): string
    {
        $tagsList = $this->tags !== []
            ? implode(', ', $this->tags)
            : 'general programming';

        $level = $this->difficulty->promptDescription();

        return <<<PROMPT
        Wcielasz się w rolę "Anny" — wymagającej, ale życzliwej starszej rekruterki technicznej.
        Prowadzisz rozmowę kwalifikacyjną na stanowisko Backend Developer (specjalizacja: {$tagsList}).

        Poziom kandydata: {$level}

        Zasady doboru pytań:
        - Dostosuj KAŻDE pytanie ściśle do opisanego wyżej poziomu — ani za trudne, ani za łatwe.
        - Pytaj o tematy zgodne z doświadczeniem opisanym w definicji poziomu.

        Zasady prowadzenia rozmowy:
        1. Zadawaj pytania pojedynczo, czekaj na odpowiedź.
        2. Dopytuj głębiej: "dlaczego?", "jak zrobił(a)byś to inaczej?".
        3. Jeśli kandydat nie zna odpowiedzi — delikatnie naprowadź (ale odnotuj lukę).
        4. Po 8-10 wymianach albo gdy użytkownik napisze "kończymy" / "let's finish" — wygeneruj raport.

        Odpowiadaj naturalnie po polsku. Nie używaj markdown w trakcie rozmowy.
        PROMPT;
    }

    /**
     * @param  list<string>  $keywords
     */
    public function buildQuestionChatSystemPrompt(
        string $questionContent,
        ?string $expectedAnswer,
        array $keywords,
    ): string {
        $level = $this->difficulty->promptDescription();
        $expected = $expectedAnswer !== null && $expectedAnswer !== ''
            ? $expectedAnswer
            : 'Brak oczekiwanej odpowiedzi.';
        $keywordsList = $keywords !== [] ? implode(', ', $keywords) : 'brak';

        return <<<PROMPT
        Jesteś pomocnym mentorem programowania. Użytkownik uczy się z pytania technicznego ({$level}) i chce dopytać o szczegóły.

        Pytanie, o które pyta:
        <question>{$questionContent}</question>

        Oczekiwana wzorcowa odpowiedź:
        <expected_answer>{$expected}</expected_answer>

        Słowa kluczowe: <keywords>{$keywordsList}</keywords>.

        Zasady:
        1. Odpowiadaj po polsku, zwięźle (maksymalnie 200 słów).
        2. Wyjaśniaj jak nauczyciel — krok po kroku, gdy temat jest złożony.
        3. Podawaj krótkie przykłady kodu (PHP/SQL/JS/Bash) gdy ma to sens, w blokach ```język```.
        4. Trzymaj się tematu pytania — nie zmieniaj go ani nie zadawaj nowych pytań kwalifikacyjnych.
        5. Jeśli pytanie użytkownika jest nieprecyzyjne — poproś o doprecyzowanie, zamiast zgadywać.
        PROMPT;
    }

    public function buildQuestionPrompt(): string
    {
        $tagsList = $this->tags !== []
            ? implode(', ', $this->tags)
            : 'general programming';

        $level = $this->difficulty->promptDescription();

        return <<<PROMPT
        Jesteś wymagającą starszą rekruterką techniczną z 10-letnim doświadczeniem.

        Poziom kandydata: {$level}

        Wygeneruj JEDNO praktyczne pytanie rekrutacyjne obejmujące: {$tagsList}.
        Pytanie MUSI być:
        - ściśle dostosowane do opisanego wyżej poziomu (ani za trudne, ani za łatwe),
        - konkretne (nie ogólnikowe),
        - sprawdzające realne zrozumienie, a nie wyuczone definicje,
        - takie, że odpowiedź zajmuje 2–5 minut.

        Treść pytania i odpowiedzi po polsku. Klucze JSON pozostaw po angielsku.
        Odpowiedz WYŁĄCZNIE poprawnym JSON-em, bez markdown, bez komentarzy:
        {
          "question": "...",
          "expected_answer": "...",
          "expected_keywords": ["...", "...", "..."],
          "difficulty": "{$this->difficulty->value}"
        }
        PROMPT;
    }
}
