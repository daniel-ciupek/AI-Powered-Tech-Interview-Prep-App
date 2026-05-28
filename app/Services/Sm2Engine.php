<?php

declare(strict_types=1);

namespace App\Services;

class Sm2Engine
{
    public const EF_DEFAULT = 2.50;

    public const EF_MIN = 1.30;

    /**
     * Calculate new SM-2 values after a review.
     *
     * @param  int  $quality  0–5 (2 = failed/red, 4 = passed/green)
     * @return array{ease_factor: float, interval_days: int, repetitions_count: int}
     */
    public function calculate(
        float $easeFactor,
        int $intervalDays,
        int $repetitionsCount,
        int $quality,
    ): array {
        $newEf = max(
            self::EF_MIN,
            round($easeFactor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02)), 2)
        );

        if ($quality < 3) {
            return [
                'ease_factor' => $newEf,
                'interval_days' => 1,
                'repetitions_count' => 0,
            ];
        }

        $newCount = $repetitionsCount + 1;

        $newInterval = match (true) {
            $newCount === 1 => 1,
            $newCount === 2 => 6,
            default => (int) round($intervalDays * $newEf),
        };

        return [
            'ease_factor' => $newEf,
            'interval_days' => $newInterval,
            'repetitions_count' => $newCount,
        ];
    }
}
