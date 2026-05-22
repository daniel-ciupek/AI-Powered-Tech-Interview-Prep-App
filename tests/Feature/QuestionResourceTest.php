<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;

test('question resource contains expected fields', function () {
    $question = Question::factory()->create([
        'user_id' => User::factory()->create()->id,
        'content' => 'What is polymorphism?',
        'expected_answer' => 'A design principle...',
        'expected_keywords' => ['inheritance', 'interface', 'override'],
        'difficulty' => Difficulty::Mid,
        'source' => QuestionSource::AiGenerated,
    ]);

    $resource = (new QuestionResource($question))->toArray(new Request);

    expect($resource)->toHaveKeys(['id', 'content', 'expected_answer', 'expected_keywords', 'difficulty', 'source', 'created_at'])
        ->and($resource['content'])->toBe('What is polymorphism?')
        ->and($resource['difficulty'])->toBe('mid')
        ->and($resource['source'])->toBe('ai_generated')
        ->and($resource['expected_keywords'])->toBe(['inheritance', 'interface', 'override']);
});

test('question resource exposes enum string values not objects', function () {
    $question = Question::factory()->create(['difficulty' => Difficulty::Senior]);

    $resource = (new QuestionResource($question))->toArray(new Request);

    expect($resource['difficulty'])->toBeString()->toBe('senior');
});

test('question resource does not expose user_id', function () {
    $question = Question::factory()->create();

    $resource = (new QuestionResource($question))->toArray(new Request);

    expect($resource)->not->toHaveKey('user_id');
});
