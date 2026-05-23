<?php

declare(strict_types=1);

use App\Enums\MessageRole;
use App\Enums\SessionStatus;
use App\Jobs\GenerateInterviewReportJob;
use App\Models\InterviewMessage;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

function interviewGeminiResponse(string $text = 'Hello! I am Anna. Let us start.'): array
{
    return [
        'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
        'usageMetadata' => ['promptTokenCount' => 20, 'candidatesTokenCount' => 30],
    ];
}

test('start interview requires authentication', function () {
    $this->postJson('/api/interview/start')->assertStatus(401);
});

test('start interview returns 422 without api key', function () {
    $user = User::factory()->create(['gemini_api_key_encrypted' => null]);

    $this->actingAs($user)
        ->postJson('/api/interview/start')
        ->assertStatus(422);
});

test('start interview creates session and initial ai message', function () {
    Http::fake(['*' => Http::response(interviewGeminiResponse(), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/interview/start', ['tags' => ['PHP', 'Laravel']])
        ->assertStatus(201);

    $response->assertJsonStructure([
        'data' => ['id', 'difficulty', 'topic_tags', 'status', 'messages'],
    ]);

    $this->assertDatabaseHas('interview_sessions', [
        'user_id' => $user->id,
        'status' => 'active',
    ]);
});

test('send message requires authentication', function () {
    $session = InterviewSession::factory()->create();

    $this->postJson("/api/interview/{$session->id}/message", ['content' => 'hi'])
        ->assertStatus(401);
});

test('user cannot message another users session', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $session = InterviewSession::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->postJson("/api/interview/{$session->id}/message", ['content' => 'hi'])
        ->assertStatus(403);
});

test('send message appends ai response to session', function () {
    Http::fake(['*' => Http::response(interviewGeminiResponse('Good answer!'), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $session = InterviewSession::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson("/api/interview/{$session->id}/message", ['content' => 'My answer here.'])
        ->assertOk();

    $response->assertJsonFragment(['content' => 'Good answer!', 'role' => 'assistant']);

    $this->assertDatabaseHas('interview_messages', [
        'session_id' => $session->id,
        'role' => 'user',
        'content' => 'My answer here.',
    ]);
});

test('cannot send message to completed session', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $session = InterviewSession::factory()->completed()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/interview/{$session->id}/message", ['content' => 'test'])
        ->assertStatus(422);
});

test('finish interview dispatches report job', function () {
    Queue::fake();

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $session = InterviewSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/interview/{$session->id}/finish")
        ->assertOk();

    expect($session->refresh()->status)->toBe(SessionStatus::Completed);
    Queue::assertPushed(GenerateInterviewReportJob::class);
});

test('start interview is rate limited per user', function () {
    Http::fake(['*' => Http::response(interviewGeminiResponse(), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    RateLimiter::clear('interview-start:'.$user->id);

    for ($i = 0; $i < 3; $i++) {
        $this->actingAs($user)
            ->postJson('/api/interview/start')
            ->assertStatus(201);
    }

    $this->actingAs($user)
        ->postJson('/api/interview/start')
        ->assertStatus(429);
});

test('generate report job writes final_report and completes session', function () {
    $reportMarkdown = "## Overall Assessment\nSolid junior performance.\n\n## Recommendation\nHire.";

    Http::fake(['*' => Http::response(interviewGeminiResponse($reportMarkdown), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $session = InterviewSession::factory()->create([
        'user_id' => $user->id,
        'status' => SessionStatus::Completed,
        'ended_at' => now(),
        'tokens_used_total' => 100,
    ]);
    InterviewMessage::create([
        'session_id' => $session->id,
        'role' => MessageRole::Assistant,
        'content' => 'Tell me about dependency injection.',
        'tokens_used' => 30,
    ]);
    InterviewMessage::create([
        'session_id' => $session->id,
        'role' => MessageRole::User,
        'content' => 'It is a pattern where dependencies are provided externally.',
        'tokens_used' => 20,
    ]);

    GenerateInterviewReportJob::dispatchSync($session, $user);

    $session->refresh();

    expect($session->final_report)->toBe($reportMarkdown);
    expect($session->status)->toBe(SessionStatus::Completed);
    expect($session->tokens_used_total)->toBeGreaterThan(100);
});

test('generate report job sends conversation history to gemini', function () {
    Http::fake(['*' => Http::response(interviewGeminiResponse('Report body'), 200)]);

    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-key'),
    ]);
    $session = InterviewSession::factory()->create(['user_id' => $user->id]);
    InterviewMessage::create([
        'session_id' => $session->id,
        'role' => MessageRole::User,
        'content' => 'unique-candidate-answer-marker',
        'tokens_used' => 10,
    ]);

    GenerateInterviewReportJob::dispatchSync($session, $user);

    Http::assertSent(static function ($request): bool {
        $payload = $request->data();
        $prompt = $payload['contents'][0]['parts'][0]['text'] ?? '';

        return str_contains($prompt, 'unique-candidate-answer-marker')
            && str_contains($prompt, 'Overall Assessment');
    });
});

test('interview page requires authentication', function () {
    $this->get('/interview')->assertRedirect('/login');
});

test('interview page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/interview')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Interview/Show'));
});
