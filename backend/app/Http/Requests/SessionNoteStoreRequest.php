<?php

namespace App\Http\Requests;

use App\Models\SessionNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SessionNoteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'visibility' => ['required', Rule::in(SessionNote::visibilities())],
            'is_pinned' => ['sometimes', 'boolean'],
        ];
    }
}
