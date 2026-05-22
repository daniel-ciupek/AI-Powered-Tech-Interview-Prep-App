<?php

declare(strict_types=1);

namespace App\Services\Gemini;

use App\Models\AiResponseCache;

class AiCacheService
{
    public const TTL_DAYS = 7;

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $prompt): ?array
    {
        $cached = AiResponseCache::where('prompt_hash', $this->hash($prompt))
            ->where('created_at', '>=', now()->subDays(self::TTL_DAYS))
            ->first();

        return $cached?->response;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function put(string $prompt, array $validated, int $tokensIn, int $tokensOut, string $model): void
    {
        AiResponseCache::updateOrCreate(
            ['prompt_hash' => $this->hash($prompt)],
            [
                'response' => $validated,
                'model' => $model,
                'tokens_in' => $tokensIn,
                'tokens_out' => $tokensOut,
            ]
        );
    }

    public function pruneExpired(): int
    {
        return AiResponseCache::where('created_at', '<', now()->subDays(self::TTL_DAYS))->delete();
    }

    private function hash(string $prompt): string
    {
        return hash('sha256', $prompt);
    }
}
