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

        $todayReviewed = DB::table('review_logs')
            ->join('repetitions', 'review_logs.repetition_id', '=', 'repetitions.id')
            ->where('repetitions.user_id', $user->id)
            ->whereDate('review_logs.created_at', $today)
            ->count();

        $dueRemaining = $user->repetitions()
            ->where('next_review_at', '<=', now())
            ->count();

        $reviewsTotal = DB::table('review_logs')
            ->join('repetitions', 'review_logs.repetition_id', '=', 'repetitions.id')
            ->where('repetitions.user_id', $user->id)
            ->count();

        $retentionRow = DB::table('review_logs')
            ->join('repetitions', 'review_logs.repetition_id', '=', 'repetitions.id')
            ->where('repetitions.user_id', $user->id)
            ->where('review_logs.created_at', '>=', now()->subDays(self::RETENTION_WINDOW_DAYS))
            ->selectRaw('COUNT(*) AS total, SUM(CASE WHEN quality >= 3 THEN 1 ELSE 0 END) AS hits')
            ->first();

        $sample = (int) ($retentionRow->total ?? 0);
        $hits = (int) ($retentionRow->hits ?? 0);
        $retentionRate = $sample > 0 ? round($hits / $sample, 4) : null;

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
                'reviewed' => $todayReviewed,
                'due_remaining' => $dueRemaining,
            ],
            'totals' => [
                'questions' => $user->questions()->count(),
                'reviews' => $reviewsTotal,
                'interviews' => $user->interviewSessions()->count(),
            ],
            'retention' => [
                'rate_30d' => $retentionRate,
                'sample_size' => $sample,
            ],
            'heatmap' => $heatmap,
        ];
    }
}
