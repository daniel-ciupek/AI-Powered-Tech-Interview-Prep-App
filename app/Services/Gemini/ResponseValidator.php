<?php

declare(strict_types=1);

namespace App\Services\Gemini;

use App\Enums\Difficulty;
use App\Exceptions\GeminiApiException;

class ResponseValidator
{
    private const REQUIRED_KEYS = ['question', 'expected_answer', 'expected_keywords', 'difficulty'];

    /**
     * Parse and validate a raw Gemini text response into a structured question array.
     *
     * @return array{question: string, expected_answer: string, expected_keywords: list<string>, difficulty: string}
     */
    public function validate(string $rawText): array
    {
        $cleaned = $this->extractJson($rawText);

        $decoded = json_decode($cleaned, associative: true);

        if (! is_array($decoded)) {
            throw new GeminiApiException('Gemini response is not valid JSON: '.$cleaned);
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if (! isset($decoded[$key])) {
                throw new GeminiApiException("Gemini response missing required key: {$key}");
            }
        }

        if (! is_string($decoded['question']) || trim($decoded['question']) === '') {
            throw new GeminiApiException('Gemini response "question" must be a non-empty string');
        }

        if (! is_string($decoded['expected_answer'])) {
            throw new GeminiApiException('Gemini response "expected_answer" must be a string');
        }

        if (! is_array($decoded['expected_keywords'])) {
            throw new GeminiApiException('Gemini response "expected_keywords" must be an array');
        }

        $validDifficulties = array_column(Difficulty::cases(), 'value');
        if (! in_array($decoded['difficulty'], $validDifficulties, strict: true)) {
            throw new GeminiApiException("Gemini response has invalid difficulty: {$decoded['difficulty']}");
        }

        return [
            'question' => trim($decoded['question']),
            'expected_answer' => trim($decoded['expected_answer']),
            'expected_keywords' => array_values(array_filter(
                $decoded['expected_keywords'],
                static fn ($kw): bool => is_string($kw),
            )),
            'difficulty' => $decoded['difficulty'],
        ];
    }

    private function extractJson(string $text): string
    {
        // Strip markdown code fences if Gemini wraps the JSON
        $stripped = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $stripped = preg_replace('/\s*```$/m', '', (string) $stripped);

        return trim((string) $stripped);
    }
}
