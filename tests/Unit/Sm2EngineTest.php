<?php

declare(strict_types=1);

use App\Services\Sm2Engine;

// ─── Reset path (q < 3) ─────────────────────────────────────────────────────

test('quality 0 resets repetitions count and sets interval to 1', function () {
    $result = (new Sm2Engine)->calculate(2.50, 10, 3, 0);

    expect($result['repetitions_count'])->toBe(0)
        ->and($result['interval_days'])->toBe(1);
});

test('quality 1 resets repetitions count and sets interval to 1', function () {
    $result = (new Sm2Engine)->calculate(2.50, 6, 2, 1);

    expect($result['repetitions_count'])->toBe(0)
        ->and($result['interval_days'])->toBe(1);
});

test('quality 2 (red) resets repetitions count and sets interval to 1', function () {
    $result = (new Sm2Engine)->calculate(2.50, 6, 5, 2);

    expect($result['repetitions_count'])->toBe(0)
        ->and($result['interval_days'])->toBe(1);
});

// ─── Success path (q >= 3) ──────────────────────────────────────────────────

test('first successful review sets interval to 1 day', function () {
    $result = (new Sm2Engine)->calculate(2.50, 0, 0, 4);

    expect($result['repetitions_count'])->toBe(1)
        ->and($result['interval_days'])->toBe(1);
});

test('second successful review sets interval to 6 days', function () {
    $result = (new Sm2Engine)->calculate(2.50, 1, 1, 4);

    expect($result['repetitions_count'])->toBe(2)
        ->and($result['interval_days'])->toBe(6);
});

test('third review interval is previous interval multiplied by EF', function () {
    $result = (new Sm2Engine)->calculate(2.50, 6, 2, 4);

    // EF_new for q=4: 2.50 + (0.1 - 1*(0.08 + 1*0.02)) = 2.50 + 0.0 = 2.50
    // interval = round(6 * 2.50) = 15
    expect($result['repetitions_count'])->toBe(3)
        ->and($result['interval_days'])->toBe(15);
});

test('quality 3 keeps ease factor roughly unchanged', function () {
    $result = (new Sm2Engine)->calculate(2.50, 6, 2, 3);

    // q=3: delta = 0.1 - 2*(0.08 + 2*0.02) = 0.1 - 2*0.12 = 0.1 - 0.24 = -0.14
    // EF_new = 2.50 - 0.14 = 2.36
    expect($result['ease_factor'])->toBe(2.36);
});

test('quality 4 (green) increases ease factor by 0.0', function () {
    $result = (new Sm2Engine)->calculate(2.50, 0, 0, 4);

    // q=4: delta = 0.1 - 1*(0.08+0.02) = 0.0
    expect($result['ease_factor'])->toBe(2.50);
});

test('quality 5 increases ease factor by 0.1', function () {
    $result = (new Sm2Engine)->calculate(2.50, 0, 0, 5);

    // q=5: delta = 0.1 - 0*(...)  = 0.1
    expect($result['ease_factor'])->toBe(2.60);
});

// ─── EF minimum clamp ───────────────────────────────────────────────────────

test('ease factor never drops below minimum 1.30', function () {
    // q=0 with low existing EF should clamp to minimum
    $result = (new Sm2Engine)->calculate(1.30, 1, 0, 0);

    expect($result['ease_factor'])->toBeGreaterThanOrEqual(Sm2Engine::EF_MIN);
});

test('ease factor clamps to minimum when formula would go lower', function () {
    // q=0: delta = 0.1 - 5*(0.08 + 5*0.02) = 0.1 - 5*0.18 = 0.1 - 0.9 = -0.8
    // 1.35 - 0.8 = 0.55 → clamped to 1.30
    $result = (new Sm2Engine)->calculate(1.35, 1, 0, 0);

    expect($result['ease_factor'])->toBe(Sm2Engine::EF_MIN);
});

// ─── EF defaults ────────────────────────────────────────────────────────────

test('default ease factor constant is 2.50', function () {
    expect(Sm2Engine::EF_DEFAULT)->toBe(2.50);
});

test('minimum ease factor constant is 1.30', function () {
    expect(Sm2Engine::EF_MIN)->toBe(1.30);
});

// ─── Long sequence simulation ────────────────────────────────────────────────

test('repeated quality 4 reviews grow interval exponentially', function () {
    $engine = new Sm2Engine;
    $ef = 2.50;
    $interval = 0;
    $count = 0;

    // Simulate 5 successful reviews
    for ($i = 0; $i < 5; $i++) {
        $result = $engine->calculate($ef, $interval, $count, 4);
        $ef = $result['ease_factor'];
        $interval = $result['interval_days'];
        $count = $result['repetitions_count'];
    }

    // After 5 reviews: 1 → 6 → 15 → 37 → 92
    expect($count)->toBe(5)
        ->and($interval)->toBeGreaterThan(6);
});

test('failed review after several successes resets but preserves EF', function () {
    $engine = new Sm2Engine;

    // Get to repetitions_count=3 first
    $state = $engine->calculate(2.50, 6, 2, 4);

    // Now fail
    $reset = $engine->calculate($state['ease_factor'], $state['interval_days'], $state['repetitions_count'], 2);

    expect($reset['repetitions_count'])->toBe(0)
        ->and($reset['interval_days'])->toBe(1);
});
