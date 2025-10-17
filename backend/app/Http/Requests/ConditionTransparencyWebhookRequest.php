<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConditionTransparencyWebhookRequest extends FormRequest
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
            'url' => ['required', 'url', 'max:255'],
        ];
    }
}
