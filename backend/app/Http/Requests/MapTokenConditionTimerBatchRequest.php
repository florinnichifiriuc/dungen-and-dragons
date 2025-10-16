<?php

namespace App\Http\Requests;

use App\Models\Map;
use App\Models\MapToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MapTokenConditionTimerBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $map = $this->route('map');

        if (! $user || ! $map instanceof Map) {
            return false;
        }

        return $user->can('create', [MapToken::class, $map]);
    }

    protected function prepareForValidation(): void
    {
        $adjustments = $this->input('adjustments');

        if (! is_array($adjustments)) {
            return;
        }

        $normalized = array_map(function ($adjustment) {
            if (! is_array($adjustment)) {
                return $adjustment;
            }

            return [
                'token_id' => isset($adjustment['token_id']) ? (int) $adjustment['token_id'] : null,
                'condition' => $adjustment['condition'] ?? null,
                'delta' => array_key_exists('delta', $adjustment) && $adjustment['delta'] !== null
                    ? (int) $adjustment['delta']
                    : null,
                'set_to' => array_key_exists('set_to', $adjustment) && $adjustment['set_to'] !== null
                    ? (int) $adjustment['set_to']
                    : null,
                'expected_rounds' => array_key_exists('expected_rounds', $adjustment) && $adjustment['expected_rounds'] !== null
                    ? (int) $adjustment['expected_rounds']
                    : null,
            ];
        }, $adjustments);

        $this->merge(['adjustments' => $normalized]);
    }

    public function rules(): array
    {
        return [
            'adjustments' => ['required', 'array', 'min:1'],
            'adjustments.*.token_id' => ['required', 'integer'],
            'adjustments.*.condition' => ['required', 'string', Rule::in(MapToken::CONDITIONS)],
            'adjustments.*.delta' => [
                'nullable',
                'integer',
                'not_in:0',
                'between:-'.MapToken::MAX_CONDITION_DURATION.','.MapToken::MAX_CONDITION_DURATION,
            ],
            'adjustments.*.set_to' => [
                'nullable',
                'integer',
                'between:1,'.MapToken::MAX_CONDITION_DURATION,
            ],
            'adjustments.*.expected_rounds' => ['nullable', 'integer', 'between:1,'.MapToken::MAX_CONDITION_DURATION],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $adjustments = $this->input('adjustments');

            if (! is_array($adjustments)) {
                return;
            }

            foreach ($adjustments as $index => $adjustment) {
                $hasDelta = array_key_exists('delta', $adjustment) && $adjustment['delta'] !== null;
                $hasSet = array_key_exists('set_to', $adjustment) && $adjustment['set_to'] !== null;

                if (! $hasDelta && ! $hasSet) {
                    $validator->errors()->add("adjustments.$index.delta", 'An adjustment must provide a delta or set value.');
                }

                if ($hasDelta && $hasSet) {
                    $validator->errors()->add("adjustments.$index.delta", 'Choose either a delta or a set value, not both.');
                }
            }
        });
    }
}

