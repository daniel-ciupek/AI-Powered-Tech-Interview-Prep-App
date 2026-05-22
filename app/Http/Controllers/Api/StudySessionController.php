<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudySessionController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        assert($user !== null);

        $repetitions = $user->repetitions()
            ->with('question')
            ->where('next_review_at', '<=', now())
            ->orderBy('next_review_at')
            ->get();

        $questions = $repetitions->map(static fn ($rep) => [
            'repetition_id' => $rep->id,
            'question' => new QuestionResource($rep->question),
        ]);

        return response()->json([
            'data' => $questions,
            'count' => $questions->count(),
        ]);
    }
}
