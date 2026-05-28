<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('onboarding route requires authentication', function () {
    $this->get('/onboarding')->assertRedirect(route('login'));
});

test('not-onboarded user is redirected from dashboard to onboarding', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('onboarding.show'));
});

test('not-onboarded user is redirected from feature pages to onboarding', function () {
    $user = User::factory()->notOnboarded()->create();

    foreach (['/questions', '/study', '/interview', '/settings', '/profile'] as $path) {
        $this->actingAs($user)
            ->get($path)
            ->assertRedirect(route('onboarding.show'));
    }
});

test('onboarding page renders for not-onboarded user', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->get('/onboarding')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Onboarding/Index')
            ->where('has_api_key', false)
            ->where('settings.preferred_difficulty', 'junior')
            ->where('settings.daily_goal', 10)
        );
});

test('already-onboarded user visiting /onboarding is redirected to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/onboarding')
        ->assertRedirect(route('dashboard'));
});

test('completing onboarding sets timestamp and redirects to dashboard', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)->get('/onboarding')->assertOk();

    $this->actingAs($user)
        ->post('/onboarding/complete')
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->onboarded_at)->not->toBeNull();
});

test('completing onboarding without viewing wizard redirects back to onboarding', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->post('/onboarding/complete')
        ->assertRedirect(route('onboarding.show'));

    expect($user->fresh()->onboarded_at)->toBeNull();
});

test('completing onboarding is idempotent', function () {
    $user = User::factory()->create([
        'onboarded_at' => now()->subDay(),
    ]);
    $original = $user->onboarded_at;

    $this->actingAs($user)
        ->post('/onboarding/complete')
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->onboarded_at?->toIso8601String())
        ->toBe($original->toIso8601String());
});

test('api key endpoint is reachable during onboarding', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->post('/settings/api-key', ['api_key' => str_repeat('a', 20)])
        ->assertRedirect(route('settings.edit'));

    expect($user->fresh()->gemini_api_key_encrypted)->not->toBeNull();
});

test('settings update endpoint is reachable during onboarding', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->patch('/settings', [
            'preferred_difficulty' => 'senior',
            'daily_goal' => 25,
            'theme' => 'dark',
        ])
        ->assertRedirect(route('settings.edit'));

    $fresh = $user->fresh();
    expect($fresh->preferred_difficulty->value)->toBe('senior');
    expect($fresh->daily_goal)->toBe(25);
});

test('onboarded user can visit feature pages normally', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
    $this->actingAs($user)->get('/settings')->assertOk();
    $this->actingAs($user)->get('/questions')->assertOk();
});
