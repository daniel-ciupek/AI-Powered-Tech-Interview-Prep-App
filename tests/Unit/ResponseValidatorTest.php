<?php

declare(strict_types=1);

use App\Exceptions\GeminiApiException;
use App\Services\Gemini\ResponseValidator;

function validJsonResponse(array $overrides = []): string
{
    return json_encode(array_merge([
        'question' => 'What is dependency injection?',
        'expected_answer' => 'A design pattern...',
        'expected_keywords' => ['IoC', 'container', 'binding'],
        'difficulty' => 'junior',
    ], $overrides));
}

test('validates a correct JSON response', function () {
    $result = (new ResponseValidator)->validate(validJsonResponse());

    expect($result['question'])->toBe('What is dependency injection?')
        ->and($result['expected_keywords'])->toBe(['IoC', 'container', 'binding'])
        ->and($result['difficulty'])->toBe('junior');
});

test('strips markdown code fences before parsing', function () {
    $raw = "```json\n".validJsonResponse()."\n```";

    $result = (new ResponseValidator)->validate($raw);

    expect($result['question'])->toBe('What is dependency injection?');
});

test('throws on invalid JSON', function () {
    expect(fn () => (new ResponseValidator)->validate('not json at all'))
        ->toThrow(GeminiApiException::class, 'not valid JSON');
});

test('throws when required key is missing', function () {
    $json = json_encode([
        'question' => 'A question',
        'expected_answer' => 'An answer',
        'difficulty' => 'junior',
        // missing expected_keywords
    ]);

    expect(fn () => (new ResponseValidator)->validate($json))
        ->toThrow(GeminiApiException::class, 'expected_keywords');
});

test('throws when question is empty', function () {
    expect(fn () => (new ResponseValidator)->validate(validJsonResponse(['question' => '   '])))
        ->toThrow(GeminiApiException::class);
});

test('throws when difficulty is not a valid enum value', function () {
    expect(fn () => (new ResponseValidator)->validate(validJsonResponse(['difficulty' => 'expert'])))
        ->toThrow(GeminiApiException::class, 'invalid difficulty');
});

test('filters non-string values from expected_keywords', function () {
    $result = (new ResponseValidator)->validate(
        validJsonResponse(['expected_keywords' => ['valid', 42, null, 'also-valid']])
    );

    expect($result['expected_keywords'])->toBe(['valid', 'also-valid']);
});
