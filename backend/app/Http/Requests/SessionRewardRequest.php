<?php

namespace App\Http\Requests;

use App\Models\SessionReward;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SessionRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reward_type' => ['required', 'string', Rule::in(SessionReward::types())],
            'title' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'awarded_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
