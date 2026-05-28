<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Question;
use App\Models\Repetition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repetition>
 */
class RepetitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'ease_factor' => 2.50,
            'interval_days' => 0,
            'repetitions_count' => 0,
            'quality_last' => null,
            'next_review_at' => now(),
            'last_reviewed_at' => null,
        ];
    }

    public function due(): static
    {
        return $this->state(['next_review_at' => now()->subDay()]);
    }

    public function future(): static
    {
        return $this->state(['next_review_at' => now()->addDays(3)]);
    }
}
