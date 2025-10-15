<?php

namespace App\Http\Requests;

use App\Models\Map;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapFogUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Map|null $map */
        $map = $this->route('map');

        return $map !== null && $this->user()?->can('update', $map);
    }

    public function rules(): array
    {
        /** @var Map|null $map */
        $map = $this->route('map');

        return [
            'hidden_tile_ids' => ['sometimes', 'array'],
            'hidden_tile_ids.*' => [
                'integer',
                Rule::exists('map_tiles', 'id')->where(fn ($query) => $query->where('map_id', $map?->id ?? 0)),
            ],
        ];
    }
}
