<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiativeEntryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'entity_type' => ['nullable', 'string', 'max:255'],
            'entity_id' => ['nullable', 'integer'],
            'dexterity_mod' => ['nullable', 'integer'],
            'initiative' => ['nullable', 'integer'],
            'is_current' => ['nullable', 'boolean'],
        ];
    }
}
