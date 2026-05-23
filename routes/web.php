<?php

declare(strict_types=1);

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterviewPageController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudyController;
use App\Http\Middleware\EnsureUserIsOnboarded;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', static function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
});

Route::middleware('auth')->group(function () {
    // Onboarding flow (must work during onboarding).
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    // Mutating endpoints used inside the wizard — accessible regardless of onboarding state.
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/api-key', [ApiKeyController::class, 'store'])->name('settings.api-key.store');
    Route::delete('/settings/api-key', [ApiKeyController::class, 'destroy'])->name('settings.api-key.destroy');
});

Route::middleware(['auth', EnsureUserIsOnboarded::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('verified')
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');

    Route::get('/questions', [QuestionsController::class, 'index'])->name('questions.index');
    Route::get('/study', [StudyController::class, 'session'])->name('study.session');
    Route::get('/interview', [InterviewPageController::class, 'show'])->name('interview.show');
});

require __DIR__.'/auth.php';
