<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorldUpdateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:512'],
            'description' => ['nullable', 'string'],
            'default_turn_duration_hours' => ['required', 'integer', 'between:1,168'],
        ];
    }
}
