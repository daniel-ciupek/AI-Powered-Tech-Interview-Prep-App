<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Questions\GenerateQuestionAction;
use App\Enums\Difficulty;
use App\Exceptions\GeminiException;
use App\Exceptions\GeminiRateLimitException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateQuestionRequest;
use App\Http\Resources\QuestionResource;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function generate(GenerateQuestionRequest $request, GenerateQuestionAction $action): JsonResponse
    {
        $user = $request->user();
        assert($user !== null);

        if ($user->gemini_api_key_encrypted === null) {
            return response()->json(['message' => __('messages.gemini_key_missing')], 422);
        }

        $difficulty = Difficulty::from(
            $request->validated('difficulty', $user->preferred_difficulty->value)
        );

        try {
            $question = $action($user, $difficulty, $request->validated('tags', []));

            return (new QuestionResource($question))->response()->setStatusCode(201);
        } catch (GeminiRateLimitException) {
            return response()->json(['message' => __('messages.ai_rate_limit')], 429);
        } catch (GeminiException) {
            return response()->json(['message' => __('messages.ai_generation_failed')], 502);
        }
    }
}
