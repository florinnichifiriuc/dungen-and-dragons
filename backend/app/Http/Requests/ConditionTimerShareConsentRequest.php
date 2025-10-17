<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConditionTimerShareConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('group');

        return $group && $this->user()?->can('update', $group);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'consented' => ['required', 'boolean'],
            'visibility_mode' => ['nullable', 'in:counts,details'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
