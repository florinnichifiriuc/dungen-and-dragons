<?php

namespace App\Http\Requests;

use App\Models\SessionAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SessionAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(SessionAttendance::statuses())],
            'note' => ['nullable', 'string'],
        ];
    }
}
