<?php

namespace App\Http\Requests;

use App\Models\CampaignTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignTaskUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(CampaignTask::statuses())],
            'due_turn_number' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'assigned_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'assigned_group_id' => ['sometimes', 'nullable', 'integer', 'exists:groups,id'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
