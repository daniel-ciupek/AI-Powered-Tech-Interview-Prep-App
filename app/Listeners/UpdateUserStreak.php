<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuestionReviewed;

class UpdateUserStreak
{
    public function handle(QuestionReviewed $event): void
    {
        $question = $event->repetition->question;
        assert($question !== null);

        $user = $question->user;
        assert($user !== null);

        $lastStudied = $user->last_studied_at;
        $today = now()->startOfDay();

        $streakBroken = $lastStudied !== null && $lastStudied->startOfDay()->lt($today->copy()->subDay());

        $user->streak_count = $streakBroken ? 1 : $user->streak_count + 1;
        $user->last_studied_at = now();
        $user->save();
    }
}
