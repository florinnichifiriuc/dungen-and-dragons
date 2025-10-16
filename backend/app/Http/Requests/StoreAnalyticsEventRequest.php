<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnalyticsEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:191'],
            'payload' => ['nullable', 'array'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ];
    }
}
