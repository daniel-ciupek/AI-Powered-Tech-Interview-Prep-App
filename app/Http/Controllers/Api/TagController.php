<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Tags\Tag;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var list<string> $tags */
        $tags = Tag::all()
            ->map(static fn (Tag $tag): string => $tag->getTranslation('name', 'en'))
            ->sort()
            ->values()
            ->all();

        return response()->json(['data' => $tags]);
    }
}
