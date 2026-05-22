<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $repetition_id
 * @property int $quality
 * @property float $ease_before
 * @property float $ease_after
 * @property int $interval_before
 * @property int $interval_after
 * @property Carbon $created_at
 */
#[Fillable(['repetition_id', 'quality', 'ease_before', 'ease_after', 'interval_before', 'interval_after'])]
class ReviewLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quality' => 'integer',
            'ease_before' => 'float',
            'ease_after' => 'float',
            'interval_before' => 'integer',
            'interval_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Repetition, $this> */
    public function repetition(): BelongsTo
    {
        return $this->belongsTo(Repetition::class);
    }
}
