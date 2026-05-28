<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\RepetitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $question_id
 * @property float $ease_factor
 * @property int $interval_days
 * @property int $repetitions_count
 * @property int|null $quality_last
 * @property Carbon $next_review_at
 * @property Carbon|null $last_reviewed_at
 */
#[Fillable(['user_id', 'question_id', 'ease_factor', 'interval_days', 'repetitions_count', 'quality_last', 'next_review_at', 'last_reviewed_at'])]
class Repetition extends Model
{
    /** @use HasFactory<RepetitionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ease_factor' => 'float',
            'interval_days' => 'integer',
            'repetitions_count' => 'integer',
            'quality_last' => 'integer',
            'next_review_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** @return HasMany<ReviewLog, $this> */
    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class);
    }

    public function isDue(): bool
    {
        return $this->next_review_at->lte(now());
    }
}
