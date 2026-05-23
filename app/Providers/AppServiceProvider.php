<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('interview-start', static function (Request $request): Limit {
            $user = $request->user();
            assert($user !== null);

            return Limit::perMinute(3)->by((string) $user->id);
        });

        RateLimiter::for('interview-message', static function (Request $request): Limit {
            $user = $request->user();
            assert($user !== null);

            return Limit::perMinute(20)->by((string) $user->id);
        });

        RateLimiter::for('question-generate', static function (Request $request): Limit {
            $user = $request->user();
            assert($user !== null);

            return Limit::perMinute(10)->by((string) $user->id);
        });
    }
}
