<?php

declare(strict_types=1);

use App\Models\User;

test('root redirects guests to login', function () {
    $this->get('/')
        ->assertRedirect(route('login'));
});

test('root redirects authenticated users to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
