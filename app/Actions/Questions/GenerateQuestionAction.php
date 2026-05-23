<?php

declare(strict_types=1);

namespace App\Actions\Questions;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Models\Question;
use App\Models\User;
use App\Services\Gemini\AiCacheService;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\PromptBuilder;
use App\Services\Gemini\ResponseValidator;
use Illuminate\Support\Facades\Crypt;

final class GenerateQuestionAction
{
    private const MODEL = 'gemini-2.5-flash';

    public function __construct(
        private readonly ResponseValidator $responseValidator,
        private readonly AiCacheService $cacheService,
    ) {}

    /**
     * @param  list<string>  $tags
     */
    public function __invoke(User $user, Difficulty $difficulty, array $tags = []): Question
    {
        assert($user->gemini_api_key_encrypted !== null);

        $prompt = (new PromptBuilder)
            ->withTags($tags)
            ->withDifficulty($difficulty)
            ->buildQuestionPrompt();

        $validated = $this->cacheService->get($user, $prompt);

        if ($validated === null) {
            $apiKey = Crypt::decryptString($user->gemini_api_key_encrypted);
            $result = (new GeminiClient($apiKey))->generate($prompt);
            $validated = $this->responseValidator->validate($result['text']);

            $this->cacheService->put(
                user: $user,
                prompt: $prompt,
                validated: $validated,
                tokensIn: $result['tokens_in'],
                tokensOut: $result['tokens_out'],
                model: self::MODEL,
            );
        }

        $question = $user->questions()->create([
            'content' => $validated['question'],
            'expected_answer' => $validated['expected_answer'],
            'expected_keywords' => $validated['expected_keywords'],
            'difficulty' => $difficulty,
            'source' => QuestionSource::AiGenerated,
        ]);

        $user->repetitions()->create([
            'question_id' => $question->id,
            'next_review_at' => now(),
        ]);

        return $question;
    }
}
