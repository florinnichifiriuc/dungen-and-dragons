<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use App\Models\CampaignEntity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class CampaignEntityStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        if (! $campaign instanceof Campaign) {
            return false;
        }

        return $this->user()?->can('create', [CampaignEntity::class, $campaign]) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', 'in:'.implode(',', CampaignEntity::types())],
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'pronunciation' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', 'string', 'in:'.implode(',', CampaignEntity::visibilities())],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'ai_controlled' => ['boolean'],
            'initiative_default' => ['nullable', 'integer', 'between:0,40'],
            'description' => ['nullable', 'string'],
            'stats' => ['nullable', 'array'],
            'stats.*.label' => ['required_with:stats.*.value', 'string', 'max:100'],
            'stats.*.value' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Normalized tag names stripped of whitespace and duplicates.
     *
     * @return array<int, string>
     */
    public function tagNames(): array
    {
        $tags = array_filter(array_map(
            fn ($value) => trim((string) $value),
            $this->input('tags', [])
        ));

        return array_values(array_unique($tags));
    }

    /**
     * Build the sanitized payload for creation.
     *
     * @return array<string, mixed>
     */
    public function validatedEntityData(): array
    {
        $data = $this->validated();

        return Arr::except($data, ['tags']);
    }
}
