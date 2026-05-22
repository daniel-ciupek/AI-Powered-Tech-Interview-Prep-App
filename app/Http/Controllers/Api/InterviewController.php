<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Interview\SendMessageAction;
use App\Actions\Interview\StartInterviewAction;
use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Exceptions\GeminiException;
use App\Exceptions\GeminiRateLimitException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\StartInterviewRequest;
use App\Jobs\GenerateInterviewReportJob;
use App\Models\InterviewSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InterviewController extends Controller
{
    public function start(StartInterviewRequest $request, StartInterviewAction $action): JsonResponse
    {
        $user = $request->user();
        assert($user !== null);

        if ($user->gemini_api_key_encrypted === null) {
            return response()->json(['message' => 'Gemini API key not configured.'], 422);
        }

        $difficulty = Difficulty::from(
            $request->validated('difficulty', $user->preferred_difficulty->value)
        );

        try {
            $session = $action($user, $difficulty, $request->validated('tags', []));

            return response()->json($this->sessionPayload($session), 201);
        } catch (GeminiRateLimitException) {
            return response()->json(['message' => 'AI rate limit reached. Please try again shortly.'], 429);
        } catch (GeminiException) {
            return response()->json(['message' => 'AI unavailable. Please try again.'], 502);
        }
    }

    public function message(
        SendMessageRequest $request,
        InterviewSession $interviewSession,
        SendMessageAction $action,
    ): JsonResponse {
        $user = $request->user();
        assert($user !== null);

        Gate::authorize('update', $interviewSession);

        if (! $interviewSession->isActive()) {
            return response()->json(['message' => 'Session is no longer active.'], 422);
        }

        try {
            $message = $action($user, $interviewSession, $request->validated('content'));

            return response()->json([
                'data' => [
                    'id' => $message->id,
                    'role' => $message->role->value,
                    'content' => $message->content,
                    'tokens_used' => $message->tokens_used,
                    'created_at' => $message->created_at->toISOString(),
                ],
            ]);
        } catch (GeminiRateLimitException) {
            return response()->json(['message' => 'AI rate limit reached. Please try again shortly.'], 429);
        } catch (GeminiException) {
            return response()->json(['message' => 'AI unavailable. Please try again.'], 502);
        }
    }

    public function finish(Request $request, InterviewSession $interviewSession): JsonResponse
    {
        $user = $request->user();
        assert($user !== null);

        Gate::authorize('update', $interviewSession);

        if (! $interviewSession->isActive()) {
            return response()->json(['message' => 'Session is already finished.'], 422);
        }

        $interviewSession->update(['status' => SessionStatus::Completed, 'ended_at' => now()]);

        GenerateInterviewReportJob::dispatch($interviewSession, $user);

        return response()->json(['message' => 'Report generation queued.']);
    }

    /** @return array<string, mixed> */
    private function sessionPayload(InterviewSession $session): array
    {
        return [
            'data' => [
                'id' => $session->id,
                'difficulty' => $session->difficulty->value,
                'topic_tags' => $session->topic_tags,
                'status' => $session->status->value,
                'messages' => $session->messages->map(static fn ($m) => [
                    'id' => $m->id,
                    'role' => $m->role->value,
                    'content' => $m->content,
                    'created_at' => $m->created_at->toISOString(),
                ])->values(),
            ],
        ];
    }
}
