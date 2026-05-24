<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskAboutQuestionRequest extends FormRequest
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
            'content' => ['required', 'string', 'max:500'],
            'history' => ['array', 'max:10'],
            'history.*.role' => ['required_with:history.*.content', 'in:user,assistant'],
            'history.*.content' => ['required_with:history.*.role', 'string', 'max:2000'],
        ];
    }
}
