<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\Repetition;
use App\Models\User;

test('study today endpoint requires authentication', function () {
    $this->getJson('/api/study/today')->assertStatus(401);
});

test('study today returns only due repetitions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    // Due for current user
    $q1 = Question::factory()->create(['user_id' => $user->id]);
    $q2 = Question::factory()->create(['user_id' => $user->id]);
    Repetition::factory()->due()->create(['user_id' => $user->id, 'question_id' => $q1->id]);
    Repetition::factory()->due()->create(['user_id' => $user->id, 'question_id' => $q2->id]);

    // Future (not due)
    $q3 = Question::factory()->create(['user_id' => $user->id]);
    Repetition::factory()->future()->create(['user_id' => $user->id, 'question_id' => $q3->id]);

    // Another user's due
    $q4 = Question::factory()->create(['user_id' => $other->id]);
    Repetition::factory()->due()->create(['user_id' => $other->id, 'question_id' => $q4->id]);

    $response = $this->actingAs($user)->getJson('/api/study/today')->assertOk();

    expect($response->json('count'))->toBe(2);
});

test('study today returns question data with repetition id', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);
    Repetition::factory()->due()->create(['user_id' => $user->id, 'question_id' => $question->id]);

    $response = $this->actingAs($user)->getJson('/api/study/today')->assertOk();

    $response->assertJsonStructure([
        'data' => [['repetition_id', 'question']],
        'count',
    ]);
});

test('study today returns empty when no due cards', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/study/today')->assertOk();

    expect($response->json('count'))->toBe(0);
});
