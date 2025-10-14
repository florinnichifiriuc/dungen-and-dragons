<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiativeEntryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'dexterity_mod' => ['sometimes', 'integer'],
            'initiative' => ['sometimes', 'integer'],
            'is_current' => ['sometimes', 'boolean'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
