<?php

declare(strict_types=1);

use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\QuestionChatController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\RepetitionController;
use App\Http\Controllers\Api\StudySessionController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/tags', [TagController::class, 'index'])->name('api.tags.index');
    Route::post('/questions/generate', [QuestionController::class, 'generate'])
        ->middleware('throttle:question-generate')
        ->name('api.questions.generate');
    Route::post('/questions/{question}/chat', QuestionChatController::class)
        ->middleware('throttle:question-chat')
        ->name('api.questions.chat');
    Route::post('/repetitions/{repetition}/review', [RepetitionController::class, 'review'])
        ->name('api.repetitions.review');
    Route::get('/study/today', [StudySessionController::class, 'today'])
        ->name('api.study.today');

    Route::get('/interview/{interviewSession}', [InterviewController::class, 'show'])
        ->name('api.interview.show');
    Route::post('/interview/start', [InterviewController::class, 'start'])
        ->middleware('throttle:interview-start')
        ->name('api.interview.start');
    Route::post('/interview/{interviewSession}/message', [InterviewController::class, 'message'])
        ->middleware('throttle:interview-message')
        ->name('api.interview.message');
    Route::post('/interview/{interviewSession}/retry-report', [InterviewController::class, 'retryReport'])
        ->middleware('throttle:interview-finish')
        ->name('api.interview.retry-report');
    Route::post('/interview/{interviewSession}/finish', [InterviewController::class, 'finish'])
        ->middleware('throttle:interview-finish')
        ->name('api.interview.finish');
});
