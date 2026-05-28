<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $session_id
 * @property MessageRole $role
 * @property string $content
 * @property int $tokens_used
 * @property Carbon $created_at
 */
#[Fillable(['session_id', 'role', 'content', 'tokens_used'])]
class InterviewMessage extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MessageRole::class,
            'tokens_used' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<InterviewSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'session_id');
    }
}
