<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Questions\AskAboutQuestionAction;
use App\Exceptions\GeminiException;
use App\Exceptions\GeminiRateLimitException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AskAboutQuestionRequest;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class QuestionChatController extends Controller
{
    public function __invoke(
        AskAboutQuestionRequest $request,
        Question $question,
        AskAboutQuestionAction $action,
    ): JsonResponse {
        $user = $request->user();
        assert($user !== null);

        Gate::authorize('view', $question);

        if ($user->gemini_api_key_encrypted === null) {
            return response()->json(['message' => __('messages.gemini_key_missing')], 422);
        }

        try {
            $result = $action(
                user: $user,
                question: $question,
                history: $request->validated('history', []),
                newMessage: $request->validated('content'),
            );

            return response()->json(['data' => $result]);
        } catch (GeminiRateLimitException) {
            return response()->json(['message' => __('messages.ai_rate_limit')], 429);
        } catch (GeminiException) {
            return response()->json(['message' => __('messages.ai_unavailable')], 502);
        }
    }
}
