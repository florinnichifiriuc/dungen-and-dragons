<?php

namespace App\Http\Requests;

use App\Models\Group;
use App\Models\Map;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Map $map */
        $map = $this->route('map');

        return $this->user()?->can('update', $map) ?? false;
    }

    public function rules(): array
    {
        /** @var Map $map */
        $map = $this->route('map');
        /** @var Group $group */
        $group = $this->route('group');

        return [
            'title' => ['required', 'string', 'max:120'],
            'base_layer' => ['required', Rule::in(['hex', 'square', 'image'])],
            'orientation' => ['required', Rule::in(['pointy', 'flat'])],
            'width' => ['nullable', 'integer', 'min:1', 'max:200'],
            'height' => ['nullable', 'integer', 'min:1', 'max:200'],
            'gm_only' => ['sometimes', 'boolean'],
            'fog_data' => ['nullable', 'json'],
            'region_id' => [
                'nullable',
                Rule::exists('regions', 'id')->where(fn ($query) => $query->where('group_id', $group->id)),
            ],
        ];
    }
}
