<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiCreativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $domains = array_keys((array) config('ai.prompts.creative'));

        return [
            'domain' => ['required', 'string', Rule::in($domains)],
            'prompt' => ['nullable', 'string', 'max:2000'],
            'context' => ['nullable', 'array'],
        ];
    }
}
