<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        if ($user->onboarded_at !== null) {
            return Redirect::route('dashboard');
        }

        return Inertia::render('Onboarding/Index', [
            'has_api_key' => $user->gemini_api_key_encrypted !== null,
            'settings' => [
                'preferred_difficulty' => $user->preferred_difficulty->value,
                'daily_goal' => $user->daily_goal,
            ],
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        if ($user->onboarded_at === null) {
            $user->onboarded_at = now();
            $user->save();
        }

        return Redirect::route('dashboard');
    }
}
