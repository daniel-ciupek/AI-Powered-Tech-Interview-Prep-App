<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    private const CACHE_KEY = 'tags.suggest.top30';

    private const CACHE_TTL_SECONDS = 600;

    private const LIMIT = 30;

    public function index(): JsonResponse
    {
        /** @var list<string> $tags */
        $tags = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            $this->loadTopTags(...),
        );

        return response()->json(['data' => $tags]);
    }

    /**
     * @return list<string>
     */
    private function loadTopTags(): array
    {
        /** @var list<\stdClass&object{tag: string}> $rows */
        $rows = DB::select(<<<'SQL'
            SELECT lower(elem) AS tag, COUNT(*) AS usage_count
            FROM interview_sessions, jsonb_array_elements_text(topic_tags) AS elem
            WHERE elem <> ''
            GROUP BY lower(elem)
            ORDER BY usage_count DESC, tag ASC
            LIMIT :limit
        SQL, ['limit' => self::LIMIT]);

        return array_map(static fn (\stdClass $row): string => (string) $row->tag, $rows);
    }
}
