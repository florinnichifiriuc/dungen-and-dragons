<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string'],
            'default_timezone' => ['required', 'string', 'timezone:all'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'turn_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'status' => ['nullable', Rule::in(Campaign::statuses())],
        ];
    }
}
