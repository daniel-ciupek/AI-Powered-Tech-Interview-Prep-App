<?php

declare(strict_types=1);

namespace App\Actions\Repetitions;

use App\Events\QuestionReviewed;
use App\Models\Repetition;
use App\Services\Sm2Engine;

class RecordReviewAction
{
    public function __construct(private readonly Sm2Engine $sm2Engine) {}

    public function __invoke(Repetition $repetition, int $quality): Repetition
    {
        $easeBefore = $repetition->ease_factor;
        $intervalBefore = $repetition->interval_days;

        $result = $this->sm2Engine->calculate(
            easeFactor: $repetition->ease_factor,
            intervalDays: $repetition->interval_days,
            repetitionsCount: $repetition->repetitions_count,
            quality: $quality,
        );

        $repetition->update([
            'ease_factor' => $result['ease_factor'],
            'interval_days' => $result['interval_days'],
            'repetitions_count' => $result['repetitions_count'],
            'quality_last' => $quality,
            'last_reviewed_at' => now(),
            'next_review_at' => now()->addDays($result['interval_days']),
        ]);

        $repetition->reviewLogs()->create([
            'quality' => $quality,
            'ease_before' => $easeBefore,
            'ease_after' => $result['ease_factor'],
            'interval_before' => $intervalBefore,
            'interval_after' => $result['interval_days'],
        ]);

        QuestionReviewed::dispatch($repetition);

        $repetition->refresh();

        return $repetition;
    }
}
