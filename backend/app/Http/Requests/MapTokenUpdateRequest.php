<?php

namespace App\Http\Requests;

use App\Models\MapToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapTokenUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MapToken $token */
        $token = $this->route('token');

        return $this->user()?->can('update', $token) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'x' => ['sometimes', 'integer', 'between:-500,500'],
            'y' => ['sometimes', 'integer', 'between:-500,500'],
            'color' => ['nullable', 'string', 'max:32'],
            'size' => ['nullable', Rule::in(['tiny', 'small', 'medium', 'large', 'huge', 'gargantuan'])],
            'faction' => ['sometimes', 'nullable', Rule::in(MapToken::FACTIONS)],
            'initiative' => ['nullable', 'integer', 'between:-50,50'],
            'status_effects' => ['nullable', 'string', 'max:255'],
            'hit_points' => ['nullable', 'integer', 'between:-999,999'],
            'temporary_hit_points' => ['nullable', 'integer', 'between:0,999'],
            'max_hit_points' => ['nullable', 'integer', 'between:1,999'],
            'z_index' => ['nullable', 'integer', 'between:-100,100'],
            'hidden' => ['sometimes', 'boolean'],
            'gm_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['z_index', 'initiative', 'hit_points', 'temporary_hit_points', 'max_hit_points'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->exists('faction') && $this->input('faction') === '') {
            $this->merge(['faction' => null]);
        }
    }
}
