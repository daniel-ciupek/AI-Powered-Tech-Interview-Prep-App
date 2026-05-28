<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => rtrim($this->faker->sentence(10), '.').'?',
            'expected_answer' => $this->faker->paragraph(),
            'expected_keywords' => $this->faker->words(3),
            'difficulty' => $this->faker->randomElement(Difficulty::cases()),
            'source' => QuestionSource::AiGenerated,
        ];
    }

    public function userCreated(): static
    {
        return $this->state(['source' => QuestionSource::UserCreated]);
    }

    public function forDifficulty(Difficulty $difficulty): static
    {
        return $this->state(['difficulty' => $difficulty]);
    }
}
