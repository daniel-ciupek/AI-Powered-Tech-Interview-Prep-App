<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Difficulty;
use App\Enums\Theme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
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
            'preferred_difficulty' => ['required', Rule::enum(Difficulty::class)],
            'daily_goal' => ['required', 'integer', 'min:1', 'max:50'],
            'theme' => ['required', Rule::enum(Theme::class)],
        ];
    }
}
