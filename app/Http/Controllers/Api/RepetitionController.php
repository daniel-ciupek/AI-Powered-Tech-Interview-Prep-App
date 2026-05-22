<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Repetitions\RecordReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordReviewRequest;
use App\Models\Repetition;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RepetitionController extends Controller
{
    public function review(
        RecordReviewRequest $request,
        Repetition $repetition,
        RecordReviewAction $action,
    ): JsonResponse {
        Gate::authorize('update', $repetition->question);

        $updated = $action($repetition, $request->validated('quality'));

        return response()->json([
            'data' => [
                'id' => $updated->id,
                'ease_factor' => $updated->ease_factor,
                'interval_days' => $updated->interval_days,
                'repetitions_count' => $updated->repetitions_count,
                'next_review_at' => $updated->next_review_at->toISOString(),
            ],
        ]);
    }
}
