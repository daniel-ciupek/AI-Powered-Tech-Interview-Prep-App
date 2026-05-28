<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudyController extends Controller
{
    public function session(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);

        return Inertia::render('Study/Session', [
            'preferred_difficulty' => $user->preferred_difficulty->value,
        ]);
    }
}
