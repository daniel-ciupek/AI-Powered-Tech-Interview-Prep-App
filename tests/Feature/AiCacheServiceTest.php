<?php

declare(strict_types=1);

use App\Models\AiResponseCache;
use App\Models\User;
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

function insertExpiredCacheEntry(User $user, string $prompt): void
{
    DB::table('ai_response_cache')->insert([
        'user_id' => $user->id,
        'prompt_hash' => hash('sha256', $prompt),
        'response' => json_encode(validatedQuestion()),
        'model' => 'gemini-2.5-flash',
        'tokens_in' => 10,
        'tokens_out' => 20,
        'created_at' => now()->subDays(AiCacheService::TTL_DAYS + 1)->toDateTimeString(),
    ]);
}

test('get returns null on cache miss', function () {
    $user = User::factory()->create();

    expect((new AiCacheService)->get($user, 'unknown prompt'))->toBeNull();
});

test('put stores validated response and get retrieves it', function () {
    $user = User::factory()->create();
    $service = new AiCacheService;
    $service->put($user, 'test prompt', validatedQuestion(), 50, 100, 'gemini-2.5-flash');

    $result = $service->get($user, 'test prompt');

    expect($result)->not->toBeNull()
        ->and($result['question'])->toBe('What is dependency injection?')
        ->and($result['expected_keywords'])->toBe(['IoC', 'container']);
});

test('get returns null for expired cache entries', function () {
    $user = User::factory()->create();
    insertExpiredCacheEntry($user, 'old prompt');

    expect((new AiCacheService)->get($user, 'old prompt'))->toBeNull();
});

test('put updates existing entry for same prompt and user', function () {
    $user = User::factory()->create();
    $service = new AiCacheService;
    $service->put($user, 'same prompt', validatedQuestion(['question' => 'First']), 10, 20, 'gemini-2.5-flash');
    $service->put($user, 'same prompt', validatedQuestion(['question' => 'Second']), 15, 25, 'gemini-2.5-flash');

    expect(AiResponseCache::count())->toBe(1)
        ->and($service->get($user, 'same prompt')['question'])->toBe('Second');
});

test('two users with the same prompt get isolated cache entries', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $service = new AiCacheService;

    $service->put($alice, 'shared prompt', validatedQuestion(['question' => "Alice's question"]), 10, 20, 'gemini-2.5-flash');

    expect($service->get($bob, 'shared prompt'))->toBeNull();

    $service->put($bob, 'shared prompt', validatedQuestion(['question' => "Bob's question"]), 10, 20, 'gemini-2.5-flash');

    expect($service->get($alice, 'shared prompt')['question'])->toBe("Alice's question")
        ->and($service->get($bob, 'shared prompt')['question'])->toBe("Bob's question")
        ->and(AiResponseCache::count())->toBe(2);
});

test('prune expired deletes old entries and keeps fresh ones', function () {
    $user = User::factory()->create();
    $service = new AiCacheService;
    insertExpiredCacheEntry($user, 'expired');
    $service->put($user, 'fresh', validatedQuestion(), 10, 20, 'gemini-2.5-flash');

    $deleted = $service->pruneExpired();

    expect($deleted)->toBe(1)
        ->and(AiResponseCache::count())->toBe(1);
});
