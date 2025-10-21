<?php

namespace App\Http\Requests;

use App\Models\Group;
use App\Models\TileTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TileTemplateStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'world_id' => $this->filled('world_id') ? (int) $this->input('world_id') : null,
            'key' => $this->filled('key') ? $this->input('key') : null,
            'image_path' => $this->filled('image_path') ? $this->input('image_path') : null,
            'category' => $this->filled('category') ? $this->input('category') : null,
            'terrain_traits' => $this->coerceJsonList('terrain_traits'),
            'encounter_tags' => $this->coerceJsonList('encounter_tags'),
            'thumbnail_path' => $this->filled('thumbnail_path') ? $this->input('thumbnail_path') : null,
            'ai_metadata' => $this->filled('ai_metadata') ? $this->input('ai_metadata') : null,
        ]);
    }

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
            'category' => ['nullable', 'string', 'max:64'],
            'movement_cost' => ['required', 'integer', 'min:0', 'max:20'],
            'defense_bonus' => ['required', 'integer', 'min:0', 'max:20'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'image_upload' => ['nullable', 'image', 'max:5120'],
            'edge_profile' => ['nullable', 'json'],
            'terrain_traits' => ['nullable', 'json'],
            'encounter_tags' => ['nullable', 'json'],
            'thumbnail_path' => ['nullable', 'string', 'max:255'],
            'ai_metadata' => ['nullable', 'json'],
            'world_id' => [
                'nullable',
                Rule::exists('worlds', 'id')->where(fn ($query) => $query->where('group_id', $group->id)),
            ],
        ];
}

    private function coerceJsonList(string $key): ?string
    {
        if (!$this->filled($key)) {
            return null;
        }

        $value = $this->input($key);

        if (is_array($value)) {
            return json_encode(array_values(array_filter($value, static fn ($item) => $item !== null && $item !== '')));
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            return $trimmed;
        }

        $items = array_values(array_filter(array_map(static fn ($item) => trim($item), explode(',', $value)), static fn ($item) => $item !== ''));

        return json_encode($items);
    }
}
