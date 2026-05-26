<?php

declare(strict_types=1);

use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

test('tags endpoint requires authentication', function () {
    $this->getJson('/api/tags')->assertStatus(401);
});

test('tags endpoint returns most-used topic tags from own interview sessions', function () {
    $user = User::factory()->create();
    InterviewSession::factory()->create(['user_id' => $user->id, 'topic_tags' => ['laravel', 'php']]);
    InterviewSession::factory()->create(['user_id' => $user->id, 'topic_tags' => ['laravel', 'vue.js']]);
    InterviewSession::factory()->create(['user_id' => $user->id, 'topic_tags' => ['php']]);

    $response = $this->actingAs($user)->getJson('/api/tags')->assertOk();

    $data = $response->json('data');

    expect($data)->toBeArray();
    expect($data[0])->toBe('laravel');
    expect($data[1])->toBe('php');
    expect($data)->toContain('vue.js');
});

test('tags endpoint does not return other users sessions', function () {
    $other = User::factory()->create();
    InterviewSession::factory()->create(['user_id' => $other->id, 'topic_tags' => ['laravel']]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/tags')->assertOk();

    expect($response->json('data'))->toBe([]);
});

test('tags endpoint normalises casing and ignores empty entries', function () {
    $user = User::factory()->create();
    InterviewSession::factory()->create(['user_id' => $user->id, 'topic_tags' => ['Laravel', 'LARAVEL', '']]);

    $response = $this->actingAs($user)->getJson('/api/tags')->assertOk();
    $data = $response->json('data');

    expect($data)->toBe(['laravel']);
});

test('tags endpoint returns empty array when no sessions exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/tags')->assertOk();

    expect($response->json('data'))->toBe([]);
});

test('tags endpoint caches results for repeated calls', function () {
    $user = User::factory()->create();
    InterviewSession::factory()->create(['user_id' => $user->id, 'topic_tags' => ['cached']]);

    $first = $this->actingAs($user)->getJson('/api/tags')->json('data');
    expect($first)->toBe(['cached']);

    InterviewSession::factory()->create(['user_id' => $user->id, 'topic_tags' => ['fresh']]);

    $second = $this->actingAs($user)->getJson('/api/tags')->json('data');
    expect($second)->toBe(['cached']);

    Cache::forget("tags.suggest.top30.{$user->id}");

    $third = $this->actingAs($user)->getJson('/api/tags')->json('data');
    expect($third)->toContain('cached');
    expect($third)->toContain('fresh');
});
