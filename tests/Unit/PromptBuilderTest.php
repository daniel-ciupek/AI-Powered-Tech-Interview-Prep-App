<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Services\Gemini\PromptBuilder;

test('prompt contains the selected tags', function () {
    $prompt = (new PromptBuilder)
        ->withTags(['PHP', 'Laravel', 'Redis'])
        ->withDifficulty(Difficulty::Mid)
        ->buildQuestionPrompt();

    expect($prompt)->toContain('PHP, Laravel, Redis');
});

test('prompt contains the difficulty level label', function () {
    $prompt = (new PromptBuilder)
        ->withDifficulty(Difficulty::Senior)
        ->buildQuestionPrompt();

    expect($prompt)->toContain('Senior');
});

test('prompt contains difficulty value for JSON schema', function () {
    $prompt = (new PromptBuilder)
        ->withDifficulty(Difficulty::Junior)
        ->buildQuestionPrompt();

    expect($prompt)->toContain('"difficulty": "junior"');
});

test('prompt falls back to general programming when no tags given', function () {
    $prompt = (new PromptBuilder)
        ->buildQuestionPrompt();

    expect($prompt)->toContain('general programming');
});

test('prompt instructs gemini to respond with JSON only', function () {
    $prompt = (new PromptBuilder)->buildQuestionPrompt();

    expect($prompt)->toContain('valid JSON')
        ->and($prompt)->toContain('expected_keywords');
});
