<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\User;

test('user can view their own question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $question))->toBeTrue();
});

test('user cannot view another users question', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $owner->id]);

    expect($other->can('view', $question))->toBeFalse();
});

test('user can update their own question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);

    expect($user->can('update', $question))->toBeTrue();
});

test('user cannot update another users question', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $owner->id]);

    expect($other->can('update', $question))->toBeFalse();
});

test('user can delete their own question', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);

    expect($user->can('delete', $question))->toBeTrue();
});

test('user cannot delete another users question', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $owner->id]);

    expect($other->can('delete', $question))->toBeFalse();
});

test('any authenticated user can create questions', function () {
    $user = User::factory()->create();

    expect($user->can('create', Question::class))->toBeTrue();
});
