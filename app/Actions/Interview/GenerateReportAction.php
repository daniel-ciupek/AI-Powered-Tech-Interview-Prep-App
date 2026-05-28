<?php

declare(strict_types=1);

namespace App\Actions\Interview;

use App\Enums\MessageRole;
use App\Enums\SessionStatus;
use App\Exceptions\GeminiRateLimitException;
use App\Models\InterviewSession;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\Crypt;

final class GenerateReportAction
{
    private const SYSTEM_PROMPT = 'Jesteś ekspertem ds. rekrutacji technicznej oceniającym rozmowę kwalifikacyjną. Pisz wyłącznie po polsku.';

    private const REPORT_REQUEST = <<<'PROMPT'
    Na podstawie powyższej rozmowy kwalifikacyjnej wygeneruj szczegółowy raport oceny kandydata w formacie Markdown.

    Użyj dokładnie tych nagłówków (##):
    ## Ogólna ocena
    ## Mocne strony
    ## Obszary do poprawy
    ## Rekomendacja (Zatrudnić / Rozważyć / Odrzucić)

    Bądź konkretny i odwołuj się do rzeczywistych odpowiedzi z rozmowy. Pisz po polsku.
    PROMPT;

    public function __invoke(User $user, InterviewSession $session): InterviewSession
    {
        assert($user->gemini_api_key_encrypted !== null);

        $messages = $session->messages()
            ->whereIn('role', [MessageRole::User->value, MessageRole::Assistant->value])
            ->orderBy('created_at')
            ->get()
            ->map(static fn ($m): array => [
                'role' => $m->role->value,
                'content' => $m->content,
            ])
            ->all();

        $messages[] = ['role' => 'user', 'content' => self::REPORT_REQUEST];

        $apiKey = Crypt::decryptString($user->gemini_api_key_encrypted);
        $client = new GeminiClient($apiKey);

        try {
            $result = $client->chat(
                systemPrompt: self::SYSTEM_PROMPT,
                messages: $messages,
                maxOutputTokens: 2048,
            );
        } catch (GeminiRateLimitException) {
            // Gemini free-tier resets in 60 s — wait and retry once.
            sleep(65);
            $result = $client->chat(
                systemPrompt: self::SYSTEM_PROMPT,
                messages: $messages,
                maxOutputTokens: 2048,
            );
        }

        $session->update([
            'final_report' => $result['text'],
            'status' => SessionStatus::Completed,
            'ended_at' => $session->ended_at ?? now(),
            'tokens_used_total' => $session->tokens_used_total + $result['tokens_in'] + $result['tokens_out'],
        ]);

        $session->refresh();

        return $session;
    }
}
