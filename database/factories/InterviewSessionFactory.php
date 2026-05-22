<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewSession>
 */
class InterviewSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'topic_tags' => ['PHP', 'Laravel'],
            'difficulty' => Difficulty::Junior,
            'status' => SessionStatus::Active,
            'final_report' => null,
            'tokens_used_total' => 0,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => SessionStatus::Completed,
            'ended_at' => now(),
        ]);
    }

    public function abandoned(): static
    {
        return $this->state([
            'status' => SessionStatus::Abandoned,
            'ended_at' => now(),
        ]);
    }
}
