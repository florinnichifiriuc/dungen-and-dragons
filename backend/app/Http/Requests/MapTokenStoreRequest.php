<?php

namespace App\Http\Requests;

use App\Models\Map;
use App\Models\MapToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapTokenStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Map $map */
        $map = $this->route('map');

        return $this->user()?->can('create', [MapToken::class, $map]) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'x' => ['required', 'integer', 'between:-500,500'],
            'y' => ['required', 'integer', 'between:-500,500'],
            'color' => ['nullable', 'string', 'max:32'],
            'size' => ['nullable', Rule::in(['tiny', 'small', 'medium', 'large', 'huge', 'gargantuan'])],
            'faction' => ['nullable', Rule::in(MapToken::FACTIONS)],
            'initiative' => ['nullable', 'integer', 'between:-50,50'],
            'status_effects' => ['nullable', 'string', 'max:255'],
            'status_conditions' => ['nullable', 'array'],
            'status_conditions.*' => ['string', Rule::in(MapToken::CONDITIONS)],
            'status_condition_durations' => ['nullable', 'array'],
            'status_condition_durations.*' => ['nullable', 'integer', 'between:1,'.MapToken::MAX_CONDITION_DURATION],
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

        $durations = $this->input('status_condition_durations');

        if (is_array($durations)) {
            foreach ($durations as $condition => $value) {
                if ($value === '') {
                    $durations[$condition] = null;
                }
            }

            $this->merge(['status_condition_durations' => $durations]);
        }
    }
}
