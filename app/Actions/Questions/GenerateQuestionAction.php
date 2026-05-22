<?php

declare(strict_types=1);

namespace App\Actions\Questions;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Models\Question;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\PromptBuilder;
use App\Services\Gemini\ResponseValidator;
use Illuminate\Support\Facades\Crypt;

class GenerateQuestionAction
{
    public function __construct(
        private readonly ResponseValidator $responseValidator,
    ) {}

    /**
     * @param  list<string>  $tags
     */
    public function __invoke(User $user, Difficulty $difficulty, array $tags = []): Question
    {
        assert($user->gemini_api_key_encrypted !== null);

        $apiKey = Crypt::decryptString($user->gemini_api_key_encrypted);

        $prompt = (new PromptBuilder)
            ->withTags($tags)
            ->withDifficulty($difficulty)
            ->buildQuestionPrompt();

        $result = (new GeminiClient($apiKey))->generate($prompt);

        $validated = $this->responseValidator->validate($result['text']);

        return $user->questions()->create([
            'content' => $validated['question'],
            'expected_answer' => $validated['expected_answer'],
            'expected_keywords' => $validated['expected_keywords'],
            'difficulty' => $difficulty,
            'source' => QuestionSource::AiGenerated,
        ]);
    }
}
