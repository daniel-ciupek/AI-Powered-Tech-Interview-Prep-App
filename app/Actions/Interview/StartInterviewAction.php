<?php

declare(strict_types=1);

namespace App\Actions\Interview;

use App\Enums\Difficulty;
use App\Enums\MessageRole;
use App\Enums\SessionStatus;
use App\Models\InterviewSession;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\PromptBuilder;
use Illuminate\Support\Facades\Crypt;

class StartInterviewAction
{
    /**
     * @param  list<string>  $tags
     */
    public function __invoke(User $user, Difficulty $difficulty, array $tags = []): InterviewSession
    {
        assert($user->gemini_api_key_encrypted !== null);

        $systemPrompt = (new PromptBuilder)
            ->withTags($tags)
            ->withDifficulty($difficulty)
            ->buildInterviewSystemPrompt();

        $session = $user->interviewSessions()->create([
            'topic_tags' => $tags,
            'difficulty' => $difficulty,
            'status' => SessionStatus::Active,
            'started_at' => now(),
        ]);

        $apiKey = Crypt::decryptString($user->gemini_api_key_encrypted);
        $client = new GeminiClient($apiKey);

        $result = $client->chat(
            systemPrompt: $systemPrompt,
            messages: [['role' => 'user', 'content' => 'Hello, I am ready to start the interview.']],
        );

        $session->messages()->create([
            'role' => MessageRole::Assistant,
            'content' => $result['text'],
            'tokens_used' => $result['tokens_in'] + $result['tokens_out'],
        ]);

        $session->increment('tokens_used_total', $result['tokens_in'] + $result['tokens_out']);

        return $session->load('messages');
    }
}
