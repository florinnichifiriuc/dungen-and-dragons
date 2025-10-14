<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegionUpdateRequest extends FormRequest
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
            'dungeon_master_id' => ['nullable', 'integer', 'exists:users,id'],
            'turn_duration_hours' => ['required', 'integer', 'in:6,24'],
            'next_turn_at' => ['nullable', 'date'],
        ];
    }
}
