<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InterviewPageController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);

        return Inertia::render('Interview/Show', [
            'has_api_key' => $user->gemini_api_key_encrypted !== null,
            'preferred_difficulty' => $user->preferred_difficulty->value,
        ]);
    }
}
