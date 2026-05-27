<?php

declare(strict_types=1);

namespace App\Actions\Interview;

use App\Enums\MessageRole;
use App\Models\InterviewMessage;
use App\Models\InterviewSession;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\PromptBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

final class SendMessageAction
{
    public function __invoke(User $user, InterviewSession $session, string $userContent): InterviewMessage
    {
        assert($user->gemini_api_key_encrypted !== null);

        $systemPrompt = (new PromptBuilder)
            ->withTags($session->topic_tags)
            ->withDifficulty($session->difficulty)
            ->buildInterviewSystemPrompt();

        // Build history from DB (existing messages) + the new user message appended inline.
        // This avoids saving the user message before the API call so that a failed API call
        // does not leave an orphaned user message that corrupts future history.
        $existing = $session->messages()
            ->whereIn('role', [MessageRole::User->value, MessageRole::Assistant->value])
            ->orderByDesc('created_at')
            ->take(19)
            ->get()
            ->reverse()
            ->map(static fn (InterviewMessage $m): array => [
                'role' => $m->role->value,
                'content' => $m->content,
            ])
            ->values()
            ->all();

        $history = [...$existing, ['role' => MessageRole::User->value, 'content' => $userContent]];

        $apiKey = Crypt::decryptString($user->gemini_api_key_encrypted);
        $result = (new GeminiClient($apiKey))->chat(
            systemPrompt: $systemPrompt,
            messages: $history,
        );

        // Only write to DB after a successful API response.
        return DB::transaction(function () use ($session, $userContent, $result): InterviewMessage {
            $session->messages()->create([
                'role' => MessageRole::User,
                'content' => $userContent,
            ]);

            $assistant = $session->messages()->create([
                'role' => MessageRole::Assistant,
                'content' => $result['text'],
                'tokens_used' => $result['tokens_in'] + $result['tokens_out'],
            ]);

            $session->increment('tokens_used_total', $result['tokens_in'] + $result['tokens_out']);

            return $assistant;
        });
    }
}
