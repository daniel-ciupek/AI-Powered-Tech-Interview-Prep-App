<?php

declare(strict_types=1);

namespace App\Actions\Interview;

use App\Enums\MessageRole;
use App\Enums\SessionStatus;
use App\Models\InterviewSession;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\Crypt;

class GenerateReportAction
{
    private const REPORT_PROMPT = <<<'PROMPT'
    The interview is now over. Based on the entire conversation above,
    generate a structured Markdown evaluation report for the candidate.

    Include:
    ## Overall Assessment
    ## Strengths
    ## Areas for Improvement
    ## Recommendation (Hire / Consider / Pass)

    Be specific, reference actual answers from the conversation.
    Write in English.
    PROMPT;

    public function __invoke(User $user, InterviewSession $session): InterviewSession
    {
        assert($user->gemini_api_key_encrypted !== null);

        $history = $session->messages()
            ->whereIn('role', [MessageRole::User->value, MessageRole::Assistant->value])
            ->orderBy('created_at')
            ->get()
            ->map(static fn ($m): array => [
                'role' => $m->role->value,
                'content' => $m->content,
            ])
            ->all();

        $history[] = ['role' => 'user', 'content' => self::REPORT_PROMPT];

        $apiKey = Crypt::decryptString($user->gemini_api_key_encrypted);
        $result = (new GeminiClient($apiKey))->generate(
            prompt: implode("\n\n", array_map(
                static fn (array $m): string => strtoupper($m['role']).': '.$m['content'],
                $history,
            )),
            maxOutputTokens: 2048,
        );

        $session->update([
            'final_report' => $result['text'],
            'status' => SessionStatus::Completed,
            'ended_at' => now(),
            'tokens_used_total' => $session->tokens_used_total + $result['tokens_in'] + $result['tokens_out'],
        ]);

        $session->refresh();

        return $session;
    }
}
