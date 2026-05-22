<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Difficulty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tags' => ['sometimes', 'array', 'max:5'],
            'tags.*' => ['string', 'max:50'],
            'difficulty' => ['sometimes', Rule::enum(Difficulty::class)],
        ];
    }
}
