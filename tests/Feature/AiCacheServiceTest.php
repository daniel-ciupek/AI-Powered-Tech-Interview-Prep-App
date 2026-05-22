<?php

declare(strict_types=1);

use App\Models\AiResponseCache;
use App\Services\Gemini\AiCacheService;
use Illuminate\Support\Facades\DB;

function validatedQuestion(array $overrides = []): array
{
    return array_merge([
        'question' => 'What is dependency injection?',
        'expected_answer' => 'A design pattern.',
        'expected_keywords' => ['IoC', 'container'],
        'difficulty' => 'junior',
    ], $overrides);
}

function insertExpiredCacheEntry(string $prompt): void
{
    DB::table('ai_response_cache')->insert([
        'prompt_hash' => hash('sha256', $prompt),
        'response' => json_encode(validatedQuestion()),
        'model' => 'gemini-2.0-flash',
        'tokens_in' => 10,
        'tokens_out' => 20,
        'created_at' => now()->subDays(AiCacheService::TTL_DAYS + 1)->toDateTimeString(),
    ]);
}

test('get returns null on cache miss', function () {
    expect((new AiCacheService)->get('unknown prompt'))->toBeNull();
});

test('put stores validated response and get retrieves it', function () {
    $service = new AiCacheService;
    $service->put('test prompt', validatedQuestion(), 50, 100, 'gemini-2.0-flash');

    $result = $service->get('test prompt');

    expect($result)->not->toBeNull()
        ->and($result['question'])->toBe('What is dependency injection?')
        ->and($result['expected_keywords'])->toBe(['IoC', 'container']);
});

test('get returns null for expired cache entries', function () {
    insertExpiredCacheEntry('old prompt');

    expect((new AiCacheService)->get('old prompt'))->toBeNull();
});

test('put updates existing entry for same prompt', function () {
    $service = new AiCacheService;
    $service->put('same prompt', validatedQuestion(['question' => 'First']), 10, 20, 'gemini-2.0-flash');
    $service->put('same prompt', validatedQuestion(['question' => 'Second']), 15, 25, 'gemini-2.0-flash');

    expect(AiResponseCache::count())->toBe(1)
        ->and($service->get('same prompt')['question'])->toBe('Second');
});

test('prune expired deletes old entries and keeps fresh ones', function () {
    $service = new AiCacheService;
    insertExpiredCacheEntry('expired');
    $service->put('fresh', validatedQuestion(), 10, 20, 'gemini-2.0-flash');

    $deleted = $service->pruneExpired();

    expect($deleted)->toBe(1)
        ->and(AiResponseCache::count())->toBe(1);
});
