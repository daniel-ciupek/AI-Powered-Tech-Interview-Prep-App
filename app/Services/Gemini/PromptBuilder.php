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

    public function buildQuestionPrompt(): string
    {
        $tagsList = $this->tags !== []
            ? implode(', ', $this->tags)
            : 'general programming';

        $level = $this->difficulty->label();

        return <<<PROMPT
        You are a demanding senior technical recruiter with 10 years of experience.
        You are conducting a {$level} level technical interview.

        Generate ONE practical question covering: {$tagsList}.
        The question must be:
        - specific (not vague),
        - testing understanding, not memorised definitions,
        - answerable in 2–5 minutes.

        Respond ONLY with valid JSON, no markdown, no comments:
        {
          "question": "...",
          "expected_answer": "...",
          "expected_keywords": ["...", "...", "..."],
          "difficulty": "{$this->difficulty->value}"
        }
        PROMPT;
    }
}
