<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Onboarding\CompleteOnboardingAction;
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

        $request->session()->put('onboarding.viewed', true);

        return Inertia::render('Onboarding/Index', [
            'has_api_key' => $user->gemini_api_key_encrypted !== null,
            'settings' => [
                'preferred_difficulty' => $user->preferred_difficulty->value,
                'daily_goal' => $user->daily_goal,
            ],
        ]);
    }

    public function complete(Request $request, CompleteOnboardingAction $action): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        if ($user->onboarded_at === null && ! $request->session()->pull('onboarding.viewed', false)) {
            return Redirect::route('onboarding.show');
        }

        $action($user);

        return Redirect::route('dashboard');
    }
}
