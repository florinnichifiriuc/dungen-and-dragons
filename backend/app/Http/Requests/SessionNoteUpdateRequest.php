<?php

namespace App\Http\Requests;

use App\Models\SessionNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SessionNoteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'required', 'string'],
            'visibility' => ['sometimes', 'required', Rule::in(SessionNote::visibilities())],
            'is_pinned' => ['sometimes', 'boolean'],
        ];
    }
}
