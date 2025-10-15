<?php

namespace App\Http\Requests;

use App\Models\CampaignTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignTaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(CampaignTask::statuses())],
            'due_turn_number' => ['nullable', 'integer', 'min:1'],
            'due_at' => ['nullable', 'date'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ];
    }
}
