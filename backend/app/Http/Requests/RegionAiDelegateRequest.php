<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegionAiDelegateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $region = $this->route('region');

        return $region && $this->user()?->can('delegateToAi', $region);
    }

    public function rules(): array
    {
        return [
            'focus' => ['nullable', 'string', 'max:500'],
        ];
    }
}
