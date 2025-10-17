<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConditionTransparencyExportRequest extends FormRequest
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
            'format' => ['nullable', 'in:csv,json'],
            'visibility_mode' => ['nullable', 'in:counts,details'],
            'since' => ['nullable', 'date'],
        ];
    }
}
