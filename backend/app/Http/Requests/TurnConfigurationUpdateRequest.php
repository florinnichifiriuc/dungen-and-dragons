<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TurnConfigurationUpdateRequest extends FormRequest
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
            'turn_duration_hours' => ['required', 'integer', 'in:6,24'],
            'next_turn_at' => ['nullable', 'date'],
        ];
    }
}
