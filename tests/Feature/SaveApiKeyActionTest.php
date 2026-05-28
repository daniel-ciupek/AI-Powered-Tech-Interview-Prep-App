<?php

declare(strict_types=1);

use App\Actions\Users\SaveApiKeyAction;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

test('save api key action encrypts the key before storing', function () {
    $user = User::factory()->create();
    $action = new SaveApiKeyAction;

    $action($user, 'fake-gemini-key-12345');

    $user->refresh();

    expect($user->gemini_api_key_encrypted)->not->toBeNull()
        ->and($user->gemini_api_key_encrypted)->not->toBe('fake-gemini-key-12345');
});

test('save api key action stores a key that can be decrypted back', function () {
    $user = User::factory()->create();
    $action = new SaveApiKeyAction;

    $action($user, 'fake-gemini-roundtrip-key');

    $user->refresh();

    expect(Crypt::decryptString($user->gemini_api_key_encrypted))->toBe('fake-gemini-roundtrip-key');
});

test('save api key action replaces existing key', function () {
    $user = User::factory()->create([
        'gemini_api_key_encrypted' => Crypt::encryptString('old-key'),
    ]);
    $action = new SaveApiKeyAction;

    $action($user, 'new-key-12345678');

    $user->refresh();

    expect(Crypt::decryptString($user->gemini_api_key_encrypted))->toBe('new-key-12345678');
});
