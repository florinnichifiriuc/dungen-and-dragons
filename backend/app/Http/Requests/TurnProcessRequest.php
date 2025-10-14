<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TurnProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'summary' => ['nullable', 'string', 'max:2000'],
            'use_ai_fallback' => ['sometimes', 'boolean'],
        ];
    }
}
