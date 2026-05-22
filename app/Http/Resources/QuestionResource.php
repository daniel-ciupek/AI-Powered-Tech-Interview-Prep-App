<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Question */
class QuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'expected_answer' => $this->expected_answer,
            'expected_keywords' => $this->expected_keywords,
            'difficulty' => $this->difficulty->value,
            'source' => $this->source->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
