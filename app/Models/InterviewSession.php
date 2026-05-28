<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use Carbon\Carbon;
use Database\Factories\InterviewSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property list<string> $topic_tags
 * @property Difficulty $difficulty
 * @property SessionStatus $status
 * @property string|null $final_report
 * @property int $tokens_used_total
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 */
#[Fillable(['user_id', 'topic_tags', 'difficulty', 'status', 'final_report', 'tokens_used_total', 'started_at', 'ended_at'])]
class InterviewSession extends Model
{
    public $timestamps = false;

    /** @use HasFactory<InterviewSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'topic_tags' => 'array',
            'difficulty' => Difficulty::class,
            'status' => SessionStatus::class,
            'tokens_used_total' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<InterviewMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(InterviewMessage::class, 'session_id')->orderBy('created_at');
    }

    public function isActive(): bool
    {
        return $this->status === SessionStatus::Active;
    }
}
