<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

function geminiQuestionResponse(array $overrides = []): array
{
    return array_merge([
        'candidates' => [['content' => ['parts' => [['text' => json_encode([
            'question' => 'What is dependency injection?',
            'expected_answer' => 'A design pattern for decoupling.',
            'expected_keywords' => ['IoC', 'container', 'binding'],
            'difficulty' => 'junior',
        ])]]]]],
        'usageMetadata' => ['promptTokenCount' => 50, 'candidatesTokenCount' => 100],
    ], $overrides);
}

test('generate endpoint requires authentication', function () {
    $this->postJson('/api/questions/generate')->assertStatus(401);
});

test('generate returns 422 when no api key is configured', function () {
    $user = User::factory()->create(['gemini_api_key_encrypted' => null]);

    $this->actingAs($user)
        ->postJson('/api/questions/generate')
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Gemini API key not configured.']);
});

test('generate creates and returns a question on success', function () {
    Http::fake(['*' => Http::response(geminiQuestionResponse(), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-api-key'),
        'preferred_difficulty' => Difficulty::Junior,
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/questions/generate');

    $response->assertStatus(201)
        ->assertJsonFragment(['difficulty' => 'junior'])
        ->assertJsonFragment(['source' => 'ai_generated'])
        ->assertJsonStructure(['data' => ['id', 'content', 'difficulty', 'source', 'expected_keywords', 'created_at']]);

    $this->assertDatabaseHas('questions', [
        'user_id' => $user->id,
        'source' => QuestionSource::AiGenerated->value,
    ]);
});

test('generate uses user preferred difficulty when not specified', function () {
    Http::fake(['*' => Http::response(geminiQuestionResponse(), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-api-key'),
        'preferred_difficulty' => Difficulty::Senior,
    ]);

    $this->actingAs($user)->postJson('/api/questions/generate')->assertStatus(201);

    $this->assertDatabaseHas('questions', ['user_id' => $user->id, 'difficulty' => 'senior']);
});

test('generate accepts explicit difficulty override', function () {
    Http::fake(['*' => Http::response(geminiQuestionResponse(), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-api-key'),
        'preferred_difficulty' => Difficulty::Junior,
    ]);

    $this->actingAs($user)
        ->postJson('/api/questions/generate', ['difficulty' => 'senior'])
        ->assertStatus(201);

    $this->assertDatabaseHas('questions', ['user_id' => $user->id, 'difficulty' => 'senior']);
});

test('generate returns 429 when gemini rate limits', function () {
    Http::fake(['*' => Http::response('Rate limited', 429)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-api-key'),
    ]);

    $this->actingAs($user)
        ->postJson('/api/questions/generate')
        ->assertStatus(429);
});

test('generate validates tags array', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-api-key'),
    ]);

    $this->actingAs($user)
        ->postJson('/api/questions/generate', ['tags' => 'not-an-array'])
        ->assertStatus(422);
});
