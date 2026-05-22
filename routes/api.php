<?php

declare(strict_types=1);

use App\Http\Controllers\Api\QuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/questions/generate', [QuestionController::class, 'generate'])
        ->name('api.questions.generate');
});
