<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class AiIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prompt' => $this->filled('prompt') ? trim((string) $this->input('prompt')) : '',
        ]);
    }

    public function rules(): array
    {
        return [
            'prompt' => ['nullable', 'string', 'max:800'],
        ];
    }
}
