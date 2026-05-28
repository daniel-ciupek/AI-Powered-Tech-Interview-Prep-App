<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Enums\Theme;
use App\Models\User;

test('settings page requires authentication', function () {
    $this->get('/settings')->assertRedirect('/login');
});

test('settings page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertOk();
});

test('settings can be updated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch('/settings', [
        'preferred_difficulty' => 'senior',
        'daily_goal' => 20,
        'theme' => 'dark',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/settings');

    $user->refresh();

    expect($user->preferred_difficulty)->toBe(Difficulty::Senior)
        ->and($user->daily_goal)->toBe(20)
        ->and($user->theme)->toBe(Theme::Dark);
});

test('settings validation rejects invalid difficulty', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings', [
            'preferred_difficulty' => 'expert',
            'daily_goal' => 10,
            'theme' => 'system',
        ])
        ->assertSessionHasErrors('preferred_difficulty');
});

test('settings validation rejects daily goal out of range', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings', [
            'preferred_difficulty' => 'junior',
            'daily_goal' => 0,
            'theme' => 'system',
        ])
        ->assertSessionHasErrors('daily_goal');
});

test('settings validation rejects invalid theme', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings', [
            'preferred_difficulty' => 'junior',
            'daily_goal' => 10,
            'theme' => 'solarized',
        ])
        ->assertSessionHasErrors('theme');
});
