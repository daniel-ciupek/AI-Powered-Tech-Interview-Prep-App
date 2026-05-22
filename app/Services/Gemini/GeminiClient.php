<?php

declare(strict_types=1);

namespace App\Services\Gemini;

use App\Exceptions\GeminiApiException;
use App\Exceptions\GeminiRateLimitException;
use Illuminate\Support\Facades\Http;

class GeminiClient
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    private const MAX_RETRIES = 3;

    private const RETRYABLE_STATUSES = [429, 500, 502, 503];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gemini-2.0-flash',
        private readonly int $retryBaseMs = 1000,
    ) {}

    /**
     * @return array{text: string, tokens_in: int, tokens_out: int}
     */
    public function generate(string $prompt, int $maxOutputTokens = 1024): array
    {
        $url = self::BASE_URL."/{$this->model}:generateContent";

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'maxOutputTokens' => $maxOutputTokens,
                'temperature' => 0.7,
            ],
        ];

        $lastException = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0 && $this->retryBaseMs > 0) {
                usleep($this->retryBaseMs * 1000 * (2 ** ($attempt - 1)));
            }

            $response = Http::withQueryParameters(['key' => $this->apiKey])
                ->timeout(30)
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (! is_array($data)) {
                    throw new GeminiApiException('Gemini API returned non-JSON response');
                }

                return $this->parseResponse($data);
            }

            if (in_array($response->status(), self::RETRYABLE_STATUSES, strict: true)) {
                $lastException = $response->status() === 429
                    ? new GeminiRateLimitException('Gemini API rate limit exceeded after retries')
                    : new GeminiApiException("Gemini server error: {$response->status()}");

                continue;
            }

            throw new GeminiApiException("Gemini API error {$response->status()}: ".$response->body());
        }

        throw $lastException ?? new GeminiApiException('Max retries exceeded');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{text: string, tokens_in: int, tokens_out: int}
     */
    private function parseResponse(array $data): array
    {
        if (! isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new GeminiApiException('Unexpected Gemini API response structure');
        }

        return [
            'text' => (string) $data['candidates'][0]['content']['parts'][0]['text'],
            'tokens_in' => (int) ($data['usageMetadata']['promptTokenCount'] ?? 0),
            'tokens_out' => (int) ($data['usageMetadata']['candidatesTokenCount'] ?? 0),
        ];
    }
}
