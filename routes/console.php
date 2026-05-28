<?php

declare(strict_types=1);

use App\Services\Gemini\AiCacheService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(static function (): void {
    app(AiCacheService::class)->pruneExpired();
})->daily()->name('prune-ai-cache')->withoutOverlapping();
