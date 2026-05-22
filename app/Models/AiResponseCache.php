<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $prompt_hash
 * @property array<string, mixed> $response
 * @property string $model
 * @property int $tokens_in
 * @property int $tokens_out
 * @property Carbon $created_at
 */
#[Fillable(['prompt_hash', 'response', 'model', 'tokens_in', 'tokens_out'])]
class AiResponseCache extends Model
{
    protected $table = 'ai_response_cache';

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response' => 'array',
            'created_at' => 'datetime',
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
        ];
    }
}
