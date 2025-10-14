<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SessionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'agenda' => ['nullable', 'string'],
            'session_date' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:150'],
            'summary' => ['nullable', 'string'],
            'recording_url' => ['nullable', 'url'],
            'turn_id' => ['nullable', Rule::exists('turns', 'id')],
        ];
    }
}
