<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use App\Models\CampaignQuest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignQuestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->route('campaign');

        if (! $campaign) {
            return false;
        }

        return $this->user()?->can('create', [CampaignQuest::class, $campaign]) ?? false;
    }

    public function rules(): array
    {
        /** @var Campaign $campaign */
        $campaign = $this->route('campaign');

        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'details' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(CampaignQuest::statuses())],
            'priority' => ['required', 'string', Rule::in(CampaignQuest::priorities())],
            'region_id' => [
                'nullable',
                Rule::exists('regions', 'id')->where(function ($query) use ($campaign): void {
                    $query->where('group_id', $campaign->group_id);
                }),
            ],
            'target_turn_number' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'region_id' => $this->filled('region_id') ? $this->input('region_id') : null,
            'target_turn_number' => $this->filled('target_turn_number') ? (int) $this->input('target_turn_number') : null,
            'starts_at' => $this->filled('starts_at') ? $this->input('starts_at') : null,
            'completed_at' => $this->filled('completed_at') ? $this->input('completed_at') : null,
        ]);
    }
}
