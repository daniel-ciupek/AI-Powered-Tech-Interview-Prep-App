<?php

declare(strict_types=1);

namespace App\Actions\Questions;

use App\Models\Question;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\PromptBuilder;
use Illuminate\Support\Facades\Crypt;

final class AskAboutQuestionAction
{
    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{content: string, tokens_used: int}
     */
    public function __invoke(User $user, Question $question, array $history, string $newMessage): array
    {
        assert($user->gemini_api_key_encrypted !== null);

        $systemPrompt = (new PromptBuilder)
            ->withDifficulty($question->difficulty)
            ->buildQuestionChatSystemPrompt(
                questionContent: $question->content,
                expectedAnswer: $question->expected_answer,
                keywords: $question->expected_keywords,
            );

        $messages = [
            ...$history,
            ['role' => 'user', 'content' => $newMessage],
        ];

        $apiKey = Crypt::decryptString($user->gemini_api_key_encrypted);
        $result = (new GeminiClient($apiKey))->chat(
            systemPrompt: $systemPrompt,
            messages: $messages,
            maxOutputTokens: 512,
        );

        return [
            'content' => $result['text'],
            'tokens_used' => $result['tokens_in'] + $result['tokens_out'],
        ];
    }
}
