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
    Rozmowa kwalifikacyjna dobiegła końca. Na podstawie całej powyższej konwersacji
    wygeneruj uporządkowany raport oceny kandydata w formacie Markdown.

    Sekcje (zachowaj polskie nagłówki):
    ## Ogólna ocena
    ## Mocne strony
    ## Obszary do poprawy
    ## Rekomendacja (Zatrudnić / Rozważyć / Odrzucić)

    Bądź konkretny, odwołuj się do rzeczywistych odpowiedzi z rozmowy.
    Pisz po polsku.
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
