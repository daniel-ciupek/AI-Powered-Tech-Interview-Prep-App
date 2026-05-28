<?php

declare(strict_types=1);

namespace App\Services\Gemini;

use App\Models\AiResponseCache;
use App\Models\User;

class AiCacheService
{
    public const TTL_DAYS = 7;

    /**
     * @return array<string, mixed>|null
     */
    public function get(User $user, string $prompt): ?array
    {
        $cached = AiResponseCache::query()
            ->where('user_id', $user->id)
            ->where('prompt_hash', $this->hash($prompt))
            ->where('created_at', '>=', now()->subDays(self::TTL_DAYS))
            ->first();

        return $cached?->response;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function put(User $user, string $prompt, array $validated, int $tokensIn, int $tokensOut, string $model): void
    {
        AiResponseCache::updateOrCreate(
            ['user_id' => $user->id, 'prompt_hash' => $this->hash($prompt)],
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
        return AiResponseCache::query()
            ->where('created_at', '<', now()->subDays(self::TTL_DAYS))
            ->delete();
    }

    private function hash(string $prompt): string
    {
        return hash('sha256', $prompt);
    }
}
