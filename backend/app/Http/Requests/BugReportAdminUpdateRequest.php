<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BugReportAdminUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:open,in_progress,resolved,closed'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,critical'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'note' => ['nullable', 'string'],
        ];
    }
}
