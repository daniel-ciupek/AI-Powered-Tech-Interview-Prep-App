<?php

declare(strict_types=1);

use App\Exceptions\GeminiApiException;
use App\Exceptions\GeminiRateLimitException;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\Http;

function geminiSuccessResponse(string $text = 'Generated answer', int $tokensIn = 10, int $tokensOut = 20): array
{
    return [
        'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
        'usageMetadata' => [
            'promptTokenCount' => $tokensIn,
            'candidatesTokenCount' => $tokensOut,
        ],
    ];
}

test('generate returns parsed text and token counts', function () {
    Http::fake(['*' => Http::response(geminiSuccessResponse('Test answer', 15, 30), 200)]);

    $client = new GeminiClient('fake-key');
    $result = $client->generate('Write a test question');

    expect($result['text'])->toBe('Test answer')
        ->and($result['tokens_in'])->toBe(15)
        ->and($result['tokens_out'])->toBe(30);
});

test('generate throws GeminiRateLimitException after exhausting retries on 429', function () {
    Http::fake(['*' => Http::response('Rate limited', 429)]);

    $client = new GeminiClient('fake-key', retryBaseMs: 0);

    expect(fn () => $client->generate('prompt'))->toThrow(GeminiRateLimitException::class);
});

test('generate throws GeminiApiException on 401 unauthorized', function () {
    Http::fake(['*' => Http::response('Unauthorized', 401)]);

    $client = new GeminiClient('fake-key');

    expect(fn () => $client->generate('prompt'))->toThrow(GeminiApiException::class);
});

test('generate throws GeminiApiException on 400 bad request', function () {
    Http::fake(['*' => Http::response('Bad request', 400)]);

    $client = new GeminiClient('fake-key');

    expect(fn () => $client->generate('prompt'))->toThrow(GeminiApiException::class);
});

test('generate retries on 500 and succeeds on second attempt', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push('Server Error', 500)
            ->push(geminiSuccessResponse('Retry success'), 200),
    ]);

    $client = new GeminiClient('fake-key', retryBaseMs: 0);
    $result = $client->generate('prompt');

    expect($result['text'])->toBe('Retry success');
});

test('generate throws GeminiApiException when response structure is unexpected', function () {
    Http::fake(['*' => Http::response(['unexpected' => 'format'], 200)]);

    $client = new GeminiClient('fake-key');

    expect(fn () => $client->generate('prompt'))->toThrow(GeminiApiException::class);
});

test('generate sends prompt to correct Gemini API endpoint', function () {
    Http::fake(['*' => Http::response(geminiSuccessResponse(), 200)]);

    $client = new GeminiClient('my-api-key');
    $client->generate('Test prompt');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'generativelanguage.googleapis.com')
            && str_contains($request->url(), 'gemini-2.5-flash')
            && ! str_contains($request->url(), 'my-api-key')
            && $request->header('x-goog-api-key') === ['my-api-key'];
    });
});
