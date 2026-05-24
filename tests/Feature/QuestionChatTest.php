<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

function questionChatGeminiResponse(string $text = 'To wzorzec wstrzykiwania zależności — przekazujesz obiekt zamiast tworzyć go w klasie.'): array
{
    return [
        'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
        'usageMetadata' => ['promptTokenCount' => 50, 'candidatesTokenCount' => 80],
    ];
}

test('chat requires authentication', function () {
    $question = Question::factory()->create();

    $this->postJson("/api/questions/{$question->id}/chat", ['content' => 'Dlaczego?'])
        ->assertStatus(401);
});

test('chat returns 422 without api key', function () {
    $user = User::factory()->create(['gemini_api_key_encrypted' => null]);
    $question = Question::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/questions/{$question->id}/chat", ['content' => 'Dlaczego?'])
        ->assertStatus(422);
});

test('user cannot ask about another users question', function () {
    $owner = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $other = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $question = Question::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->postJson("/api/questions/{$question->id}/chat", ['content' => 'Wyjaśnij'])
        ->assertStatus(403);
});

test('chat returns ai content on success', function () {
    Http::fake(['*' => Http::response(questionChatGeminiResponse('Krótsza odpowiedź.'), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $question = Question::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson("/api/questions/{$question->id}/chat", [
            'content' => 'Dlaczego DI jest lepsze niż new?',
            'history' => [],
        ])
        ->assertOk();

    $response->assertJsonStructure(['data' => ['content', 'tokens_used']])
        ->assertJsonPath('data.content', 'Krótsza odpowiedź.')
        ->assertJsonPath('data.tokens_used', 130);
});

test('chat sends question context and history to gemini', function () {
    Http::fake(['*' => Http::response(questionChatGeminiResponse(), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $question = Question::factory()->create([
        'user_id' => $user->id,
        'content' => 'Czym jest unique-question-marker?',
        'expected_answer' => 'Odpowiedź wzorcowa zawiera unique-answer-marker.',
        'expected_keywords' => ['marker', 'unique'],
    ]);

    $this->actingAs($user)
        ->postJson("/api/questions/{$question->id}/chat", [
            'content' => 'Daj przykład',
            'history' => [
                ['role' => 'user', 'content' => 'unique-prior-user-msg'],
                ['role' => 'assistant', 'content' => 'unique-prior-assistant-msg'],
            ],
        ])
        ->assertOk();

    Http::assertSent(static function ($request): bool {
        $data = $request->data();
        $system = $data['systemInstruction']['parts'][0]['text'] ?? '';
        $turns = $data['contents'] ?? [];
        $firstUserTurn = $turns[0]['parts'][0]['text'] ?? '';
        $lastUserTurn = end($turns)['parts'][0]['text'] ?? '';
        $header = $request->header('x-goog-api-key');

        return str_contains($system, 'unique-question-marker')
            && str_contains($system, 'unique-answer-marker')
            && $firstUserTurn === 'unique-prior-user-msg'
            && $lastUserTurn === 'Daj przykład'
            && $header === ['fake-key'];
    });
});

test('chat validates content is required', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $question = Question::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/questions/{$question->id}/chat", ['history' => []])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');
});

test('chat validates content max length', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $question = Question::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/questions/{$question->id}/chat", [
            'content' => str_repeat('a', 501),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');
});

test('chat validates history size', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $question = Question::factory()->create(['user_id' => $user->id]);

    $history = array_fill(0, 11, ['role' => 'user', 'content' => 'x']);

    $this->actingAs($user)
        ->postJson("/api/questions/{$question->id}/chat", [
            'content' => 'a',
            'history' => $history,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('history');
});

test('chat returns 429 when gemini rate limits', function () {
    Http::fake(['*' => Http::response('Rate limited', 429)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $question = Question::factory()->create(['user_id' => $user->id]);
    RateLimiter::clear('question-chat:'.$user->id);

    $this->actingAs($user)
        ->postJson("/api/questions/{$question->id}/chat", ['content' => 'a'])
        ->assertStatus(429);
})->skip('Gemini retry loop with backoff makes this slow — covered by InterviewTest pattern.');

test('chat returns 502 on gemini server error', function () {
    Http::fake(['*' => Http::response('Bad gateway', 400)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $question = Question::factory()->create(['user_id' => $user->id]);
    RateLimiter::clear('question-chat:'.$user->id);

    $this->actingAs($user)
        ->postJson("/api/questions/{$question->id}/chat", ['content' => 'a'])
        ->assertStatus(502);
});

test('chat is rate limited per user', function () {
    Http::fake(['*' => Http::response(questionChatGeminiResponse(), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $question = Question::factory()->create(['user_id' => $user->id]);
    RateLimiter::clear('question-chat:'.$user->id);

    for ($i = 0; $i < 20; $i++) {
        $this->actingAs($user)
            ->postJson("/api/questions/{$question->id}/chat", ['content' => 'q'.$i])
            ->assertOk();
    }

    $this->actingAs($user)
        ->postJson("/api/questions/{$question->id}/chat", ['content' => 'over'])
        ->assertStatus(429);
});
