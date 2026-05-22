<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);

        return Inertia::render('Settings/Index', [
            'settings' => [
                'preferred_difficulty' => $user->preferred_difficulty->value,
                'daily_goal' => $user->daily_goal,
                'theme' => $user->theme->value,
            ],
            'status' => session('status'),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $user->fill($request->validated())->save();

        return Redirect::route('settings.edit')->with('status', 'settings-updated');
    }
}
