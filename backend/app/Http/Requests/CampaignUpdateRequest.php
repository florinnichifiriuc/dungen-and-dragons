<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string'],
            'status' => ['required', Rule::in(Campaign::statuses())],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'default_timezone' => ['required', 'string', 'timezone:all'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'turn_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ];
    }
}
