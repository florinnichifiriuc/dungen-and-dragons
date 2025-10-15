<?php

namespace App\Http\Requests;

use App\Models\Map;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapTileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mapParam = $this->route('map');

        $map = $mapParam instanceof Map
            ? $mapParam
            : Map::query()->findOrFail($mapParam);

        return [
            'tile_template_id' => [
                'nullable',
                Rule::exists('tile_templates', 'id')->where(fn ($query) => $query->where('group_id', $map->group_id)),
            ],
            'q' => ['nullable', 'integer', 'between:-200,200'],
            'r' => ['nullable', 'integer', 'between:-200,200'],
            'elevation' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'variant' => ['nullable', 'json'],
            'locked' => ['sometimes', 'boolean'],
        ];
    }
}
