<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class LoadDashboardStatsAction
{
    private const HEATMAP_DAYS = 84;

    private const RETENTION_WINDOW_DAYS = 30;

    /**
     * @return array{
     *     streak: array{current: int, last_studied_at: string|null, daily_goal: int},
     *     today: array{reviewed: int, due_remaining: int},
     *     totals: array{questions: int, reviews: int, interviews: int},
     *     retention: array{rate_30d: float|null, sample_size: int},
     *     heatmap: list<array{date: string, count: int}>
     * }
     */
    public function __invoke(User $user): array
    {
        $today = Carbon::today();
        $heatmapStart = $today->copy()->subDays(self::HEATMAP_DAYS - 1);
        $retentionStart = now()->subDays(self::RETENTION_WINDOW_DAYS);

        // Single aggregate over review_logs covers: today_reviewed, total reviews, retention sample/hits.
        // Postgres FILTER (WHERE ...) lets one scan compute all four counters in a single round-trip.
        $reviewStats = DB::table('review_logs')
            ->join('repetitions', 'review_logs.repetition_id', '=', 'repetitions.id')
            ->where('repetitions.user_id', $user->id)
            ->selectRaw(
                'COUNT(*) AS total,
                 COUNT(*) FILTER (WHERE review_logs.created_at::date = ?) AS today_count,
                 COUNT(*) FILTER (WHERE review_logs.created_at >= ?) AS retention_total,
                 COUNT(*) FILTER (WHERE review_logs.created_at >= ? AND quality >= 3) AS retention_hits',
                [$today->toDateString(), $retentionStart, $retentionStart]
            )
            ->first();

        // Single round-trip for the three remaining counters across separate tables.
        $counts = DB::query()
            ->selectRaw(
                '(SELECT COUNT(*) FROM questions WHERE user_id = ?) AS questions,
                 (SELECT COUNT(*) FROM interview_sessions WHERE user_id = ?) AS interviews,
                 (SELECT COUNT(*) FROM repetitions WHERE user_id = ? AND next_review_at <= NOW()) AS due',
                [$user->id, $user->id, $user->id]
            )
            ->first();

        $sample = (int) ($reviewStats->retention_total ?? 0);
        $hits = (int) ($reviewStats->retention_hits ?? 0);
        $retentionRate = $sample > 0 ? round($hits / $sample, 4) : null;

        // to_char(..., 'YYYY-MM-DD') returns a string that matches Carbon::toDateString(),
        // so the result keys can be looked up directly when filling the heatmap below.
        $heatmapRows = DB::table('review_logs')
            ->join('repetitions', 'review_logs.repetition_id', '=', 'repetitions.id')
            ->where('repetitions.user_id', $user->id)
            ->where('review_logs.created_at', '>=', $heatmapStart)
            ->selectRaw("to_char(review_logs.created_at, 'YYYY-MM-DD') AS day, COUNT(*) AS total")
            ->groupBy('day')
            ->get();

        $heatmapMap = [];
        foreach ($heatmapRows as $row) {
            /** @var \stdClass $row */
            $heatmapMap[(string) $row->day] = (int) $row->total;
        }

        $heatmap = [];
        for ($day = $heatmapStart->copy(); $day->lte($today); $day->addDay()) {
            $key = $day->toDateString();
            $heatmap[] = [
                'date' => $key,
                'count' => $heatmapMap[$key] ?? 0,
            ];
        }

        return [
            'streak' => [
                'current' => $user->streak_count,
                'last_studied_at' => $user->last_studied_at?->toIso8601String(),
                'daily_goal' => $user->daily_goal,
            ],
            'today' => [
                'reviewed' => (int) ($reviewStats->today_count ?? 0),
                'due_remaining' => (int) ($counts->due ?? 0),
            ],
            'totals' => [
                'questions' => (int) ($counts->questions ?? 0),
                'reviews' => (int) ($reviewStats->total ?? 0),
                'interviews' => (int) ($counts->interviews ?? 0),
            ],
            'retention' => [
                'rate_30d' => $retentionRate,
                'sample_size' => $sample,
            ],
            'heatmap' => $heatmap,
        ];
    }
}
