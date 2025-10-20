<?php

namespace App\Http\Requests;

use App\Models\Group;
use App\Models\Map;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'region_id' => $this->filled('region_id') ? (int) $this->input('region_id') : null,
            'width' => $this->filled('width') ? (int) $this->input('width') : null,
            'height' => $this->filled('height') ? (int) $this->input('height') : null,
            'fog_data' => $this->filled('fog_data') ? $this->input('fog_data') : null,
            'gm_only' => $this->has('gm_only') ? $this->boolean('gm_only') : false,
        ]);
    }

    public function authorize(): bool
    {
        /** @var Group $group */
        $group = $this->route('group');

        return $this->user()?->can('create', [Map::class, $group]) ?? false;
    }

    public function rules(): array
    {
        /** @var Group $group */
        $group = $this->route('group');

        return [
            'title' => ['required', 'string', 'max:120'],
            'base_layer' => ['required', Rule::in(['hex', 'square', 'image'])],
            'orientation' => ['required', Rule::in(['pointy', 'flat', 'orthogonal', 'isometric', 'freeform'])],
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
