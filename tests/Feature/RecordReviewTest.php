<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\Repetition;
use App\Models\User;

test('review endpoint requires authentication', function () {
    $repetition = Repetition::factory()->create();

    $this->postJson("/api/repetitions/{$repetition->id}/review", ['quality' => 4])
        ->assertStatus(401);
});

test('user cannot review another users repetition', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $question = Question::factory()->create(['user_id' => $owner->id]);
    $repetition = Repetition::factory()->create(['user_id' => $owner->id, 'question_id' => $question->id]);

    $this->actingAs($other)
        ->postJson("/api/repetitions/{$repetition->id}/review", ['quality' => 4])
        ->assertStatus(403);
});

test('successful review updates repetition values', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);
    $repetition = Repetition::factory()->create([
        'user_id' => $user->id,
        'question_id' => $question->id,
        'ease_factor' => 2.50,
        'interval_days' => 1,
        'repetitions_count' => 1,
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/repetitions/{$repetition->id}/review", ['quality' => 4])
        ->assertOk();

    $response->assertJsonStructure([
        'data' => ['id', 'ease_factor', 'interval_days', 'repetitions_count', 'next_review_at'],
    ]);

    $repetition->refresh();
    expect($repetition->repetitions_count)->toBe(2)
        ->and($repetition->interval_days)->toBe(6)
        ->and($repetition->quality_last)->toBe(4);
});

test('review creates an audit log entry', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);
    $repetition = Repetition::factory()->create(['user_id' => $user->id, 'question_id' => $question->id]);

    $this->actingAs($user)
        ->postJson("/api/repetitions/{$repetition->id}/review", ['quality' => 2])
        ->assertOk();

    $this->assertDatabaseHas('review_logs', [
        'repetition_id' => $repetition->id,
        'quality' => 2,
    ]);
});

test('failed review resets repetition count to zero', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);
    $repetition = Repetition::factory()->create([
        'user_id' => $user->id,
        'question_id' => $question->id,
        'repetitions_count' => 5,
    ]);

    $this->actingAs($user)
        ->postJson("/api/repetitions/{$repetition->id}/review", ['quality' => 2]);

    expect($repetition->refresh()->repetitions_count)->toBe(0);
});

test('review validates quality is between 0 and 5', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);
    $repetition = Repetition::factory()->create(['user_id' => $user->id, 'question_id' => $question->id]);

    $this->actingAs($user)
        ->postJson("/api/repetitions/{$repetition->id}/review", ['quality' => 6])
        ->assertStatus(422);
});

test('review event updates user streak', function () {
    $user = User::factory()->create(['streak_count' => 0, 'last_studied_at' => null]);
    $question = Question::factory()->create(['user_id' => $user->id]);
    $repetition = Repetition::factory()->create(['user_id' => $user->id, 'question_id' => $question->id]);

    $this->actingAs($user)
        ->postJson("/api/repetitions/{$repetition->id}/review", ['quality' => 4]);

    expect($user->refresh()->streak_count)->toBe(1);
});
