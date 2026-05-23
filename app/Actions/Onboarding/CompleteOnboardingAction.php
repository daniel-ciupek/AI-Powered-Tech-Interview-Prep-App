<?php

declare(strict_types=1);

namespace App\Actions\Onboarding;

use App\Models\User;

final class CompleteOnboardingAction
{
    public function __invoke(User $user): void
    {
        if ($user->onboarded_at !== null) {
            return;
        }

        $user->onboarded_at = now();
        $user->save();
    }
}
