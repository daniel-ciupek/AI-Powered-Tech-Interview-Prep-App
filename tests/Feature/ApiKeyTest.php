<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Crypt;

test('api key endpoint requires authentication', function () {
    $this->post('/settings/api-key', ['api_key' => 'test-key-12345'])->assertRedirect('/login');
    $this->delete('/settings/api-key')->assertRedirect('/login');
});

test('api key can be saved and is encrypted at rest', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/settings/api-key', ['api_key' => 'fake-gemini-key-12345'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings');

    $user->refresh();

    expect($user->gemini_api_key_encrypted)->not->toBeNull()
        ->and($user->gemini_api_key_encrypted)->not->toBe('fake-gemini-key-12345');

    expect(Crypt::decryptString($user->gemini_api_key_encrypted))->toBe('fake-gemini-key-12345');
});

test('api key is never exposed in settings page response', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('fake-gemini-secret-key'),
    ]);

    $response = $this->actingAs($user)->get('/settings');

    $response->assertOk();
    $response->assertDontSee('fake-gemini-secret-key');
    $response->assertDontSee($user->gemini_api_key_encrypted);
});

test('settings page shows has_api_key true when key is set', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('some-key'),
    ]);

    $this->actingAs($user)
        ->get('/settings')
        ->assertInertia(fn ($page) => $page->where('has_api_key', true));
});

test('settings page shows has_api_key false when key is not set', function () {
    $user = User::factory()->create(['gemini_api_key_encrypted' => null]);

    $this->actingAs($user)
        ->get('/settings')
        ->assertInertia(fn ($page) => $page->where('has_api_key', false));
});

test('api key can be removed', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('some-key'),
    ]);

    $this->actingAs($user)
        ->delete('/settings/api-key')
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings');

    expect($user->refresh()->gemini_api_key_encrypted)->toBeNull();
});

test('api key validation rejects short keys', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/settings/api-key', ['api_key' => 'short'])
        ->assertSessionHasErrors('api_key');
});
