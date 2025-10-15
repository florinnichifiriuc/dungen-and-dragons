<?php

namespace App\Http\Requests;

use App\Models\Map;
use App\Models\MapTile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapTileStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Map $map */
        $map = $this->route('map');

        return $this->user()?->can('create', [MapTile::class, $map]) ?? false;
    }

    public function rules(): array
    {
        /** @var Map $map */
        $map = $this->route('map');

        return [
            'tile_template_id' => [
                'required',
                Rule::exists('tile_templates', 'id')->where(fn ($query) => $query->where('group_id', $map->group_id)),
            ],
            'q' => ['required', 'integer', 'between:-200,200'],
            'r' => ['required', 'integer', 'between:-200,200'],
            'elevation' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'variant' => ['nullable', 'json'],
            'locked' => ['sometimes', 'boolean'],
        ];
    }
}
