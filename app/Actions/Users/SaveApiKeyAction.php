<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class SaveApiKeyAction
{
    public function __invoke(User $user, string $apiKey): void
    {
        $user->gemini_api_key_encrypted = Crypt::encryptString($apiKey);
        $user->save();
    }
}
