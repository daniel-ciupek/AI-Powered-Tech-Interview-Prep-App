<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\QuestionResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuestionsController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var list<string> $selectedTags */
        $selectedTags = array_values(
            array_filter(
                array_map('strval', (array) $request->input('tags', [])),
                static fn (string $t): bool => $t !== '',
            )
        );

        $query = $user->questions()->latest();

        foreach ($selectedTags as $tag) {
            $query->whereRaw('topic_tags @> ?::jsonb', [json_encode([$tag])]);
        }

        /** @var list<\stdClass&object{tag: string}> $rows */
        $rows = DB::select(<<<'SQL'
            SELECT lower(elem) AS tag, COUNT(*) AS cnt
            FROM questions, jsonb_array_elements_text(topic_tags) AS elem
            WHERE elem <> ''
            AND user_id = :userId
            GROUP BY lower(elem)
            ORDER BY cnt DESC, tag ASC
            LIMIT 30
        SQL, ['userId' => $user->id]);

        /** @var list<string> $tagSuggestions */
        $tagSuggestions = array_map(static fn (\stdClass $r): string => (string) $r->tag, $rows);

        return Inertia::render('Questions/Index', [
            'questions' => QuestionResource::collection(
                $query->paginate(10)->withQueryString()
            ),
            'has_api_key' => $user->gemini_api_key_encrypted !== null,
            'preferred_difficulty' => $user->preferred_difficulty->value,
            'selected_tags' => $selectedTags,
            'tag_suggestions' => $tagSuggestions,
        ]);
    }
}
