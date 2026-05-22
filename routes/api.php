<?php

declare(strict_types=1);

use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/tags', [TagController::class, 'index'])->name('api.tags.index');
    Route::post('/questions/generate', [QuestionController::class, 'generate'])
        ->name('api.questions.generate');
});
