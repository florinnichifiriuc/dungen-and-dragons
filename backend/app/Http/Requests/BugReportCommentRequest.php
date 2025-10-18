<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BugReportCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
        ];
    }
}
