<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    private const CACHE_TTL_SECONDS = 600;

    private const LIMIT = 30;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        /** @var list<string> $tags */
        $tags = Cache::remember(
            "tags.suggest.top30.{$userId}",
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->loadTopTags($userId),
        );

        return response()->json(['data' => $tags]);
    }

    /**
     * @return list<string>
     */
    private function loadTopTags(int $userId): array
    {
        /** @var list<\stdClass&object{tag: string}> $rows */
        $rows = DB::select(<<<'SQL'
            SELECT lower(elem) AS tag, COUNT(*) AS usage_count
            FROM interview_sessions, jsonb_array_elements_text(topic_tags) AS elem
            WHERE elem <> ''
            AND user_id = :userId
            GROUP BY lower(elem)
            ORDER BY usage_count DESC, tag ASC
            LIMIT :limit
        SQL, ['userId' => $userId, 'limit' => self::LIMIT]);

        return array_map(static fn (\stdClass $row): string => (string) $row->tag, $rows);
    }
}
