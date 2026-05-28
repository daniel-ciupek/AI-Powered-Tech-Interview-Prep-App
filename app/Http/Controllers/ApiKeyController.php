<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Users\SaveApiKeyAction;
use App\Http\Requests\SaveApiKeyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ApiKeyController extends Controller
{
    public function store(SaveApiKeyRequest $request, SaveApiKeyAction $action): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $action($user, $request->validated('api_key'));

        return Redirect::route('settings.edit')->with('status', 'api-key-saved');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $user->gemini_api_key_encrypted = null;
        $user->save();

        return Redirect::route('settings.edit')->with('status', 'api-key-removed');
    }
}
