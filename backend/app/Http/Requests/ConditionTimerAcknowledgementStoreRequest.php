<?php

namespace App\Http\Requests;

use App\Models\MapToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConditionTimerAcknowledgementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'map_token_id' => ['required', 'integer', 'exists:map_tokens,id'],
            'condition_key' => ['required', 'string', 'max:64', Rule::in(MapToken::CONDITIONS)],
            'summary_generated_at' => ['required', 'date'],
            'queued_at' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:32', Rule::in(['online', 'offline'])],
        ];
    }
}
