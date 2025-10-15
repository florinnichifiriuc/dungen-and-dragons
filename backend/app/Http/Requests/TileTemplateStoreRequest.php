<?php

namespace App\Http\Requests;

use App\Models\Group;
use App\Models\TileTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TileTemplateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Group $group */
        $group = $this->route('group');

        return $this->user()?->can('create', [TileTemplate::class, $group]) ?? false;
    }

    public function rules(): array
    {
        /** @var Group $group */
        $group = $this->route('group');

        return [
            'name' => ['required', 'string', 'max:120'],
            'key' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('tile_templates', 'key')->where(fn ($query) => $query->where('group_id', $group->id)),
            ],
            'terrain_type' => ['required', 'string', 'max:64'],
            'movement_cost' => ['required', 'integer', 'min:0', 'max:20'],
            'defense_bonus' => ['required', 'integer', 'min:0', 'max:20'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'edge_profile' => ['nullable', 'json'],
            'world_id' => [
                'nullable',
                Rule::exists('worlds', 'id')->where(fn ($query) => $query->where('group_id', $group->id)),
            ],
        ];
    }
}
