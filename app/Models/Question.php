<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string $content
 * @property string|null $expected_answer
 * @property list<string> $expected_keywords
 * @property Difficulty $difficulty
 * @property QuestionSource $source
 */
#[Fillable(['content', 'expected_answer', 'expected_keywords', 'difficulty', 'source'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_keywords' => 'array',
            'difficulty' => Difficulty::class,
            'source' => QuestionSource::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<Repetition, $this> */
    public function repetition(): HasOne
    {
        return $this->hasOne(Repetition::class);
    }
}
