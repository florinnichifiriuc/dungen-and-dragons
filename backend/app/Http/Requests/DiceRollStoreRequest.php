<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiceRollStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expression' => ['required', 'regex:/^[0-9]*d[0-9]+([+-][0-9]+)?$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'expression.regex' => 'Provide a valid dice expression such as 1d20+5.',
        ];
    }
}
