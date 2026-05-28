<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

test('questions page requires authentication', function () {
    $this->get('/questions')->assertRedirect('/login');
});

test('questions page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/questions')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Questions/Index'));
});

test('questions page shows only the users own questions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Question::factory()->count(3)->create(['user_id' => $user->id]);
    Question::factory()->count(2)->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->get('/questions')
        ->assertInertia(fn ($page) => $page
            ->component('Questions/Index')
            ->has('questions.data', 3)
        );
});

test('questions page passes has_api_key true when key is set', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('some-key'),
    ]);

    $this->actingAs($user)
        ->get('/questions')
        ->assertInertia(fn ($page) => $page->where('has_api_key', true));
});

test('questions page passes selected_tags and tag_suggestions props', function () {
    $user = User::factory()->create();
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => ['laravel', 'php']]);
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => ['vue.js']]);

    $this->actingAs($user)
        ->get('/questions?tags[]=laravel')
        ->assertInertia(fn ($page) => $page
            ->where('selected_tags', ['laravel'])
            ->has('tag_suggestions')
        );
});

test('questions page filters by single tag', function () {
    $user = User::factory()->create();
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => ['laravel', 'php']]);
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => ['vue.js']]);
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => []]);

    $this->actingAs($user)
        ->get('/questions?tags[]=laravel')
        ->assertInertia(fn ($page) => $page->has('questions.data', 1));
});

test('questions page filters by multiple tags using AND logic', function () {
    $user = User::factory()->create();
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => ['laravel', 'php']]);
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => ['laravel']]);
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => ['php']]);

    $this->actingAs($user)
        ->get('/questions?tags[]=laravel&tags[]=php')
        ->assertInertia(fn ($page) => $page->has('questions.data', 1));
});

test('questions page returns empty when no questions match all tags', function () {
    $user = User::factory()->create();
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => ['laravel']]);

    $this->actingAs($user)
        ->get('/questions?tags[]=laravel&tags[]=vue.js')
        ->assertInertia(fn ($page) => $page->has('questions.data', 0));
});

test('questions page with no tag filter returns all questions', function () {
    $user = User::factory()->create();
    Question::factory()->count(3)->create(['user_id' => $user->id, 'topic_tags' => ['laravel']]);
    Question::factory()->count(2)->create(['user_id' => $user->id, 'topic_tags' => []]);

    $this->actingAs($user)
        ->get('/questions')
        ->assertInertia(fn ($page) => $page->has('questions.data', 5));
});

test('questions page tag filter does not leak other users questions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Question::factory()->create(['user_id' => $other->id, 'topic_tags' => ['laravel']]);
    Question::factory()->create(['user_id' => $user->id, 'topic_tags' => ['laravel']]);

    $this->actingAs($user)
        ->get('/questions?tags[]=laravel')
        ->assertInertia(fn ($page) => $page->has('questions.data', 1));
});

test('study session page requires authentication', function () {
    $this->get('/study')->assertRedirect('/login');
});

test('study session page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/study')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Study/Session'));
});

test('study session page passes preferred difficulty', function () {
    $user = User::factory()->create(['preferred_difficulty' => Difficulty::Senior]);

    $this->actingAs($user)
        ->get('/study')
        ->assertInertia(fn ($page) => $page->where('preferred_difficulty', 'senior'));
});
