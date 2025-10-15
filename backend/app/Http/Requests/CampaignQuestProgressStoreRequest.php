<?php

namespace App\Http\Requests;

use App\Models\CampaignQuest;
use App\Models\CampaignQuestUpdate;
use Illuminate\Foundation\Http\FormRequest;

class CampaignQuestProgressStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CampaignQuest|null $quest */
        $quest = $this->route('quest');

        if (! $quest) {
            return false;
        }

        return $this->user()?->can('create', [CampaignQuestUpdate::class, $quest]) ?? false;
    }

    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'recorded_at' => $this->filled('recorded_at') ? $this->input('recorded_at') : null,
        ]);
    }
}
