<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Models\User;
use App\Services\Gemini\AiCacheService;
use App\Services\Gemini\PromptBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

test('generate uses cached response and does not call gemini', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
        'preferred_difficulty' => Difficulty::Junior,
    ]);

    // Pre-populate cache with a matching entry
    $cacheService = app(AiCacheService::class);
    $cacheService->put(
        user: $user,
        prompt: app(PromptBuilder::class)
            ->withDifficulty(Difficulty::Junior)
            ->buildQuestionPrompt(),
        validated: [
            'question' => 'Cached question?',
            'expected_answer' => 'Cached answer.',
            'expected_keywords' => ['cache'],
            'difficulty' => 'junior',
        ],
        tokensIn: 0,
        tokensOut: 0,
        model: 'gemini-2.5-flash',
    );

    $response = $this->actingAs($user)
        ->postJson('/api/questions/generate')
        ->assertStatus(201);

    expect($response->json('data.content'))->toBe('Cached question?');

    Http::assertNothingSent();
});
