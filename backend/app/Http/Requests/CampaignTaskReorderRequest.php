<?php

namespace App\Http\Requests;

use App\Models\CampaignTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignTaskReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(CampaignTask::statuses())],
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:campaign_tasks,id'],
        ];
    }
}
