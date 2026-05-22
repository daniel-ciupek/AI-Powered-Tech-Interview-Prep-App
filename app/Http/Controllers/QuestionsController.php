<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\QuestionResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);

        return Inertia::render('Questions/Index', [
            'questions' => QuestionResource::collection(
                $user->questions()->latest()->paginate(10)
            ),
            'has_api_key' => $user->gemini_api_key_encrypted !== null,
        ]);
    }
}
