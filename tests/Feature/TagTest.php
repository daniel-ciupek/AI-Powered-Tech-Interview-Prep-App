<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Tags\Tag;

test('tags endpoint requires authentication', function () {
    $this->getJson('/api/tags')->assertStatus(401);
});

test('tags endpoint returns all tags sorted alphabetically', function () {
    Tag::findOrCreate('Vue.js');
    Tag::findOrCreate('Laravel');
    Tag::findOrCreate('PHP');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/tags');

    $response->assertOk()->assertJsonStructure(['data']);

    $data = $response->json('data');
    expect($data)->toBeArray()->not->toBeEmpty();

    $sorted = $data;
    sort($sorted);
    expect($data)->toBe($sorted);
});

test('tags endpoint returns string values not objects', function () {
    Tag::findOrCreate('Docker');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/tags');
    $data = $response->json('data');

    expect($data[0])->toBeString();
});
