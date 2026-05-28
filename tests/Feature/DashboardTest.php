<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\Repetition;
use App\Models\ReviewLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

test('dashboard requires authentication', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('dashboard renders for authenticated user with stats payload', function () {
    $user = User::factory()->create([
        'streak_count' => 4,
        'daily_goal' => 10,
        'last_studied_at' => now()->subHours(2),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('stats.streak', fn (AssertableInertia $streak) => $streak
                ->where('current', 4)
                ->where('daily_goal', 10)
                ->where('last_studied_at', fn ($v) => is_string($v))
            )
            ->has('stats.today.reviewed')
            ->has('stats.today.due_remaining')
            ->has('stats.totals.questions')
            ->has('stats.totals.reviews')
            ->has('stats.totals.interviews')
            ->has('stats.retention.rate_30d')
            ->has('stats.retention.sample_size')
            ->has('stats.heatmap', 84)
            ->has('has_api_key')
        );
});

test('dashboard counts only current user reviews in totals', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $question = Question::factory()->create(['user_id' => $user->id]);
    $rep = Repetition::factory()->create([
        'user_id' => $user->id,
        'question_id' => $question->id,
    ]);
    ReviewLog::create([
        'repetition_id' => $rep->id,
        'quality' => 5,
        'ease_before' => 2.5,
        'ease_after' => 2.6,
        'interval_before' => 0,
        'interval_after' => 1,
    ]);

    $otherQuestion = Question::factory()->create(['user_id' => $other->id]);
    $otherRep = Repetition::factory()->create([
        'user_id' => $other->id,
        'question_id' => $otherQuestion->id,
    ]);
    ReviewLog::create([
        'repetition_id' => $otherRep->id,
        'quality' => 5,
        'ease_before' => 2.5,
        'ease_after' => 2.6,
        'interval_before' => 0,
        'interval_after' => 1,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.totals.reviews', 1)
            ->where('stats.totals.questions', 1)
        );
});

test('dashboard retention rate reflects last 30 days quality threshold', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);
    $rep = Repetition::factory()->create([
        'user_id' => $user->id,
        'question_id' => $question->id,
    ]);

    foreach ([5, 4, 2] as $quality) {
        ReviewLog::create([
            'repetition_id' => $rep->id,
            'quality' => $quality,
            'ease_before' => 2.5,
            'ease_after' => 2.5,
            'interval_before' => 0,
            'interval_after' => 1,
        ]);
    }

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.retention.sample_size', 3)
            ->where('stats.retention.rate_30d', fn ($v) => abs($v - (2 / 3)) < 0.01)
        );
});

test('dashboard retention is null without samples', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.retention.rate_30d', null)
            ->where('stats.retention.sample_size', 0)
        );
});

test('dashboard heatmap is 84 contiguous days ending today', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $heatmap = $response->viewData('page')['props']['stats']['heatmap'];

    expect($heatmap)->toHaveCount(84);
    expect($heatmap[83]['date'])->toBe(now()->toDateString());
    expect($heatmap[0]['date'])->toBe(now()->subDays(83)->toDateString());

    foreach ($heatmap as $cell) {
        expect($cell['count'])->toBe(0);
    }
});

test('dashboard today reviewed counts today logs only', function () {
    $user = User::factory()->create();
    $question = Question::factory()->create(['user_id' => $user->id]);
    $rep = Repetition::factory()->create([
        'user_id' => $user->id,
        'question_id' => $question->id,
    ]);

    ReviewLog::create([
        'repetition_id' => $rep->id,
        'quality' => 4,
        'ease_before' => 2.5,
        'ease_after' => 2.6,
        'interval_before' => 0,
        'interval_after' => 1,
    ]);

    $old = ReviewLog::create([
        'repetition_id' => $rep->id,
        'quality' => 4,
        'ease_before' => 2.5,
        'ease_after' => 2.6,
        'interval_before' => 0,
        'interval_after' => 1,
    ]);
    DB::table('review_logs')
        ->where('id', $old->id)
        ->update(['created_at' => now()->subDays(2)]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.today.reviewed', 1)
        );
});

test('dashboard exposes due_remaining', function () {
    $user = User::factory()->create();
    $q1 = Question::factory()->create(['user_id' => $user->id]);
    $q2 = Question::factory()->create(['user_id' => $user->id]);
    Repetition::factory()->due()->create(['user_id' => $user->id, 'question_id' => $q1->id]);
    Repetition::factory()->future()->create(['user_id' => $user->id, 'question_id' => $q2->id]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.today.due_remaining', 1)
        );
});
