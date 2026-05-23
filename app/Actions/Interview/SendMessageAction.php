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

final class SendMessageAction
{
    public function __invoke(User $user, InterviewSession $session, string $userContent): InterviewMessage
    {
        assert($user->gemini_api_key_encrypted !== null);

        $session->messages()->create([
            'role' => MessageRole::User,
            'content' => $userContent,
        ]);

        $systemPrompt = (new PromptBuilder)
            ->withTags($session->topic_tags)
            ->withDifficulty($session->difficulty)
            ->buildInterviewSystemPrompt();

        $history = $session->messages()
            ->whereIn('role', [MessageRole::User->value, MessageRole::Assistant->value])
            ->orderBy('created_at')
            ->get()
            ->map(static fn (InterviewMessage $m): array => [
                'role' => $m->role->value,
                'content' => $m->content,
            ])
            ->values()
            ->all();

        $apiKey = Crypt::decryptString($user->gemini_api_key_encrypted);
        $result = (new GeminiClient($apiKey))->chat(
            systemPrompt: $systemPrompt,
            messages: $history,
        );

        $assistant = $session->messages()->create([
            'role' => MessageRole::Assistant,
            'content' => $result['text'],
            'tokens_used' => $result['tokens_in'] + $result['tokens_out'],
        ]);

        $session->increment('tokens_used_total', $result['tokens_in'] + $result['tokens_out']);

        return $assistant;
    }
}
