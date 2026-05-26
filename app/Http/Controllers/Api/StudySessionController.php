<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\Difficulty;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudySessionController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        assert($user !== null);

        $validated = $request->validate([
            'difficulty' => ['sometimes', 'nullable', Rule::enum(Difficulty::class)],
        ]);

        $query = $user->repetitions()
            ->with('question')
            ->where('next_review_at', '<=', now())
            ->orderBy('next_review_at');

        if (! empty($validated['difficulty'])) {
            $query->whereHas('question', static function ($q) use ($validated): void {
                $q->where('difficulty', $validated['difficulty']);
            });
        }

        $questions = $query->get()->map(static fn ($rep) => [
            'repetition_id' => $rep->id,
            'question' => new QuestionResource($rep->question),
        ]);

        return response()->json([
            'data' => $questions,
            'count' => $questions->count(),
        ]);
    }
}
