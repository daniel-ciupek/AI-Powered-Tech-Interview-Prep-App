<?php

declare(strict_types=1);

use App\Enums\Difficulty;

test('difficulty enum has correct values', function () {
    expect(Difficulty::Junior->value)->toBe('junior')
        ->and(Difficulty::Mid->value)->toBe('mid')
        ->and(Difficulty::Senior->value)->toBe('senior');
});

test('difficulty enum labels are correct', function () {
    expect(Difficulty::Junior->label())->toBe('Junior')
        ->and(Difficulty::Mid->label())->toBe('Mid')
        ->and(Difficulty::Senior->label())->toBe('Senior');
});

test('difficulty can be created from string value', function () {
    expect(Difficulty::from('mid'))->toBe(Difficulty::Mid);
});
